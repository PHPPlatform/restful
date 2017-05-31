<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\Tests\RESTFul\Services\Models\Person;
use PhpPlatform\Tests\RESTFul\Services\Models\Employee;

/**
 * @Path /test/http-response
 *
 */
class TestHTTPResponse implements RESTService{
	
	/**
	 * @Path /header
	 * @GET
	 */
	function testHeaders(){
		$response = new HTTPResponse();
		$response->setHeader("Test-Header", "Test-Header-Value");
		$response->setHeader("Test-Header-int", 10);
		
		return $response;
	}
	
	/**
	 * @Path /buffer
	 * @GET
	 */
	function testBufferClearing(){
		$content = array("k1"=>"v1");
		
		echo "This message will be written to buffer , and buffer is cleared before flushing the response, hence this mesage will not appear in response output";
		$response = new HTTPResponse(200,'OK',$content);
		return $response;
	}
	
	/**
	 * @Path /unknown-type
	 * @GET
	 */
	function testUnknownType(){
		$f = fopen(__FILE__,'r');
		fclose($f);
		echo "Even the buffer is cleared in Error";
		return new HTTPResponse(200,'OK',$f);
	}
	
	/**
	 * @Path /person
	 * @GET
	 */
	function getPerson(){
		$person = new Person();
		$person->setFirstName("Raghavendra");
		$person->setLastName("Raju");
		$person->setAge(27);
		
		return new HTTPResponse(200,'OK',$person);
	}
	
	/**
	 * @Path /employee
	 * @GET
	 */
	function getEmployee(){
		$employee = new Employee();
		$employee->setFirstName("Raghavendra");
		$employee->setLastName("Raju");
		$employee->setAge(27);
		
		$employee->setEmpId(100);
		
		return new HTTPResponse(200,'OK',$employee);
	}
}