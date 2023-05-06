<?php

namespace EncoderAnnotation;

use EncoderAnnotation\Attributes\DecodeClass;
use EncoderAnnotation\Attributes\DecodeToHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentProperty;
use EncoderAnnotation\Attributes\DecodeToProperty;
use EncoderAnnotation\Attributes\EncodeClass;

class Decoder
{
    public function decode($value, object $object, string $scenario): AnnotationNode
    {
        return $this->buildAnnotationTree(new AnnotationNode(''), json_decode($value, JSON_OBJECT_AS_ARRAY), $object, $scenario);
    }

    public function getNode($type, string $name, array $data)
    {
        new \ReflectionProperty($type, $name);

    }

    protected function buildAnnotationTree(AnnotationNode $rootNode, array $values, object $object, string $scenario): AnnotationNode
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
            return $rootNode;
        }

        $processedKeys = [];


        //@todo Turn this around, look for items from reflection in data not the other way around.

        foreach ($classReflection->getProperties() as $reflection) {
            foreach ($reflection->getAttributes(DecodeToProperty::class) as $attributeReflection) {
                /** @var  DecodeToProperty $annotationInstance */
                $annotationInstance = $attributeReflection->newInstance();
                if (!($annotationInstance->scenario === $scenario &&
                    isset($annotationInstance->publicName, $values)) ||
                    is_null($values[$annotationInstance->publicName])
                ) {
                    continue;
                }


                $type = $reflection->getType()->getName();
                // Check for object might be better.
                if (in_array($type, ['null', 'int', 'bool', 'float', 'string', 'array'])) {
                    $rootNode->addChild(
                        new AnnotationNode(
                            $annotationInstance->publicName,
                            $values[$annotationInstance->publicName],
                            parent: $rootNode
                        )
                    );
                } else {
                    $rootNode->addChild(
                        new AnnotationNode(
                            $annotationInstance->publicName,
                            $this->buildAnnotationTree(
                                new AnnotationNode($annotationInstance->publicName),
                                $values[$annotationInstance->publicName],
                                new $type(),
                                $scenario
                            ),
                            parent: $rootNode
                        )
                    );
                }

            }
        }

        foreach ($classReflection->getAttributes(DecodeToParentProperty::class) as $attributeReflection) {
            /** @var  DecodeToProperty $annotationInstance */
            $annotationInstance = $attributeReflection->newInstance();
            if (!($annotationInstance->scenario === $scenario &&
                    isset($annotationInstance->publicName, $values)) ||
                is_null($values[$annotationInstance->publicName])
            ) {
                continue;
            }

            $type = $reflection->getType()->getName();
            // Check for object might be better.
            if (in_array($type, ['null', 'int', 'bool', 'float', 'string', 'array'])) {
                $rootNode->addChild(
                    new AnnotationNode(
                        $annotationInstance->publicName,
                        $values[$annotationInstance->publicName],
                        parent: $rootNode
                    )
                );
            } else {
                $rootNode->addChild(
                    new AnnotationNode(
                        $annotationInstance->publicName,
                        $this->buildAnnotationTree(
                            new AnnotationNode($annotationInstance->publicName),
                            $values[$annotationInstance->publicName],
                            new $type(),
                            $scenario
                        ),
                        parent: $rootNode
                    )
                );
            }
        }

        foreach ($classReflection->getAttributes(DecodeToParentHomomorphCollection::class) as $attributeReflection) {
            /** @var DecodeToParentHomomorphCollection $attributeInstance */
            $attributeInstance = $attributeReflection->newInstance();

            if (!($attributeInstance->scenario === $scenario &&
                    isset($attributeInstance->publicName, $values)) ||
                is_null($values[$attributeInstance->publicName] ?? null)
            ) {
                continue;
            }

            if (!$attributeInstance->type) {
                $rootNode->addChild(
                    new AnnotationNode(
                        $attributeInstance->publicName,
                        $values[$attributeInstance->publicName],
                        parent: $rootNode
                    )
                );
            } else {
                $result = [];

                foreach($values[$attributeInstance->publicName] as $item) {
                    $result[] = $this->decode(json_encode($item), new $attributeInstance->type(), $scenario);
                }

                $rootNode->addChild(
                    new AnnotationNode(
                        $attributeInstance->publicName,
                        $result,
                        parent: $rootNode
                    )
                );
            }
        }

        foreach ($classReflection->getProperties() as $reflection) {
            foreach ($reflection->getAttributes(DecodeToHomomorphCollection::class) as $attributeReflection) {
                /** @var  DecodeToHomomorphCollection $annotationInstance */

                $annotationInstance = $attributeReflection->newInstance();

                if (!($annotationInstance->scenario === $scenario &&
                        isset($annotationInstance->publicName, $values)) ||
                    is_null($values[$annotationInstance->publicName] ?? null)
                ) {
                    continue;
                }

                if (!$annotationInstance->type) {
                    $rootNode->addChild(
                        new AnnotationNode(
                            $annotationInstance->publicName,
                            $values[$annotationInstance->publicName],
                            parent: $rootNode
                        )
                    );
                } else {
                    $result = [];

                    foreach($values[$annotationInstance->publicName] as $item) {
                        $result[] = $this->decode(json_encode($item), new $annotationInstance->type(), $scenario);
                    }

                    $rootNode->addChild(
                        new AnnotationNode(
                            $annotationInstance->publicName,
                            $result,
                            parent: $rootNode
                        )
                    );
                }
            }
        }

        return $rootNode;
    }
}