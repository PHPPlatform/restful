<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;

class TestRoute extends \PHPUnit_Framework_TestCase {
	
	function testSimple(){
		MockSettings::setSettings("php-platform/restful", "routes.children.test.children.route.children.simple.methods.GET", array("class"=>'PhpPlatform\Tests\RESTFul\Server\TestRoute',"method"=>"simpleTest"));
		MockSettings::setSettings("php-platform/restful", "webroot", "php-platform/restful");
		$client = new Client();
		$request = $client->get(APP_HOME_URL.'/test/route/simple');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
	}

}