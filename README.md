# Notice

This  library is in beta-status; depending on use-cases or problems found some internal implementations may change.
Should it affect persisted data, a method for updating the data-format will be provided.


### Purpose

The purpose of this module is to allow PHP itself to infer the desired structure for the encoded data. It will move the description of
what the output is supposed to be onto the dataobject itself and negates the need to have dedicated encoderlogic.

### Inspiration

This package was inspired by GoLang's JSON-module.

### Extensibility

The logic this package provides is split into two parts communicating over 
a common data-structure.

Any one of those parts is replaceable by a custom implementation
catering to encoding/decoding to/from formats other than the one provided(JSON).

It also allows for custom logic during transformation.

Please see the test provided for some illustration.

### Deployment

The implementation for encoding and decoding can be composed freely.

This needs to be done in the code using it and as long the extensibility requirements
are honoured there shouldn't be any problems.

Otherwise, please file a bug-report.

Using this package you will want to initialize and use the services provided.


### Example

```php
#[DecodeClass(DataObject::class)]
#[EncodeClass(DataObject::class)]
class DataObject
{
    #[EncodeProperty('dataObjectValue')]
    #[DecodeToProperty('dataObjectValue')]
    public int $computedValue;

    public function __construct(int $computedValue = 0)
    {
        $this->computedValue = $computedValue;
    }
}

$instance = new DataObject(44);
```

Upon an instance of this class being put into the services provided
the class will be persisted into

```json
{
  "dataObjectValue": 44
}
```

Without having to implement further logic.

This also holds true for more complex items.
Collections that are not homomorphic (read holding various datatypes, e.g. `[2,'a', new class{}]`)
are not supported.

The different objects are composable can can stack.
The result must not always be a JSON IF the encoder-logic provided creates e.g. XML.
You can also encode one object and decode it to another object.
Additionally, properties known on the part-object can also be encoded
(annotation must take place on the class level though).

An earlier iteration of this (not released) also provided invocations of method-calls.
However, this blurred the line between data and logic and will most likely
(unless for a very good reason) not be supported going forward.

Encoding into other formats is also on the table (e.g. currently XML is up there as a candidate for implementation).
They are subject to time-constraints, though.

The provided attribute-parameter in the annotation is a going to be used there.
For the time being that parameter is provided, but not actively used.
Feel free to test out implementations using that parameter(e.g. for XML) and provide feedback/PRs/bug-reports.
