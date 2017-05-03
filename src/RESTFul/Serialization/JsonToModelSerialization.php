<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;
use PhpPlatform\RESTFul\Model;

class JsonToModelSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data
	 * @param Model $data
	 *
	 * @return string JSON String representaion of the data
	 */
	public function serialize(Model $data) {
		return json_encode($data);
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return Model for the passed in $data
	 */
	public function deserialize($data) {
		return json_decode($data,true);
	}
}