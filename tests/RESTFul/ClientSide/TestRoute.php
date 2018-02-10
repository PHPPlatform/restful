<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use PhpPlatform\Tests\RESTFul\TestBase;
use Guzzle\Http\Exception\ServerErrorResponseException;
use Guzzle\Http\Exception\ClientErrorResponseException;

class TestRoute extends TestBase {
	
	function testEmpty(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/empty');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody(true));
	}
	
	function testSimple(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("Simple output", $response->getBody(true));
	}
	
	function testGetJSON(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/json');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"this":["is","JSON","from","array"]}', $response->getBody(true));
	}
	
	function testNonService(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.non-service.children.empty.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestRouteNonService',"method"=>"emptyTest"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/non-service/empty');
		
		$isException = false;
		try{
		    $client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('PhpPlatform\Tests\RESTFul\Services\TestRouteNonService does not implement PhpPlatform\RESTFul\RESTService');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testWrongResponceFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/wrong-response');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('Service method does not return instance of PhpPlatform\RESTFul\HTTPResponse');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testExceptionFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/exception');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('Testing Exception in Service Method');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testPlatformExceptionFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/exception/platform-exception');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('Testing Uncaught Bad Input Exception in Service');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testInternalServerErrorFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/exception/internal-server-error');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals("Internal Server Error", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('Testing Uncaught internalServerError in Service');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testDataNotFoundExceptionFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/exception/data-not-found-exception');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ClientErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(404, $response->getStatusCode());
			$this->assertEquals("Not Found", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('[H][404][::1][/test/route/exception/data-not-found-exception] Not Found');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testNoAccessExceptionFromServiceMethod(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/exception/no-access-exception');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ClientErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(401, $response->getStatusCode());
			$this->assertEquals("Unauthorized", $response->getReasonPhrase());
			$this->assertEquals("", $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('[H][401][::1][/test/route/exception/no-access-exception] Unauthorized');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testPathParams(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/myParam1/path/myParam2');
		
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('OK', $response->getReasonPhrase());
		
		$this->assertEquals('{"param1":"myParam1","param2":"myParam2"}', $response->getBody(true));
	}
	
	function testHttpMethods(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/method');
		$response = $client->send($request);
		$this->assertEquals('GET', $response->getBody(true));
		
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/route/method');
		$response = $client->send($request);
		$this->assertEquals('POST', $response->getBody(true));
		
		$request = $client->put(APP_DOMAIN.'/'.APP_PATH.'/test/route/method');
		$response = $client->send($request);
		$this->assertEquals('PUT', $response->getBody(true));
		
		$request = $client->patch(APP_DOMAIN.'/'.APP_PATH.'/test/route/method');
		$response = $client->send($request);
		$this->assertEquals('PATCH', $response->getBody(true));
		
		$request = $client->delete(APP_DOMAIN.'/'.APP_PATH.'/test/route/method');
		$response = $client->send($request);
		$this->assertEquals('DELETE', $response->getBody(true));
		
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/method/default');
		$response = $client->send($request);
		$this->assertEquals('Default', $response->getBody(true));
		
	}
	
	function testResourceNotFound(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/myParam1/path/myParam2/non-existant-resource');
		
		$isException = false;
		try{
		    $client->send($request);
		}catch (ClientErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(404, $response->getStatusCode());
			$this->assertEquals('Not Found', $response->getReasonPhrase());
			$this->assertEquals('Resource at test/route/myParam1/path/myParam2/non-existant-resource Not Found', $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('[H][404][::1][/test/route/myParam1/path/myParam2/non-existant-resource] Not Found');
			
			$isException = true;
		}
		$this->assertTrue($isException);
		
		
		// tests for methods not configured in resource path
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.*.children.path.children.*.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestRoute',"method"=>"pathParams"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ClientErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(404, $response->getStatusCode());
			$this->assertEquals('Not Found', $response->getReasonPhrase());
			$this->assertEquals('Resource at test Not Found', $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('[H][404][::1][/test] Not Found');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testMethodNotAllowed(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.json.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestRoute',"method"=>"getJSON"));
		
		$client = new Client();
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/route/json');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ClientErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(405, $response->getStatusCode());
			$this->assertEquals('Method Not Allowed', $response->getReasonPhrase());
			$this->assertEquals('POST method is not Allowed', $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('[H][405][::1][/test/route/json] Method Not Allowed');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testClassOrMethodNotExists(){
		// class not exists
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.class-not-exists.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestRouteNotExists',"method"=>"someMethod"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/class-not-exists');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals('Internal Server Error', $response->getReasonPhrase());
			$this->assertEquals('', $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('class and/or method does not exists for route at test/route/class-not-exists');
			
			$isException = true;
		}
		$this->assertTrue($isException);
		
		
		// method not exists
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.method-not-exists.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestRoute',"method"=>"methodNotExists"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/method-not-exists');
		
		$isException = false;
		try{
			$client->send($request);
		}catch (ServerErrorResponseException $e){
			$response = $e->getResponse();
			$this->assertEquals(500, $response->getStatusCode());
			$this->assertEquals('Internal Server Error', $response->getReasonPhrase());
			$this->assertEquals('', $response->getBody(true));
			
			// validate log
			$this->assertContainsAndClearLog('class and/or method does not exists for route at test/route/method-not-exists');
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}

}