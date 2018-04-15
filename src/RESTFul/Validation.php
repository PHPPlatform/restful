<?php

namespace PhpPlatform\RESTFul;

use PhpPlatform\Errors\Exceptions\Http\_4XX\BadRequest;

/**
 * Provides utilities to validate and sanitize the input data from rest clients
 * 
 * All of the methods can be chained to validate for multiple conditions
 * 
 * Example 
 * 
 * $validation = new Validation('data',['a','b','c']);
 * $validation->isArray()->hasCount(2,3); // success
 * $validation->key(2)->isString()->hasLength(2,4);
 *
 */
class Validation {
    
    private $key = null;
    private $data = null;
    private $validationErrors = null;
    private $continue = true; // continue to next validation or skip it
    
    /**
     * @var Validation
     */
    private $parentValidation = null;
    
    /**
     * @var Validation[]
     */
    private $childValidations = [];
	
    function __construct($key, $data){
        $this->key = $key;
        $this->data = $data;
    }
    
	function containsOnly($keys){
	    if($this->continue){
    		if(!is_array($this->data) || count(array_diff(array_keys($this->data), $keys)) > 0){
    			$this->validationErrors = 'invalid';
    			$this->continue = false;
    		}
	    }
		return $this;
	}
	
	function containsAll($keys){
	    if($this->continue){
	        if(!is_array($this->data) || count(array_diff($keys,array_keys($this->data))) > 0){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function containsExactly($keys){
	    if($this->continue){
	        $this->containsOnly($keys);
	        $this->containsAll($keys);
	    }
	    return $this;
	}
	
	function key($key){
	    $subValidation = new Validation($key,$this->data[$key]);
	    $subValidation->continue = $this->continue;
	    $subValidation->parentValidation = $this;
	    $this->childValidations[] = $subValidation;
	    return $subValidation;
	}
	
	function required(){
	    if($this->continue){
	        if(!isset($this->data)){
	            $this->validationErrors = 'missing';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function defaultValue($value){
	    if($this->continue){
	        if(!isset($this->data)){
	            $this->parentValidation->data[$this->key] = $value;
	        }
	    }
	    return $this;
	}
	
	function isNumeric(){
	    if($this->continue){
	        if(!is_numeric($this->data)){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function isInt(){
	    $this->isNumeric();
	    if($this->continue){
	        if(intval($this->data) != $this->data){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function inRange($min = null,$max = null){
	    if($this->continue){
	        if($min !== null && $this->data < $min){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	        if($max !== null && $this->data > $max){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	}
	
	function isTimestamp(){
	    if($this->continue){
	        if(strtotime($this->data) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function isString(){
	    if($this->continue){
	        if(is_string($this->data) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function isArray(){
	    if($this->continue){
	        if(is_array($this->data) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function isBoolean(){
	    if($this->continue){
	        if(is_bool($this->data) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function match($regExpr){
	    if($this->continue){
	        if(preg_match($regExpr,$this->data) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function hasLength($min = null, $max = null){
	    if($this->continue){
	        if($min !== null && strlen($this->data) < $min){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	        if($max !== null && strlen($this->data) > $max){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function hasCount($min = null, $max = null){
	    if($this->continue){
	        if($min !== null && count($this->data) < $min){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	        if($max !== null && count($this->data) > $max){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function in($range){
	    if($this->continue){
	        if(in_array($this->data,$range) === false){
	            $this->validationErrors = 'invalid';
	            $this->continue = false;
	        }
	    }
	    return $this;
	}
	
	function getValidationErrors(){
	    if(isset($this->validationErrors)){
	        return $this->validationErrors;
	    }
	    $validationErrors = [];
	    foreach ($this->childValidations as $childValidation){
	        $childValidationErrors = $childValidation->getValidationErrors();
	        if(is_string($childValidationErrors) || (is_array($childValidationErrors) && count($childValidationErrors) > 0)){
	            $validationErrors[$childValidation->key] = $childValidationErrors;
            }
	    }
	    return $validationErrors;
	}
	
	function generateBadRequestOnError(){
	    $validationErrors = $this->getValidationErrors();
	    if(is_string($validationErrors) || (is_array($validationErrors) && count($validationErrors) > 0)){
	        // there is validation error
	        throw new BadRequest($validationErrors);
	    }
	}
}