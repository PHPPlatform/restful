<?php

namespace PhpPlatform\RESTFul\Serialization;

interface Deserialize {
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return mixed PHP Represenation of the passed in $data
	 */
	function deserialize($data);
}