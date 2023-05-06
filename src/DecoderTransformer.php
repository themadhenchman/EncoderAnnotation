<?php

namespace EncoderAnnotation;

use EncoderAnnotation\Attributes\DecodeClass;
use EncoderAnnotation\Attributes\DecodeToHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentProperty;
use EncoderAnnotation\Attributes\DecodeToProperty;
use EncoderAnnotation\Attributes\EncodeClass;
use ReflectionAttribute;

class DecoderTransformer
{
    public function unMarshall(AnnotationNode $data, object $object, string $scenario = Constants::DEFAULT_SCENARIO): object
    {
        $classReflection = new \ReflectionClass($object);
        $doNotProcessObject = true;

        foreach ($classReflection->getAttributes(DecodeClass::class) as $reflection) {
            /** @var EncodeClass $encodeClassInstance */
            $encodeClassInstance = $reflection->newInstance();
            if ($encodeClassInstance->scenario == $scenario) {
                $doNotProcessObject = false;
                break;
            }
        }

        if ($doNotProcessObject) {
            return $object;
        }

        $classReflection = new \ReflectionClass($object);

        foreach ($classReflection->getAttributes(DecodeToParentProperty::class) as $reflectionProperty) {
            /** @var DecodeToParentProperty $reflectionPropertyInstance */
            $reflectionPropertyInstance = $reflectionProperty->newInstance();
            $dataName = $reflectionPropertyInstance->publicName;
            // @todo This is the only difference for processing this compared to DecodeToProperty
            $attributeName = $reflectionPropertyInstance->propertyName;
            $child = $data[$dataName];

            if (is_null($child)) {
                continue;
            }

            // If it is a value the attribute is a primitive.
            if ($child->value) {
                \Closure::bind(fn() => $this->{$attributeName} = $child->value, $object, $object)();
            }

            // If the child has children itself it is a complex value, and we need to reconstruct further.
            $propertyChildren = $child->getChildren();

            if (!empty($propertyChildren)) {
                $objectType = $reflection->getType()->getName();
                $object->{$attributeName} = $this->unMarshall($child, new $objectType(), $scenario);
            }
        }

        foreach ($classReflection->getProperties() as $reflection) {
            foreach ($reflection->getAttributes(DecodeToProperty::class) as $reflectionProperty) {
                /** @var DecodeToProperty $reflectionPropertyInstance */
                $reflectionPropertyInstance = $reflectionProperty->newInstance();
                $dataName = $reflectionPropertyInstance->publicName;
                $attributeName = $reflection->getName();
                $child = $data[$dataName];

                if (is_null($child)) {
                    continue;
                }

                // If it is a value the attribute is a primitive.
                if ($child->value) {
                    \Closure::bind(fn() => $this->{$attributeName} = $child->value, $object, $object)();
                }

                // If the child has children itself it is a complex value, and we need to reconstruct further.

                $propertyChildren = $child->getChildren();
                if (!empty($propertyChildren)) {
                    $objectType = $reflection->getType()->getName();
                    $object->{$attributeName} = $this->unMarshall($child, new $objectType(), $scenario);
                }
            }


            foreach ($reflection->getAttributes(DecodeToHomomorphCollection::class) as $reflectionProperty) {
                /** @var DecodeToHomomorphCollection $reflectionPropertyInstance */
                $reflectionPropertyInstance = $reflectionProperty->newInstance();
                $dataName = $reflectionPropertyInstance->publicName;
                $attributeName = $reflection->getName();
                $child = $data[$dataName];

                if (is_null($child)) {
                    continue;
                }

                if (is_null($reflectionPropertyInstance->type)) {
                    \Closure::bind(fn() => $this->{$attributeName} = $child->value, $object, $object)();
                } else {
                    $result = [];

                    foreach($child->value as $item) {
                        $result[] = $this->unMarshall($item, new $reflectionPropertyInstance->type, $scenario);
                    }

                    \Closure::bind(fn() => $this->{$attributeName} = $result, $object, $object)();
                }
            }
        }

        foreach ($classReflection->getAttributes(DecodeToParentHomomorphCollection::class) as $reflectionProperty) {
            /** @var DecodeToParentHomomorphCollection $reflectionPropertyInstance */
            $reflectionPropertyInstance = $reflectionProperty->newInstance();
            $dataName = $reflectionPropertyInstance->publicName;
            $attributeName = $reflectionPropertyInstance->attributeName;
            $child = $data[$dataName];

            if (is_null($child)) {
                continue;
            }

            if (is_null($reflectionPropertyInstance->type)) {
                \Closure::bind(fn() => $this->{$attributeName} = $child->value, $object, $object)();
            } else {
                $result = [];

                foreach($child->value as $item) {
                    $result[] = $this->unMarshall($item, new $reflectionPropertyInstance->type, $scenario);
                }

                \Closure::bind(fn() => $this->{$attributeName} = $result, $object, $object)();
            }
        }

        return $object;
    }
}