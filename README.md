# PHP Platforms RESTFul Apis
This packages provides platform for writing RESTFul APIs in PHP 

[![build status](https://gitlab.com/php-platform/restful/badges/master/build.svg)](https://gitlab.com/php-platform/restful/commits/master) [![coverage report](https://gitlab.com/php-platform/restful/badges/master/coverage.svg)](https://gitlab.com/php-platform/restful/commits/master)


## Introduction
RESTful APIs are modern way of designing an web application , this approach provides the UI Dresigners complete freedom of their design and improvement 

RESTFul APIs also give a way to secure the web resources in more elegant way

This package provides a platform for creating such RESTFul APIs in PHP

## Features
* Annotated APIs 
* can be used with any frameworks 

## Usage

* copy `resources/.htaccess` and `resources/index.php` to the root of the composer package
* enable apache `rewrite` module 
* add `AllowOveride All` to composer root directory in apache's configuration
* Service Class must implement ``PhpPlatform\RESTFul\RESTService``
* annotate service classes and methods with ``@Path`` to specify the path to which the the perticular class::method provides the service
* configure routes as mentioned in the Configuration section below 
* All service methods must return ``PhpPlatform\RESTFul\HTTPResponse``

## Annotations

#### @Path
Can be applied on service class or method , this denotes url path to reach that service-method

#### @GET @POST @PUT @HEAD @DELETE
Can be applied only on service method , denotes what http verb this method is capable of serving.
A service method can have more than on of these Annotations

#### @Consumes
Cab be applied only on service method, specifies the data type of the request body

deserializers use this annnotation to deserialize the http request body into a php data. refer deserilizers section to configure multiple deserializers

## Configuration

This section explains the configuration for this package which can be configured using config.xml

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
 * @Consumes array
 * @Path my-service
 */
function myService(){}

```

#### routes
routes is the static map of url pattern to service class and methods.

routes can be updated manually or generated based on the annotations by running
```
$ ./vendor/bin/build-restful
```

routes is organized as a tree , where each node contains the class and method name of the service available for that url path 

web services for these url patterns will be configured as follows

 * ``GET  /user/all    :- MyService\User::getAllUsers``
 * ``POST /user/create :- MyService\User::createUser``
 * ``GET  /user/{id}   :- MyService\User::getUser``

``` JSON
"routes" : {
    "children" : {
        "user" : {
            "children" : {
                "all" : {
                    "methods" : {
                        "GET" : {
                            "class" : "MyService\\User",
                            "method" : "getAllUsers"
                        }
                    }
                },
                "create" : {
                    "methods" : {
                        "POST" : {
                            "class" : "MyService\\User",
                            "method" : "createUser"
                        }
                    }
                },
                "*" : {
                    "methods" : {
                        "GET" : {
                            "class" : "MyService\\User",
                            "method" : "getUser"
                        }
                    }
                }
            }
        }
    }
}
```

``NOTE :``
 
``The parameters in the path are represented as * in the config , in the above example {id} is represented as *``
 
``Name of the params does not map to the name of the service method arguments , but they map to the position``