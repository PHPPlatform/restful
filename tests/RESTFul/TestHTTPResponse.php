<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;

class TestHTTPResponse extends TestServerSide{
	
	function testConstruct(){
		$isException = false;
		try{
			$response = new HTTPResponse();
		}catch (\Exception $e){
			$isException = true;
		}
		$this->assertTrue(!$isException);
		
		$this->assertHTTPResponseProperty('code',200, $response);
		$this->assertHTTPResponseProperty('message','OK', $response);
		$this->assertHTTPResponseProperty('headers',array(), $response);
		$this->assertHTTPResponseProperty('data',null, $response);
		
		
		$isException = false;
		try{
			$response = new HTTPResponse(404,'Resource Not Found','Please try again later or contact administrator');
		}catch (\Exception $e){
			$isException = true;
		}
		$this->assertTrue(!$isException);
		
		$this->assertHTTPResponseProperty('code',404, $response);
		$this->assertHTTPResponseProperty('message','Resource Not Found', $response);
		$this->assertHTTPResponseProperty('headers',array(), $response);
		$this->assertHTTPResponseProperty('data','Please try again later or contact administrator', $response);
		
	}
	
	function testSetCode(){
		// bad input
		$response = new HTTPResponse();
		$isException = false;
		try{
			$response->setCode(99);
		}catch (BadInputException $e){
			$this->assertEquals('Invalid Code', $e->getMessage());
			$this->clearErrorLog();
			$isException = true;
		}
		$this->assertTrue($isException);
	
	}
	
	
	
	/**
	 * @param string $propertyName
	 * @param mixed $expected
	 * @param HTTPResponse $response
	 */
	private function assertHTTPResponseProperty($propertyName,$expected,$response){
		$reflectionProperty = new \ReflectionProperty(get_class($response), $propertyName);
		$reflectionProperty->setAccessible(true);
		$this->assertEquals($expected, $reflectionProperty->getValue($response));
	}
	
}