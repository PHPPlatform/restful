<?php

namespace PhpPlatform\Tests\RESTFul\Services\Models;

use PhpPlatform\RESTFul\Serialization\Serialize;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;

class EmployeeInterfaceSerializer implements Serialize {
	public static function serialize($employee) {
		if(!($employee instanceof EmployeeInterface)){
			throw new BadInputException("argument to be an instance of EmployeeInterface");
		}
		
		$employeeXML = new \SimpleXMLElement("<employee></employee>");
		$employeeReflection = new \ReflectionClass($employee);
		while($employeeReflection){
			$properties = $employeeReflection->getProperties();
			foreach ($properties as $property){
				$property->setAccessible(true);
				$employeeXML->addAttribute($property->getName(),$property->getValue($employee));
			}
			$employeeReflection = $employeeReflection->getParentClass();
		}
		return $employeeXML->asXML();
	}
}