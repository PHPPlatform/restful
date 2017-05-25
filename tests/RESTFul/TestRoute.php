<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use PhpPlatform\Tests\RestFul\TestBase;

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
	

}