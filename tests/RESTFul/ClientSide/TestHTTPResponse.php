<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Header;
use Guzzle\Http\Exception\ServerErrorResponseException;

class TestHTTPResponse extends TestBase {

	function testHeaders(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/header');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody(true));
		
		$this->assertEquals("Test-Header-Value", $response->getHeader("Test-Header"));
		$this->assertEquals("10", $response->getHeader("Test-Header-int"));
	}
	
	function testBuffer(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/buffer');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"k1":"v1"}', $response->getBody(true));
	}
	
	function testUnknownType(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/unknown-type');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('Unkown type of data in HTTPResponse');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testGetObject(){
		MockSettings::setSettings("php-platform/restful", "serializers", array('PhpPlatform\Tests\RESTFul\Services\Models\Person'=>array('application/json'=>'PhpPlatform\Tests\RESTFul\Services\Models\PersonSerializer')));
		MockSettings::setSettings("php-platform/restful", "serializers", array('PhpPlatform\Tests\RESTFul\Services\Models\EmployeeInterface'=>array('application/xml'=>'PhpPlatform\Tests\RESTFul\Services\Models\EmployeeInterfaceSerializer')));
		
		// only available serializer
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/person');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"firstName":"Raghavendra","lastName":"Raju","age":27}', $response->getBody(true));
		
		// 2 serializers available , preference given to accept
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/employee');
		$request->setHeader("Accept", "application/json,application/xml;q=0.8,*/*;q=0.5");
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"empId":100,"firstName":"Raghavendra","lastName":"Raju","age":27}', $response->getBody(true));
		
		// 2 serializers available , preference is given to class hirarchy of the data
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/employee');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('<?xml version="1.0"?>'."\n".
                            '<employee empId="100" firstName="Raghavendra" lastName="Raju" age="27"/>'."\n", 
				$response->getBody(true));
		
	}

}