<?php

namespace EncoderAnnotation;

class DecoderService
{
    public function __construct(
        private readonly Decoder $decoder,
        private readonly DecoderTransformer $transformer
    )
    {
    }

    public function decode($data, object $dataObject, string $scenario = Constants::DEFAULT_SCENARIO): object
    {
        return $this->transformer->unMarshall(
            $this->decoder->decode($data, $dataObject, $scenario),
            $dataObject,
            $scenario
        );
    }
}