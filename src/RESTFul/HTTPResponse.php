<?php
namespace PhpPlatform\RESTFul;

use PhpPlatform\Errors\Exceptions\Application\BadInputException;
use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;
use phpDocumentor\Reflection\DocBlock\Serializer;

class HTTPResponse{
	
	
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
			throw new BadInputException('Invalid Code');
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
	 * @param int|float|string|boolean $value
	 * 
	 * @return HTTPResponse this object for method chaining
	 */
	function setHeader($name,$value){
		if(!is_string($name) || !is_scalar($value)){
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
	 * calling this method will cause exit from php
	 * 
	 * Any code after calling this method won't be executed
	 * 
	 * @param $httpAccept Accept Header of this http request
	 */
	function flush($httpAccept = null){
		try{
			// set reponse code and message
			header($_SERVER['SERVER_PROTOCOL']." ".$this->code." ".$this->message);
			
			foreach ($this->headers as $name=>$value){
				header("$name:$value");
			}
			
			// clear buffer , if any
			if(ob_get_length() !== false){
				ob_clean();
			}
			
			// write data
			$dataType = gettype($this->data);
			if($dataType == "unknown type"){
				$this->exitWithError("Unkown type of data in HTTPResponse");
			}
			
			if($dataType == "NULL"){
				// don't do anything
			}else{
				$serializers = Settings::getSettings(Package::Name,"serializers");
				if($serializers == null){
					$serializers = array();
				}
				$contentTypeToSerializerMap = array();
				if($dataType == "object"){
					$className = get_class($this->data);
					
					$classAndInterfaces = $this->getParentsAndInterfaces($className);
					
					foreach ($classAndInterfaces as $_className=>$_interfaces){
						if(array_key_exists($_className, $serializers) && is_array($serializers[$_className])){
							$contentTypeToSerializerMap = array_merge($contentTypeToSerializerMap,$serializers[$_className]);
						}
						foreach ($_interfaces as $_interface){
							if(array_key_exists($_interface, $serializers) && is_array($serializers[$_interface])){
								$contentTypeToSerializerMap = array_merge($contentTypeToSerializerMap,$serializers[$_interface]);
							}
						}
					}
				}else{
					if(array_key_exists($dataType, $serializers) && is_array($serializers[$dataType])){
						$contentTypeToSerializerMap = array_merge($contentTypeToSerializerMap,$serializers[$dataType]);
					}
				}
				$acceptPreferences = $this->getAcceptPreferenceTable($httpAccept);
				$_serializer = $this->chooseSerializer($contentTypeToSerializerMap, $acceptPreferences);
				
				// write content-type header
				$contentType = $_serializer["type"];
				header("Content-Type:$contentType");
				
				// call serializer
				$serializer = $_serializer["serializer"];
				$serializedData = $this->serialize($serializer, $this->data);
				
				// output serialized data
				echo $serializedData;
			}
		}catch (\Exception $e){
			// if any exception , this should result in error
			$this->exitWithError($e->getMessage());
		}
		exit();
	}
	
	/**
	 * This method exits the php scripts , resulting in 500 Internal Server Error
	 * 
	 * @param $message message to log
	 */
	private function exitWithError($message){
		header($_SERVER['SERVER_PROTOCOL']." 500 Internal Server Error");
		new InternalServerError($message); // for logging purpose a new InternalServerError exception is created, but not thrown
		// clear buffer , if any
		if(ob_get_length() !== false){
			ob_clean();
		}
		exit();
	}
	
	/**
	 * invokes serilizer with provided data
	 * 
	 * @param string $serializerClass class name of the serializer
	 * @param mixed $data data to be passed as argument to serialize method
	 * 
	 * @return string serializedData
	 */ 
	private function serialize($serializerClass,$data){
		if(!class_exists($serializerClass,true)){
			$this->exitWithError("Serializer class $serializerClass does not exist");
		}
		
		$reflectionClass = new \ReflectionClass($serializerClass);
		$serializerInterface = 'PhpPlatform\RESTFul\Serialization\Serialize';
		if(!($reflectionClass->implementsInterface($serializerInterface))){
			$this->exitWithError("$serializerClass does not implement $serializerInterface");
		}
		$serializerInstance = $reflectionClass->newInstance();
		$serializeMethod = $reflectionClass->getMethod("serialize");
		
		$serializedData = $serializeMethod->invoke($serializerInstance,$data);
		return $serializedData;
	}
	
	/**
	 * converts Accept header into array of acceptTypes based on their preferences
	 * @param string $acceptString Accept Header as sent by the client
	 * 
	 * @return array of each acceptTypes grouped by their quality parameter
	 */ 
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
	
	/**
	 * This method chooses serializer for the accept-preferences
	 * 
	 * @param $contentTypeToSerializer map of content-type to serializer 
	 * @param $acceptPreferences accept preferences grouped by their quality parameter
	 * 
	 * @return array containing type and serializer
	 *             ["type"] content-type of the choosen Serializer
	 *             ["serializer"] classname of the serializer
	 */ 
	private function chooseSerializer($contentTypeToSerializer,$acceptPreferences){
		
		$serializerToContentType = array_flip($contentTypeToSerializer);
		
		$_contentTypeToSerializer = array();
		$isFirst = true;
		foreach ($contentTypeToSerializer as $contentType=>$serializer){
			$_contentTypeToSerializer[$contentType] = $serializer;
			$contentTypeGeneric = preg_replace("/\/.*/","/*",$contentType);
			if(!array_key_exists($contentTypeGeneric, $_contentTypeToSerializer)){
				$_contentTypeToSerializer[$contentTypeGeneric] = $serializer;
			}
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
		
		$type = null;
		if(array_key_exists($serializer, $serializerToContentType)){
			$type = $serializerToContentType[$serializer];
		}
		return array("type"=>$type,"serializer"=>$serializer);
	}
	

}