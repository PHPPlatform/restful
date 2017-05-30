<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;

/**
 * @Path /test/route
 *
 */
class TestRoute implements RESTService{
	
	/**
	 * @Path /empty
	 * @GET
	 */
	function emptyTest(){
		return new HTTPResponse();
	}
	
	/**
	 * @Path /simple
	 * @GET
	 */
	function simpleTest(){
		return new HTTPResponse(200,'OK',"Simple output");
	}
	
	/**
	 * @Path /json
	 * @GET
	 */
	function getJSON(){
		return new HTTPResponse(200,'OK',array("this"=>array("is","JSON","from","array")));
	}

	/**
	 * @Path /wrong-response
	 * @GET
	 */
	function wrongResponse(){
		return array("this"=>array("is","JSON","from","array"));
	}
	
	/**
	 * @Path /exception
	 * @GET
	 */
	function exception(){
		throw new BadInputException("Testing Uncaught Bad Input Exception in Service");
	}
	
	/**
	 * @Path /{param1}/path/{param2}
	 * @GET
	 */
	function pathParams($request,$param1,$param2){
		return new HTTPResponse(200,'OK',array("param1"=>$param1,"param2"=>$param2));
	}
	
}