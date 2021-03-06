<?php

namespace PhpPlatform\RESTFul;

use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_4XX\NotAcceptable;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class HTTPRequest {
	private $method = null;
	private $protocol = null;
	private $host = null;
	private $appPath = null;
	private $uri = null;
	private $queryParams = null;
	private $headers = null;
	private $data = null;
	
	private static $instance = null;
	
	private function __construct(){
		$this->method = $_SERVER['REQUEST_METHOD'];
		
		$this->protocol = isset($_SERVER['HTTPS'])?"https":"http";
		
		$this->host = $_SERVER['HTTP_HOST'];
		
		$this->appPath = $_SERVER['PLATFORM_APPLICATION_PATH'];
		
		$this->uri = $_SERVER['REQUEST_URI'];
		
		$this->queryParams = $_GET;
		$_GET = array();
		$this->headers = getallheaders();
		
		// deserialize the content
		if(strtoupper($this->method) != 'GET' && strtoupper($this->method) != 'TRACE'){
			$data = file_get_contents('php://input');
			if(array_key_exists('Content-Type', $this->headers)){
				$contentType = $this->headers['Content-Type'];
				$positionOfSemiColonInContentType = strpos($contentType, ';');
				if($positionOfSemiColonInContentType !== false){
					$contentType = substr($contentType, 0, $positionOfSemiColonInContentType);
				}
				$internalContentType = $_SERVER['PLATFORM_SERVICE_CONSUMES'];
				
				$deserializer = Settings::getSettings(Package::Name,"deserializers.$contentType.$internalContentType");
				
				if(!class_exists($deserializer, true)){
					throw new NotAcceptable("$contentType Not Acceptable");
				}
				
				$deserializerReflectionClass = new \ReflectionClass($deserializer);
				$deserializerInterface = 'PhpPlatform\RESTFul\Serialization\Deserialize';
				if(!in_array($deserializerInterface, $deserializerReflectionClass->getInterfaceNames())){
					throw new InternalServerError("$deserializer does not implement $deserializerInterface");
				}
				
				$reflectionMethod = $deserializerReflectionClass->getMethod('deserialize');
				
				$this->data = $reflectionMethod->invoke(null,$data);
			}else{
				$this->data = $data;
			}
			
		}
	}
	
	/**
	 * returns the singleton instance of the HTTPRequest Object
	 * 
	 * @return HTTPRequest
	 */
	static function getInstance(){
		if(self::$instance === null){
			self::$instance = new HTTPRequest();
		}
		return self::$instance;
	}
	
	/**
	 * Returns HTTP method 
	 * @return string
	 */
	function getMethod() {
		return $this->method;
	}
	
	/**
	 * Returns Protocol of this request
	 * @return string either http ot https
	 */
	function getProtocol() {
		return $this->protocol;
	}
	
	/**
	 * Returns Host
	 * @return string 
	 */
	function getHost() {
		return $this->host;
	}
	
	/**
	 * Returns Application path
	 * @return string
	 */
	function getAppPath() {
		return $this->appPath;
	}
	
	/**
	 * Returns the uri of this request
	 * @return string
	 */
	function getUri(){
		return $this->uri;
	}
	
	/**
	 * Returns the query parameter value for the provided parameter name
	 * @param string $name
	 * @return string
	 */
	function getQueryParam($name){
		if(array_key_exists($name, $this->queryParams)){
			return $this->queryParams[$name];
		}else{
			return null;
		}
	}
	
	/**
	 * Returns the header value for the provided header name
	 * @param string $name
	 * @return string
	 */
	function getHeader($name){
		if(array_key_exists($name, $this->headers)){
			return $this->headers[$name];
		}else{
			return null;
		}
	}
	
	/**
	 * Returns Data with this request
	 * 
	 * @return mixed the type of the return object depends on $_SERVER['PLATFORM_SERVICE_CONSUMES']
	 */
	function getData(){
		return $this->data;
	}
	
}