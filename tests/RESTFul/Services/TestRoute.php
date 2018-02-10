<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;
use PhpPlatform\RESTFul\HTTPRequest;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;
use PhpPlatform\Errors\Exceptions\Persistence\DataNotFoundException;
use PhpPlatform\Errors\Exceptions\Persistence\NoAccessException;

/**
 * @Path "/test/route"
 *
 */
class TestRoute implements RESTService{
	
	/**
	 * @Path "/empty"
	 * @GET
	 */
	function emptyTest(){
		return new HTTPResponse();
	}
	
	/**
	 * @Path "/simple"
	 * @GET
	 */
	function simpleTest(){
		return new HTTPResponse(200,'OK',"Simple output");
	}
	
	/**
	 * @Path "/json"
	 * @GET
	 */
	function getJSON(){
		return new HTTPResponse(200,'OK',array("this"=>array("is","JSON","from","array")));
	}

	/**
	 * @Path "/wrong-response"
	 * @GET
	 */
	function wrongResponse(){
		return array("this"=>array("is","JSON","from","array"));
	}
	
	/**
	 * @Path "/exception/platform-exception"
	 * @GET
	 */
	function platformException(){
		throw new BadInputException("Testing Uncaught Bad Input Exception in Service");
	}
	
	/**
	 * @Path "/exception/internal-server-error"
	 * @GET
	 */
	function internalServerError(){
		throw new InternalServerError("Testing Uncaught internalServerError in Service");
	}
	
	/**
	 * @Path "/exception/data-not-found-exception"
	 * @GET
	 */
	function dataNotFoundException(){
		throw new DataNotFoundException();
	}
	
	
	/**
	 * @Path "/exception/no-access-exception"
	 * @GET
	 */
	function noAccessException(){
		throw new NoAccessException();
	}
	
	
	/**
	 * @Path "/exception"
	 * @GET
	 */
	function exception(){
		throw new \Exception("Testing Exception in Service Method");
	}
	
	/**
	 * @Path "/{param1}/path/{param2}"
	 * @GET
	 */
	function pathParams($request,$param1,$param2){
		return new HTTPResponse(200,'OK',array("param1"=>$param1,"param2"=>$param2));
	}
	
	/**
	 * @Path "/method"
	 * @GET
	 * 
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodGET($request){
		return new HTTPResponse(200,'OK',"GET");
	}
	
	/**
	 * @Path "/method"
	 * @POST
	 *
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodPOST($request){
		return new HTTPResponse(200,'OK',"POST");
	}
	
	/**
	 * @Path "/method"
	 * @PUT
	 *
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodPUT($request){
		return new HTTPResponse(200,'OK',"PUT");
	}
	
	/**
	 * @Path "/method"
	 * @PATCH
	 *
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodPATCH($request){
		return new HTTPResponse(200,'OK',"PATCH");
	}
	
	/**
	 * @Path "/method"
	 * @DELETE
	 *
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodDELETE($request){
		return new HTTPResponse(200,'OK',"DELETE");
	}
	
	/**
	 * @Path "/method/default"
	 *
	 * @param HTTPRequest $request
	 */
	function testHTTPMethodDefault($request){
		return new HTTPResponse(200,'OK',"Default");
	}

}