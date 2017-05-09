<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\RESTFul\HTTPResponse;

class TestHTTPResponse extends \PHPUnit_Framework_TestCase {
	
	function testGetAcceptPreferenceTable(){
		$httpResponse = new HTTPResponse();
		$reflection = new \ReflectionClass($httpResponse);
		$method = $reflection->getMethod('getAcceptPreferenceTable');
		$method->setAccessible(true);
		$acceptPreferences = $method->invoke($httpResponse,"application/xml,*/*, image/* ; q=0.6, image/jpeg, image/png, text/*");
		
		$this->assertEquals(array(
				"1"=>array(
						"application/xml",
						"image/jpeg",
						"image/png",
						"text/*",
						"*/*"
				),
				"0.6" => array(
						"image/*"
				)
		), $acceptPreferences);
	}
}