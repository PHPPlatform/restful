<?php

namespace PhpPlatform\Tests\RESTFul\Services\Models;

class Employee extends Person {
	private $empId;
	
	public function getEmpId() {
		return $this->empId;
	}
	public function setEmpId($empId) {
		$this->empId = $empId;
		return $this;
	}
	
}