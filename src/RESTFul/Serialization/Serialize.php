<?php

namespace PhpPlatform\RESTFul\Serialization;

interface Serialize {
	
	/**
	 * serializes the $data 
	 * @param mixed $data
	 * 
	 * @return string String representaion of the data
	 */
	function serialize($data);
}