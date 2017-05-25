<?php

namespace PhpPlatform\Tests\RESTFul\Server;

use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPResponse;

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
	
}