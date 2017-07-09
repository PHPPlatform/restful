<?php

namespace PhpPlatform\Tests\RESTFul\Services;


use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\HTTPRequest;

/**
 * @Path "/test/recaptcha"
 *
 */
class TestReCaptcha implements RESTService{
	
	/**
	 * @Path "/test"
	 * @POST
	 * @ReCaptcha
	 */
	function testRecaptcha(){
		return new HTTPResponse();
	}
	
	/**
	 * @Path "/test-norecaptcha-service"
	 * @POST
	 */
	function testNoRecaptcha(){
		return new HTTPResponse();
	}
	
	/**
	 * @Path "/mock-test-site"
	 * @POST
	 * @Consumes array
	 * @param HTTPRequest $request
	 */
	function mockTestSite($request){
		$data = $request->getData();
		if(array_key_exists("response", $data) && $data["response"] == "qbnhdytteoanbkjyD4f7oesnhmdgbtDmjhaldhCRttyugr3m268nkjduyrj"){
			return new HTTPResponse(200,OK,array("success"=>true));
		}else{
			return new HTTPResponse(200,OK,array("success"=>false));
		}
	}
	
}