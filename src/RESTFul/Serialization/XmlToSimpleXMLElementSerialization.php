<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;
use PhpPlatform\RESTFul\Model;

class XmlToModelSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data 
	 * @param Model $data
	 * 
	 * @return string XML String representaion of the data
	 */
	public function serialize(Model $data) {
		return $data->asXML();
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return Model object for the passed in xml $data
	 */
	public function deserialize($data) {
		return new \SimpleXMLElement($data);
	}
}