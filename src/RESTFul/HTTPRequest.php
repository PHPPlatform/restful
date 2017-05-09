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
		$this->headers = self::getHeaders();
		
		// deserialize the content
		$contentType = $this->headers['Content-Type'];
		$internalContentType = $_SERVER['PLATFORM_INTERNAL_CONTENT_TYPE'];
		
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
		
		$this->data = $reflectionMethod->invoke(null,file_get_contents('php://input'));
		
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
	    return $this->queryParams[$name];
	}
	
	/**
	 * Returns the header value for the provided header name
	 * @param string $name
	 * @return string
	 */
	function getHeader($name){
	    return $this->headers[$name];
	}
	
	/**
	 * Returns Data with this request
	 * 
	 * @return mixed the type of the return object depends on $_SERVER['PLATFORM_INTERNAL_CONTENT_TYPE']
	 */
	function getData(){
		return $this->data;
	}
	
	/**
	 * this method returns the headers 
	 * @return array
	 */
	private function getHeaders(){
		if(function_exists('getallheaders')){
			return getallheaders();
		}else{
			$headers = array();
			$copy_server = array(
					'CONTENT_TYPE'   => 'Content-Type',
					'CONTENT_LENGTH' => 'Content-Length',
					'CONTENT_MD5'    => 'Content-Md5',
			);
			foreach ($_SERVER as $key => $value) {
				if (substr($key, 0, 5) === 'HTTP_') {
					$key = substr($key, 5);
					if (!isset($copy_server[$key]) || !isset($_SERVER[$key])) {
						$key = str_replace(' ', '-', ucwords(strtolower(str_replace('_', ' ', $key))));
						$headers[$key] = $value;
					}
				} elseif (isset($copy_server[$key])) {
					$headers[$copy_server[$key]] = $value;
				}
			}
			if (!isset($headers['Authorization'])) {
				if (isset($_SERVER['REDIRECT_HTTP_AUTHORIZATION'])) {
					$headers['Authorization'] = $_SERVER['REDIRECT_HTTP_AUTHORIZATION'];
				} elseif (isset($_SERVER['PHP_AUTH_USER'])) {
					$basic_pass = isset($_SERVER['PHP_AUTH_PW']) ? $_SERVER['PHP_AUTH_PW'] : '';
					$headers['Authorization'] = 'Basic ' . base64_encode($_SERVER['PHP_AUTH_USER'] . ':' . $basic_pass);
				} elseif (isset($_SERVER['PHP_AUTH_DIGEST'])) {
					$headers['Authorization'] = $_SERVER['PHP_AUTH_DIGEST'];
				}
			}
			return $headers;
		}
	}
	
}