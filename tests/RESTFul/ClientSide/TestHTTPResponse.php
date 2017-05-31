<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use Guzzle\Http\Message\Header;

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

}