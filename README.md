# PHP Platforms RESTFul Apis
This packages provides platform for writing RESTFul APIs in PHP 

[![Build Status](https://travis-ci.org/PHPPlatform/restful.svg?branch=master)](https://travis-ci.org/PHPPlatform/restful)

## Introduction
RESTful APIs are modern way of designing an web application , this approach provides the UI Dresigners complete freedom of their design and improvement 

RESTFul APIs also give a way to secure the web resources in more elegant way

This package provides a platform for creating such RESTFul APIs in PHP

## Features
* Annotated APIs 
* can be used with any frameworks 

### Example

For More Usage please see the included tests 

### Configuration

This section explains the configuration for this package which can be configured using config.xml

#### webroot
This is the path where application is deployed relative to htdocs 
``` 
if application is deployed at demo , 
the url looks like http://localhost/demo/myservice
and 'demo' is the webroot
```

#### serializers
Differrent type of data needs to be serialized in differrent formats based on the Accept Header in the request

So differrent serializing implementations can be configured as follows
``` JSON
"serializers":{
        "array":{
            "application/json":"PhpPlatform\\RESTFul\\Serialization\\JsonToArraySerialization"
        },
        "SimpleXMLElement":{
            "application/xml":"PhpPlatform\\RESTFul\\Serialization\\XmlToSimpleXMLElementSerialization"
        }
    },
```
Serializer class must implement ``PhpPlatform\RESTFul\Serialization\Serialize`` interface

#### deserializers
The data in the http request must be converted into a php represenation 
So differrent deserializing implementations can be configured as follows
``` JSON
"deserializers":{
        "application/json":{
            "array":"PhpPlatform\\RESTFul\\Serialization\\JsonToArraySerialization"
        },
        "application/xml":{
            "SimpleXMLElement":"PhpPlatform\\RESTFul\\Serialization\\XmlToSimpleXMLElementSerialization"
        }
    },
```
Deserializer class must implement ``PhpPlatform\RESTFul\Serialization\Deserialize`` interface

The PHP type to which the data should be converted needs to be specified at an annotation for the service as follows
``` PHP

/**
 * @dataType array
 * @Path my-service
 */
function myService(){}

```

#### routes
routes is the static map of url pattern to service class and methods
can be updated manually or generated from [php-platform/restful-build-routes](https://github.com/PHPPlatform/restful-build-routes) package