<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use PhpPlatform\Tests\RestFul\TestBase;
use Guzzle\Http\Exception\ServerErrorResponseException;
use PhpPlatform\Config\Settings;

class TestRoute extends TestBase {
	
	function testEmpty(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.empty.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"emptyTest"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/empty');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody(true));
	}
	
	function testSimple(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.simple.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"simpleTest"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("Simple output", $response->getBody(true));
	}
	
	function testGetJSON(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.json.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"getJSON"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/json');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"this":["is","JSON","from","array"]}', $response->getBody(true));
	}
	
	function testNonService(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.non-service.children.empty.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRouteNonService',"method"=>"emptyTest"));
		
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
			$logFile = Settings::getSettings('php-platform/errors','traces.Http');
			$log = "";
			if(file_exists($logFile)){
				$log = file_get_contents($logFile);
			}
			$this->assertContains('PhpPlatform\Tests\RESTFul\Server\TestRouteNonService does not implement PhpPlatform\RESTFul\RESTService', $log);
			unlink($logFile);
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testWrongResponceFromServiceMethod(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.wrong-response.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"wrongResponse"));
		
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
			$logFile = Settings::getSettings('php-platform/errors','traces.Http');
			$log = "";
			if(file_exists($logFile)){
				$log = file_get_contents($logFile);
			}
			$this->assertContains('Service method does not return instance of PhpPlatform\RESTFul\HTTPResponse', $log);
			unlink($logFile);
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}
	
	function testExceptionFromServiceMethod(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.exception.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"exception"));
		
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
			$logFile = Settings::getSettings('php-platform/errors','traces.Http');
			$log = "";
			if(file_exists($logFile)){
				$log = file_get_contents($logFile);
			}
			$this->assertContains('Testing Uncaught Bad Input Exception in Service', $log);
			unlink($logFile);
			
			$isException = true;
		}
		$this->assertTrue($isException);
	}

}