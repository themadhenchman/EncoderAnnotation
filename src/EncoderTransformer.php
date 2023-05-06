<?php

namespace EncoderAnnotation;

use EncoderAnnotation\Attributes\EncodeFromHomomorphCollection;
use EncoderAnnotation\Attributes\EncodeFromParentHomomorphCollection;
use EncoderAnnotation\Attributes\EncodeParentProperty;
use EncoderAnnotation\Attributes\EncodeProperty;
use EncoderAnnotation\Attributes\EncodeClass;

class EncoderTransformer
{
    public function __construct(public readonly ?AnnotationNode $parentNode = null)
    {
    }

    public function marshall(object $object, string $scenario = Constants::DEFAULT_SCENARIO): ?AnnotationNode
    {
        $classReflection = new \ReflectionClass($object);
        $parentNode  = null;

        foreach ($classReflection->getAttributes(EncodeClass::class) as $reflection) {
            /** @var EncodeClass $encodeClassInstance */
            $encodeClassInstance = $reflection->newInstance();
            if ($encodeClassInstance->scenario == $scenario) {
                $parentNode = new AnnotationNode(
                    $encodeClassInstance->publicName,
                    null,
                    $encodeClassInstance->attributes,
                    $this->parentNode
                );
                break;
            }
        }

        // We do not have a class meeting the annotations.
        if (is_null($parentNode)) {
            return $parentNode;
        }

        foreach ($classReflection->getProperties() as $reflection) {
            foreach ($reflection->getAttributes(EncodeProperty::class) as $attributeReflection) {
                /** @var  EncodeProperty $annotationInstance */
                $annotationInstance = $attributeReflection->newInstance();

                if ($annotationInstance->scenario !== $scenario) {
                    continue;
                }
                $callback = $this->checkForNesting(...);

                $value = (function () use ($reflection, $scenario, $callback) {
                    return $callback($this->{$reflection->getName()}, $scenario);
                })->call($object);

                $child = new AnnotationNode(
                    $annotationInstance->publicName,
                    $value,
                    $annotationInstance->attributes,
                    $parentNode
                );

                $parentNode->addChild($child);
            }

            foreach ($reflection->getAttributes(EncodeFromHomomorphCollection::class) as $attributeReflection) {
                /** @var  EncodeFromHomomorphCollection $annotationInstance */
                $annotationInstance = $attributeReflection->newInstance();

                // @todo This creates a problem, we must also be able to encode NULL is it really necessary.
                if ($annotationInstance->scenario !== $scenario) {
                    continue;
                }

                if (is_null($annotationInstance->type)) {
                    $value = (function () use ($reflection, $scenario, $callback) {
                        return $this->{$reflection->getName()};
                    })->call($object);

                    if (!$value) {
                        continue;
                    }

                    $child = new AnnotationNode(
                        $annotationInstance->publicName,
                        $value,
                        $annotationInstance->attributes,
                        $parentNode
                    );

                    $parentNode->addChild($child);
                    continue;
                } else {
                    $value = (function () use ($reflection, $scenario, $callback) {
                        return $this->{$reflection->getName()};
                    })->call($object);

                    if (!$value) {
                        continue;
                    }

                    $checkForNestingCallback = $this->marshall(...);

                    $result = array_map(function ($element) use ($checkForNestingCallback, $scenario) {
                        return $checkForNestingCallback($element, $scenario);
                    }, $value);

                    $child = new AnnotationNode(
                        $annotationInstance->publicName,
                        $result,
                        $annotationInstance->attributes,
                        $parentNode
                    );

                    $parentNode->addChild($child);
                }
            }
        }

        foreach ($classReflection->getAttributes(EncodeParentProperty::class) as $reflection) {
            // @todo This can be consolidated with EncodeProperty. The only difference is the retrieval of the property name.
            /** @var  EncodeParentProperty $annotationInstance */
            $annotationInstance = $reflection->newInstance();

            if ($annotationInstance->scenario !== $scenario) {
                continue;
            }
            $callback = $this->checkForNesting(...);

            $value = (function () use ($annotationInstance, $scenario, $callback) {
                return $callback($this->{$annotationInstance->propertyName}, $scenario);
            })->call($object);

            $child = new AnnotationNode(
                $annotationInstance->publicName,
                $value,
                $annotationInstance->attributes,
                $parentNode
            );

            $parentNode->addChild($child);
        }

        foreach ($classReflection->getAttributes(EncodeFromParentHomomorphCollection::class) as $reflectionAttribute) {
            /** @var EncodeFromParentHomomorphCollection $reflectionInstance */
            $reflectionInstance = $reflectionAttribute->newInstance();

            // @todo This creates a problem, we must also be able to encode NULL is it really necessary.
            if ($reflectionInstance->scenario !== $scenario) {
                continue;
            }

            if (is_null($reflectionInstance->type)) {
                $value = \Closure::bind(fn() => $this->{$reflectionInstance->attributeName}, $object, $object)();
                // We do not need to check for nesting here as the connection is of a simple datatype.

                if (!$value) {
                    continue;
                }

                $child = new AnnotationNode(
                    $reflectionInstance->publicName,
                    $value,
                    $reflectionInstance->attributes,
                    $parentNode
                );

                $parentNode->addChild($child);
            } else {
                $value = (function () use ($reflectionInstance, $scenario, $callback) {
                    return $this->{$reflectionInstance->attributeName};
                })->call($object);

                if (!$value) {
                    continue;
                }

                $checkForNestingCallback = $this->marshall(...);

                $result = array_map(function ($element) use ($checkForNestingCallback, $scenario) {
                    return $checkForNestingCallback($element, $scenario);
                }, $value);

                $child = new AnnotationNode(
                    $reflectionInstance->publicName,
                    $result,
                    $reflectionInstance->attributes,
                    $parentNode
                );

                $parentNode->addChild($child);
            }
        }

        return $parentNode;
    }

    protected function checkForNesting($value, $scenario)
    {
        // @todo because of the new Homomorphic annotation we will not be needing this functionality anymore.
        if (is_array($value)) {
            $result = [];

            foreach ($value as $key => $partial) {
                $result[$key] = is_object($partial) ? $this->marshall($partial, $scenario) : $partial;
            }

            return $result;
        }

        return is_object($value) ? $this->marshall($value, $scenario) : $value;
    }
}