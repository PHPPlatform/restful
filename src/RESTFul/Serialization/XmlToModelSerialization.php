<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\RESTFul\Serialization\Serialize;

class XmlToSimpleXMLElementSerialization implements Serialize, Deserialize {
	
	/**
	 * serializes the $data 
	 * @param \SimpleXMLElement $data
	 * 
	 * @return string XML String representaion of the data
	 */
	public function serialize(\SimpleXMLElement $data) {
		return $data->asXML();
	}
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return \SimpleXMLElement object for the passed in xml $data
	 */
	public function deserialize($data) {
		return new \SimpleXMLElement($data);
	}
}