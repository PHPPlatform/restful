<?php

namespace PhpPlatform\RESTFul\CORS;

use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPResponse;

/**
 * @Path "/"
 */
class PreFlight implements RESTService {
	
	/**
	 * @Path "/"
	 * @OPTIONS
	 */
	function preFlight(){
		return new HTTPResponse();
	}
}