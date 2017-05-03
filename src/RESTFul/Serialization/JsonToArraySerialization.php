<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;

class JsonToArraySerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data
	 * @param array $data
	 *
	 * @return string JSON String representaion of the data
	 */
	public function serialize(array $data) {
		return json_encode($data);
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return array PHP array Represenation of the passed in $data
	 */
	public function deserialize($data) {
		return json_decode($data,true);
	}
}