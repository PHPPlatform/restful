<?php

namespace PhpPlatform\Tests\RESTFul\Services\Models;

use PhpPlatform\RESTFul\Serialization\Serialize;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;

class PersonSerializer implements Serialize {
	public static function serialize($person) {
		if(!($person instanceof Person)){
			throw new BadInputException("argument to be an instance of Person");
		}
		
		$personJson = array();
		$personReflection = new \ReflectionClass($person);
		while($personReflection){
			$properties = $personReflection->getProperties();
			foreach ($properties as $property){
				$property->setAccessible(true);
				$personJson[$property->getName()] = $property->getValue($person);
			}
			$personReflection = $personReflection->getParentClass();
		}
		return json_encode($personJson);
	}
}