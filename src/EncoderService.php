<?php

namespace EncoderAnnotation;

class EncoderService
{
    public function __construct(
        private readonly Encoder $encoder,
        private readonly EncoderTransformer $transformer
    )
    {
    }

    public function encode(object $data, string $scenario = Constants::DEFAULT_SCENARIO): string
    {
        return $this->encoder->encode(
            $this->transformer->marshall($data, $scenario)
        );
    }
}