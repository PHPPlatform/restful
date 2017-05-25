<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;

class StringToPlainTextSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data
	 * @param string $data
	 *
	 * @return string 
	 */
	public static function serialize($data) {
		return $data;
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return string
	 */
	public static function deserialize($data) {
		return $data;
	}
}