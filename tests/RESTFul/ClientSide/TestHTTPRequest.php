<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;

class TestHTTPRequest extends TestBase{
	
	function testGetters(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.http-request.children.getters.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestHTTPRequest',"method"=>"testGetters"));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/getters?p1=qv1');
		$request->addHeader("h1", "hv1");
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"method":"GET","protocol":"http","host":"localhost","appPath":"/php-platform/restful","uri":"/test/http-request/getters","queryParam_p1":"qv1","header_h1":"hv1"}', $response->getBody(true));
		
	}
	
	function testJSON(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.http-request.children.json.methods.POST", array("class"=>'PhpPlatform\Tests\RESTFul\Services\TestHTTPRequest',"method"=>"testJSON"));
		
		$client = new Client();
		$jsonContent = '{"name":"raaghu","children":[{"name":"shri"},{"name":"di"}]}';
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/json',array("Content-Type"=>"application/json","Content-Length"=>strlen($jsonContent)),$jsonContent);
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($jsonContent, $response->getBody(true));
	}
	
}