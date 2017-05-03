<?php

namespace PhpPlatform\RESTFul;

use PhpPlatform\Errors\Exceptions\Http\_4XX\BadRequest;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class HTTPRequest {
	private $url = null;
	private $queryParams = null;
	private $headers = null;
	private $object = null;
	private $files = null;
	
	private static $instance = null;
	
	private function __construct(){
		$this->url = $_SERVER['REQUEST_URI'];
		$this->queryParams = $_GET;
		$_GET = array();
		$this->headers = self::getHeaders();
		
		$contentType = $this->headers['Content-Type'];
		if(stripos($contentType, 'multipart/form-data') === 0){
			// actual content is the files 
			// validate files for error
			foreach ($_FILES as $name =>$_FILE){
				switch ($_FILE['error']){
					case UPLOAD_ERR_INI_SIZE : throw new BadRequest("Exceeded File size for $name");
					case UPLOAD_ERR_FORM_SIZE : throw new BadRequest("Exceeded File size for $name");
					case UPLOAD_ERR_PARTIAL : throw new BadRequest("$name File Uploaded Partially");
					case UPLOAD_ERR_NO_FILE : throw new BadRequest("No File Uploaded for $name");
					        
					case UPLOAD_ERR_NO_TMP_DIR : throw new InternalServerError("No Temporary Directory to write $name");
					case UPLOAD_ERR_CANT_WRITE : throw new InternalServerError("Cant write $name to Disk");
					case UPLOAD_ERR_EXTENSION : throw new InternalServerError("PHP extention caused the error to upload $name");
				}
			}
			$this->files = $_FILES;
			$_FILES = array();
			
			// if data present along with file , populate into object
			$this->object = $_POST;
			$_POST = array();
		}elseif (stripos($contentType, 'application/x-www-form-urlencoded') === 0){
			$this->object = $_POST;
			$_POST = array();
		}elseif (stripos($contentType, 'application/json') === 0){
			$jsonString = file_get_contents('php://input');
			$this->object = json_decode($jsonString,true);
			if($this->object === null){
				throw new BadRequest('Invalid JSON Content');
			}
		}elseif (stripos($contentType, 'application/xml') === 0){
			$xmlString = file_get_contents('php://input');
			try{
				$this->object = new \SimpleXMLElement($xmlString);
			}catch (\Exception $e){
				throw new BadRequest('Invalid XML Content');
			}
		}else{
			// else save raw 
			$this->object = file_get_contents('php://input');
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
	 * Returns the url of this request
	 * @return string
	 */
	function getUrl(){
		return $this->url;
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
	 * Returns Data as string or FALSE on failure
	 * 
	 * @return string
	 */
	function getDataAsString(){
		if(is_string($this->object)){
			return $this->object;
		}elseif (is_array($this->object)){
			return json_encode($this->object);
		}elseif ($this->object instanceof \SimpleXMLElement){
			return $this->object->asXML();
		}else {
			return FALSE;
		}
	}
	
	/**
	 * Returns Data as associative array or FALSE on failure
	 * 
	 * @return array|boolean
	 */
	function getDataAsArray(){
		if(is_array($this->object)){
			return $this->object;
		}elseif ($this->object instanceof \SimpleXMLElement){
			
		}else{
			return FALSE;
		}
	}
	
	/**
	 * Returns Data as SimpleXMLElement object or FALSE on Failure
	 * @return \SimpleXMLElement|boolean
	 */
	function getDataAsXml(){
		if($this->object instanceof \SimpleXMLElement){
			return $this->object;
		}else {
			return FALSE;
		}
	}
	
	/**
	 * @todo add getDataAsMappedEntity , which maps data in $this->object into provided class
	 */
	
	/**
	 * Returns associative array of uploaded file information with following information
	 * name , type, size, tmp_name
	 * 
	 * @param string $name , Name of the file 
	 * 
	 * @return array
	 */
	function getFile($name){
		return $this->files[$name];
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