<?php

namespace EncoderAnnotation\Attributes;

use EncoderAnnotation\Constants;

#[\Attribute(\Attribute::IS_REPEATABLE|\Attribute::TARGET_CLASS)]
class EncodeFromParentHomomorphCollection
{
    public function __construct(
        public readonly string $publicName,
        public readonly string $attributeName,
        public readonly ?string $type = null,
        public readonly string $scenario = Constants::DEFAULT_SCENARIO,
        public readonly array $attributes = []
    )
    {
    }
}