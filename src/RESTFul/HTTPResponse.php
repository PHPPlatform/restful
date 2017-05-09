<?php
namespace PhpPlatform\RESTFul;


use PhpPlatform\Errors\Exceptions\Application\BadInputException;
use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class HTTPResponse{
	
	const THIS_PACKAGE_NAME = 'php-platform/restful';
	
	private $code = 200;
	private $message = 'OK';
	private $headers = array();
	private $data = null;
	
	function __construct($code = 200, $message = "OK", $data = null){
		$this->setCode($code);
		$this->setMessage($message);
		$this->setData($data);
	}
	
	/**
	 * set the response code
	 * @param int $code
	 * 
	 * @return HTTPResponse this object for method chaining
	 */
	function setCode($code){
		if(!(is_int($code) && 100 <= $code && $code <= 599)){
			throw new BadInputException('Invalid code');
		}
		$this->code = $code;
		return $this;
	}
	
	/**
	 * set the response message
	 * @param string $message
	 * 
	 * @return HTTPResponse this object for method chaining
	 */
	function setMessage($message){
		if(!is_string($message)){
			throw new BadInputException('Invalid Message');
		}
		$this->message = $message;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 * 
	 * @return HTTPResponse this object for method chaining
	 */
	function setHeader($name,$value){
		if(!is_string($name) || !is_string($value)){
			throw new BadInputException('Invalid Name or Value');
		}
		$this->headers[$name] = $value;
		return $this;
	}
	
	/**
	 * @param string $name
	 * @param string $value
	 *
	 * @return HTTPResponse this object for method chaining
	 */
	function setData($data){
		$this->data = $data;
		return $this;
	}
	
	/**
	 * this method flushes the HTTPResponse, output and headers are written , 
	 * tobe called only once and it ends the request
	 * 
	 * called from PhpPlatform\RESTFul\Route using Reflection
	 * 
	 */
	private function flush(HTTPRequest $httpRequest){
		// set reponse code and message
		header($_SERVER['SERVER_PROTOCOL']." ".$this->code." ".$this->message);
		
		foreach ($this->headers as $name=>$value){
			header("$name : $value");
		}
		
		// write data
		$dataType = gettype($this->data);
		if($dataType == "unknown type"){
			self::exitWithError("Unkown type of data in HTTPResponse");
		}
		
		$serializedData = "";
		
		if($dataType == "NULL"){
			// don't do anything
		}elseif($dataType == "object"){
			// find the serializer
			$acceptPreferences = self::getAcceptPreferenceTable($httpRequest->getHeader("Accept"));
			
			$className = get_class($this->data);
			
			$classAndInterfaces = self::getParentsAndInterfaces($className);
			
			$contentTypeToSerializerMap = array();
			foreach ($classAndInterfaces as $_className=>$_interfaces){
				$contentTypes = Settings::getSettings(self::THIS_PACKAGE_NAME,"serializers.$_className");
				if(is_array($contentTypes)){
					$contentTypeToSerializerMap = array_merge($contentTypes,$contentTypeToSerializerMap);
				}
				foreach ($_interfaces as $_interface){
					$contentTypes = Settings::getSettings(self::THIS_PACKAGE_NAME,"serializers.$_interface");
					if(is_array($contentTypes)){
						$contentTypeToSerializerMap = array_merge($contentTypes,$contentTypeToSerializerMap);
					}
				}
			}
			
			
			$serializer = self::chooseSerializer($contentTypeToSerializerMap, $acceptPreferences);
			
			$serializedData = self::serialize($serializer, $this->data);
			
		}else{
			$acceptPreferences = self::getAcceptPreferenceTable($httpRequest->getHeader("Accept"));
			$contentTypeToSerializerMap = Settings::getSettings(self::THIS_PACKAGE_NAME,"serializers.$dataType");
			$serializer = self::chooseSerializer($contentTypeToSerializerMap, $acceptPreferences);
			$serializedData = self::serialize($serializer, $this->data);
		}
		
		
		exit();
	}
	
	private function exitWithError($message){
		header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error");
		new InternalServerError($message);
		exit();
	}
	
	private function serialize($serializerClass,$data){
		if(!class_exists($serializerClass,true)){
			self::exitWithError("Serializer class $serializerClass does not exist");
		}
		
		$reflectionClass = new \ReflectionClass($serializerClass);
		$serializerInterface = 'PhpPlatform\RESTFul\Serialization\Serialize';
		if(!($reflectionClass->implementsInterface($serializerInterface))){
			self::exitWithError("$serializerClass does not implement $serializerInterface");
		}
		$serializerInstance = $reflectionClass->newInstance();
		$serializeMethod = $reflectionClass->getMethod("serialize");
		
		$serializedData = $serializeMethod->invoke($serializerInstance,$data);
		return $serializedData;
	}
	
	private function getAcceptPreferenceTable($acceptString){
		$acceptsPreferences = array();
		if(!isset($acceptString) || $acceptString == ""){
			$acceptString = "*/*";
		}
		$acceptTypes =  preg_split("/\s*\,\s*/",trim($acceptString));
		
		foreach ($acceptTypes as $acceptType){
			$acceptTypeWithQuality = preg_split("/\s*;\s*q=/",$acceptType);
			$quality = 1;
			if(count($acceptTypeWithQuality) == 2 && is_numeric($acceptTypeWithQuality[1])){
				$acceptType = $acceptTypeWithQuality[0];
				$quality = $acceptTypeWithQuality[1];
			}
			if(!isset($acceptsPreferences[$quality])){
				$acceptsPreferences[$quality] = array();
			}
			
			$acceptsPreferences[$quality][] = $acceptType;
		}
		
		krsort($acceptsPreferences);
		
		// sort acceptTypes , moving the accept with * to the last but keeping the rest in same order
		foreach ($acceptsPreferences as $quality=>&$acceptTypes){
			$acceptTypesOriginalOrder = array_flip($acceptTypes);
			usort($acceptTypes,function($elem1,$elem2) use ($acceptTypesOriginalOrder){
				$elem1EndsInStar = (strpos($elem1,"/*") === strlen($elem1) - 2);
				$elem2EndsInStar = (strpos($elem2,"/*") === strlen($elem2) - 2);
				if($elem1EndsInStar && !$elem2EndsInStar){
					return 1;
				}
				if(!$elem1EndsInStar && $elem2EndsInStar){
					return -1;
				}
				if($elem1 == "*/*"){
					return 1;
				}
				if($elem2 == "*/*"){
					return -1;
				}
				return $acceptTypesOriginalOrder[$elem1] - $acceptTypesOriginalOrder[$elem2];
			});
		}
		
		return $acceptsPreferences;
		
	}
	
	
	/**
	 * 
	 * @param object|string $className
	 * @return array of this and parent classnames and interfces implemented by each of this class
	 *         array['class1'] => array() \\ interfaces of class1
	 *         array['class2'] => array() \\ interfaces of class2
	 *         
	 *         the order classnames are in the order of class inheritance
	 */
	private function getParentsAndInterfaces($className){
		if($className === false){
			return array();
		}
		
		if(gettype($className) == 'object'){
			$className = get_class($className);
		}
		
		$thisClassInterfaces = class_implements($className,true);
		$parentClassName = get_parent_class($className);
		$parentClassAndInterfaces = self::getParentsAndInterfaces($parentClassName);
		
		foreach ($parentClassAndInterfaces as $parentClassName=>$parentClassInterfaces){
			$thisClassInterfaces = array_diff($thisClassInterfaces, $parentClassInterfaces);
		}
		
		$classAndInterfces = array($className=>$thisClassInterfaces);
		
		return $classAndInterfces+$parentClassAndInterfaces;
	}
	
	
	
	
	private function chooseSerializer($contentTypeToSerializer,$acceptPreferences){
		
		$_contentTypeToSerializer = array();
		$isFirst = true;
		foreach ($contentTypeToSerializer as $contentType=>$serializer){
			$_contentTypeToSerializer[$contentType] = $serializer;
			$_contentTypeToSerializer[preg_replace("/\/.*/","/*",$contentType)] = $serializer;
			if($isFirst){
				$_contentTypeToSerializer["*/*"] = $serializer;
				$isFirst = false;
			}
		}
		
		
		$serializer = null;
		foreach ($acceptPreferences as $acceptTypes){
			foreach ($acceptTypes as $acceptType){
				if(array_key_exists($acceptType, $_contentTypeToSerializer)){
					$serializer = $_contentTypeToSerializer[$acceptType];
					break;
				}
			}
			if(isset($serializer)){
				break;
			}
		}
		return $serializer;
	}
	

}