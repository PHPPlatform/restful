<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;

/**
 * @Path "/test/route/non-service"
 *
 */
class TestRouteNonService {

	/**
	 * @Path "/empty"
	 * @GET
	 */
	function emptyTest(){
		return new HTTPResponse();
	}
}