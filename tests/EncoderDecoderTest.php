<?php

use EncoderAnnotation\Attributes\DecodeClass;
use EncoderAnnotation\Attributes\DecodeToHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentHomomorphCollection;
use EncoderAnnotation\Attributes\DecodeToParentProperty;
use EncoderAnnotation\Attributes\DecodeToProperty;
use EncoderAnnotation\Attributes\EncodeClass;
use EncoderAnnotation\Attributes\EncodeFromHomomorphCollection;
use EncoderAnnotation\Attributes\EncodeFromParentHomomorphCollection;
use EncoderAnnotation\Attributes\EncodeParentProperty;
use EncoderAnnotation\Attributes\EncodeProperty;
use EncoderAnnotation\Decoder;
use EncoderAnnotation\DecoderService;
use EncoderAnnotation\DecoderTransformer;
use EncoderAnnotation\Encoder;
use EncoderAnnotation\EncoderService;
use EncoderAnnotation\EncoderTransformer;
use PHPUnit\Framework\TestCase;


class ParentDataObject {
    public int $someCounter = 0;

    protected array $simpleCollection = [];

    /**
     * @return array
     */
    public function getSimpleCollection(): array
    {
        return $this->simpleCollection;
    }

    /**
     * @param array $simpleCollection
     */
    public function setSimpleCollection(array $simpleCollection): void
    {
        $this->simpleCollection = $simpleCollection;
    }

    /**
     * @return array
     */
    public function getComplexCollection(): array
    {
        return $this->complexCollection;
    }

    /**
     * @param array $complexCollection
     */
    public function setComplexCollection(array $complexCollection): void
    {
        $this->complexCollection = $complexCollection;
    }

    protected array $complexCollection = [];
}

#[DecodeClass(DataObjectWithMethodCalls::class)]
#[EncodeClass(DataObjectWithMethodCalls::class)]
class DataObjectWithMethodCalls
{
    #[EncodeProperty('dataObjectValue')]
    #[DecodeToProperty('dataObjectValue')]
    public int $computedValue;

    public function __construct(int $computedValue = 0)
    {
        $this->computedValue = $computedValue;
    }
}

#[DecodeClass(DataObjectWithData::class)]
#[EncodeClass(DataObjectWithData::class)]
#[EncodeParentProperty('parentProperties', 'someCounter')]
#[DecodeToParentProperty('parentProperties', 'someCounter')]
#[EncodeFromParentHomomorphCollection('parentSimpleCollection', 'simpleCollection')]
#[DecodeToParentHomomorphCollection('parentSimpleCollection', 'simpleCollection')]
#[EncodeFromParentHomomorphCollection('parentComplexCollection', 'complexCollection', DataObjectWithMethodCalls::class)]
#[DecodeToParentHomomorphCollection('parentComplexCollection', 'complexCollection', DataObjectWithMethodCalls::class)]
class DataObjectWithData extends ParentDataObject
{
    #[EncodeProperty('stringValue')]
    #[DecodeToProperty('stringValue')]
    public string $foobar = 'Hello';
    #[EncodeProperty('floatValue')]
    #[DecodeToProperty('floatValue')]
    private float $floating;

    #[EncodeProperty('NestedSelf')]
    #[DecodeToProperty('NestedSelf')]
    public ?DataObjectWithData $nested;

    #[EncodeFromHomomorphCollection('simple___Property')]
    #[DecodeToHomomorphCollection('simple___Property')]
    public array $nestedSimpleValues = [];


    #[EncodeFromHomomorphCollection('Complex---Property', DataObjectWithMethodCalls::class)]
    #[DecodeToHomomorphCollection('Complex---Property', DataObjectWithMethodCalls::class)]
    public array $nestedComplexValues = [];

    public float $computedValue = 0;

    public function __construct(bool $isNested = false)
    {
        if (!$isNested) {
            $this->nested = (new self(true))->setFloat(1.11);
        } else {
            $this->nested = null;
        }
    }

    public function getFloat(): float
    {
        return $this->floating;
    }

    public function setFloat(float $value): self
    {
        $this->floating = $value;

        return $this;
    }

    #[DecodeToMethodCall('computedValue', [0.5])]
    public function setComputedValueWithFactor(float $value, float $factor): self
    {
        $this->computedValue = $value * $factor;

        return $this;
    }

}

class EncoderDecoderTest extends TestCase
{
    private DecoderService $decoderServiceUnderTest;
    private EncoderService $encoderServiceUnderTest;
    protected function setUp(): void
    {
        $this->decoderServiceUnderTest = new DecoderService(
            new Decoder(),
            new DecoderTransformer()
        );

        $this->encoderServiceUnderTest = new EncoderService(
            new Encoder(),
            new EncoderTransformer()
        );
    }


    public function testEncoding()
    {
        $source = new DataObjectWithData();
        $expectedFloat = 222.33;
        $expectedString =  'goodbye!';

        $source->setFloat($expectedFloat);
        $source->foobar = $expectedString;
        $source->nestedSimpleValues = [1,2,3,4];
        $source->computedValue = 33;
        $source->someCounter = 123456789;

        $source->setSimpleCollection([1,2,3,4,55,66,77,88,99]);

        $source->nestedComplexValues = [new DataObjectWithMethodCalls(33)];
        $source->setComplexCollection([new DataObjectWithMethodCalls(99)]);
        $serializedData = $this->encoderServiceUnderTest->encode($source);

        /** @var DataObjectWithData $sink */
        $sink = $this->decoderServiceUnderTest->decode($serializedData, new DataObjectWithData(true));
        $this->assertEquals($source->foobar,$sink->foobar);
        $this->assertEquals($expectedString,$sink->foobar);
        $this->assertEquals($source->getFloat(), $sink->getFloat());
        $this->assertEquals($source->nested->foobar, $sink->nested->foobar);
        $this->assertEquals($source->nested->getFloat(), $sink->nested->getFloat());
        $this->assertEquals([1,2,3,4], $sink->nestedSimpleValues);
        $this->assertEquals($source->someCounter, $sink->someCounter);
        $this->assertEquals($source->nestedComplexValues[0]->computedValue, $sink->nestedComplexValues[0]->computedValue);
        $this->assertEquals($source->getSimpleCollection(), $sink->getSimpleCollection());
        $this->assertEquals($source->getComplexCollection(), $sink->getComplexCollection());
    }
}
