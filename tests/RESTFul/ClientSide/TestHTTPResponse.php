<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Header;
use Guzzle\Http\Exception\ServerErrorResponseException;

class TestHTTPResponse extends TestBase {

	function testHeaders(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.http-response.children.header.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestHTTPResponse',"method"=>"testHeaders"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/header');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody(true));
		
		$this->assertEquals("Test-Header-Value", $response->getHeader("Test-Header"));
		$this->assertEquals("10", $response->getHeader("Test-Header-int"));
	}
	
	function testBuffer(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.http-response.children.buffer.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestHTTPResponse',"method"=>"testBufferClearing"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-response/buffer');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"k1":"v1"}', $response->getBody(true));
	}
	
	function testUnknownType(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.http-response.children.unknown-type.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestHTTPResponse',"method"=>"testUnknownType"));
		
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
	

}