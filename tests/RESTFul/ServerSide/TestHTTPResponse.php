<?php

namespace PhpPlatform\Tests\RESTFul\ServerSide;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;
use PhpPlatform\Tests\RESTFul\TestBase;

class TestHTTPResponse extends TestBase{
	
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
		
		$response->setCode(404);
		$this->assertHTTPResponseProperty('code', 404, $response);
	}
	
	function testSetMessage(){
		// bad input
		$response = new HTTPResponse();
		$isException = false;
		try{
			$response->setMessage(array());
		}catch (BadInputException $e){
			$this->assertEquals('Invalid Message', $e->getMessage());
			$this->clearErrorLog();
			$isException = true;
		}
		$this->assertTrue($isException);
		
		$response->setMessage('Good');
		$this->assertHTTPResponseProperty('message', 'Good', $response);
	}
	
	function testSetHeader(){
		// bad input
		$response = new HTTPResponse();
		$isException = false;
		try{
			$response->setHeader(array('HName'), 'HValue');
		}catch (BadInputException $e){
			$this->assertEquals('Invalid Name or Value', $e->getMessage());
			$this->clearErrorLog();
			$isException = true;
		}
		$this->assertTrue($isException);
		
		$isException = false;
		try{
			$response->setHeader('HName', array('HValue'));
		}catch (BadInputException $e){
			$this->assertEquals('Invalid Name or Value', $e->getMessage());
			$this->clearErrorLog();
			$isException = true;
		}
		$this->assertTrue($isException);
		
		$response->setHeader('HName', 'HValue');
		$this->assertHTTPResponseProperty('headers', array('HName'=>'HValue'), $response);

		$response->setHeader('Content-Length', 200);
		$response->setHeader('Time-Occurred', 1.5);
		$response->setHeader('Content-Type', 'application/json');
		$response->setHeader('Keep-Connection', true);
		
		$this->assertHTTPResponseProperty('headers', 
				array(
						'HName'=>'HValue',
						'Content-Length'=>200,
						'Time-Occurred'=> 1.5,
						'Content-Type'=> 'application/json',
						'Keep-Connection' => true
				), $response);
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
	
	function testGetAcceptpreferenceTable(){
		$response = new HTTPResponse();
		$reflectionMethod = new \ReflectionMethod(get_class($response),'getAcceptPreferenceTable');
		$reflectionMethod->setAccessible(true);
		
		$preferenceTable = $reflectionMethod->invoke($response,'application/json ;q=0.8,text/json;');
		$this->assertEquals(array(
				'1'=>array(
						'text/json;'
				),
				'0.8'=>array(
						'application/json'
				)
		), $preferenceTable);
		
		$preferenceTable = $reflectionMethod->invoke($response,'{"type":"application/json;q=1.0"}');
		
		$this->assertEquals(array(
				'1'=>array(
						'{"type":"application/json;q=1.0"}'
				)
		), $preferenceTable);
		
		$preferenceTable = $reflectionMethod->invoke($response,'*/*;q=0.8,text/plain;q=0.8,text/html;q=0.8,application/json');
		
		$this->assertEquals(array(
				'1' => array(
						"application/json"
				),
				'0.8' => array(
				        "text/plain",
						"text/html",
						"*/*"
				)		
			), $preferenceTable);
		
	}
	
	
	
}