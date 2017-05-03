<?php
namespace PhpPlatform\RESTFul;


use PhpPlatform\Errors\Exceptions\Application\BadInputException;

class HTTPResponse{
	
	private $code = 200;
	private $message = 'OK';
	private $headers = array();
	private $object = null;
	
	
	function __construct(){
		
	}
	
	
	/**
	 * set the response code
	 * @param int $code
	 */
	function setCode($code){
		if(!(is_int($code) && 100 <= $code && $code <= 599)){
			throw new BadInputException('Invalid code');
		}
		$this->code = $code;
	}
	
	/**
	 * set the response message
	 * @param string $message
	 */
	function setMessage($message){
		if(!is_string($message)){
			throw new BadInputException('Invalid Message');
		}
		$this->message = $message;
	}
	
	/**
	 * 
	 * @param string $name
	 * @param string $value
	 */
	function setHeader($name,$value){
		if(!is_string($name) || !is_string($value)){
			throw new BadInputException('Invalid Name or Value');
		}
		$this->headers[$name] = $value;
	}
	
	function setStringData($data){
		if(!is_string($data)){
			throw new BadInputException('Invalid data');
		}
		$this->object = $data;
	}
	
	function setArrayData($data){
		if(!is_array($data)){
			throw new BadInputException('Invalid data');
		}
		$this->object = $data;
	}
	
	function setXmlData(\SimpleXMLElement $data){
		$this->object = $data;
	}
	

}