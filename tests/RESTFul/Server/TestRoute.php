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
	 * @Path /simple
	 * @GET
	 */
	function simpleTest(){
		return new HTTPResponse();
	}
	
}