<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;

class SimpleXMLElementToXmlSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data 
	 * @param \SimpleXMLElement $data
	 * 
	 * @return string XML String representaion of the data
	 */
	public static function serialize($data) {
		return $data->asXML();
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return \SimpleXMLElement object for the passed in xml $data
	 */
	public static function deserialize($data) {
		return new \SimpleXMLElement($data);
	}
}