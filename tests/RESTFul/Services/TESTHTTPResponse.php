<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\RESTService;

/**
 * @Path /test/http-response
 *
 */
class TESTHTTPResponse implements RESTService{
	
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
	
}