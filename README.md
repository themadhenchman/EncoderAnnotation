# Purpose

The purpose of this module is to allow PHP itself to infer the desired structure for the encoded data. It will move the description of
what the output is supposed to be onto the dataobject itself and negates the need to have dedicated encoderlogic.

# Inspiration

This package was inspired by GoLangs JSON-module.

# Extensibility

The logic this package provides is split into two parts communicating over 
a common data-structure.

Any one of those parts is replaceable by a custom implementation
catering to encoding/decoding to/from formats other than the one provided(JSON).

It also allows for custom logic during transformation.

Please see the test provided for some illustration.

# Deployment

The implementation for encoding and decoding can be composed freely.

This needs to be done in the code using it and as long the extensibility requirements
are honoured there shouldn't be any problems.

Otherwise, please file a bug-report.

Using this package you will want to initialize and use the services provided.
