<?php

namespace PhpPlatform\Tests\RESTFul\Server;

use PhpPlatform\RESTFul\HTTPResponse;

/**
 * @Path /test/route/non-service
 *
 */
class TestRouteNonService {

	/**
	 * @Path /empty
	 * @GET
	 */
	function emptyTest(){
		return new HTTPResponse();
	}
}