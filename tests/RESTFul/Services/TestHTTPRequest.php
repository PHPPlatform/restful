<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPRequest;

/**
 * @Path /test/http-request
 *
 */
class TestHTTPRequest implements RESTService{
	
	/**
	 * @Path /getters
	 * @GET
	 */
	function testGetters(HTTPRequest $request){
		$content = array(
				"method"=>$request->getMethod(),
				"protocol"=>$request->getProtocol(),
				"host"=>$request->getHost(),
				"appPath"=>$request->getAppPath(),
				"uri"=>$request->getUri(),
				"queryParam_p1"=>$request->getQueryParam("p1"),
				"header_h1"=>$request->getHeader("h1")
	    );
		
		$response = new HTTPResponse();
		$response->setData($content);
		return $response;
	}
	
	/**
	 * @Path /json
	 * @POST
	 * @Consumes array
	 */
	function testJSON(HTTPRequest $request){
		$inputArray = $request->getData();
		return new HTTPResponse(200,'OK',$inputArray);
	}
}