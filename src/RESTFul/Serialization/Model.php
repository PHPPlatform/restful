<?php

namespace PhpPlatform\RESTFul;

/**
 * Implementing this interface makes a class eligible for serializing
 * 
 * @example
 *   An existing Persistance Model can be made serializable by a class extending it and implemeting this interface
 * 
 * 
 */
interface Model {
	
	/**
	 * returns property names which needs to be serialized , other properties in the class are not serialized
	 * 
	 * @return string[] property names
	 */
	function __getProperties();
}