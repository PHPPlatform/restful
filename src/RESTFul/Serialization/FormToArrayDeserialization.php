<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;

class FormToArrayDeserialization implements Deserialize {
	
	/**
	 * deserializes the application/x-www-form-urlencoded $data
	 * @param string $data
	 *
	 * @return array PHP array with information about the uploaded form data
	 * 			array['data']   form data
	 *        
	 */
	public static function deserialize($data) {
		$data = $_POST;
		$_POST = array();
		return $data;
	}
}