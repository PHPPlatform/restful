<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;

class ArrayToJsonSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data
	 * @param array $data
	 *
	 * @return string JSON String representaion of the data
	 */
	public static function serialize($data) {
		return json_encode($data);
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return array PHP array Represenation of the passed in $data
	 */
	public static function deserialize($data) {
		return json_decode($data,true);
	}
}