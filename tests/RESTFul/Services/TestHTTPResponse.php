<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\RESTService;

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
}