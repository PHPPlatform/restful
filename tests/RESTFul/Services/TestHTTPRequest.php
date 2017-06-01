<?php

namespace PhpPlatform\Tests\RESTFul\Services;

use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\RESTService;
use PhpPlatform\RESTFul\HTTPRequest;
use PhpPlatform\Errors\Exceptions\Application\BadInputException;

/**
 * @Path /test/http-request
 *
 */
class TestHTTPRequest implements RESTService{
	
	/**
	 * @Path /getters
	 * @GET
	 */
	function testGetters(HTTPRequest $request){
		$content = array(
				"method"=>$request->getMethod(),
				"protocol"=>$request->getProtocol(),
				"host"=>$request->getHost(),
				"appPath"=>$request->getAppPath(),
				"uri"=>$request->getUri(),
				"queryParam_p1"=>$request->getQueryParam("p1"),
				"header_h1"=>$request->getHeader("h1")
	    );
		
		$response = new HTTPResponse();
		$response->setData($content);
		return $response;
	}
	
	/**
	 * @Path /text
	 * @POST
	 * @Consumes string
	 */
	function testText(HTTPRequest $request){
		$inputString = $request->getData();
		if(!is_string($inputString)){
			throw new BadInputException("Input should be string");
		}
		return new HTTPResponse(200,'OK',$inputString);
	}
	
	/**
	 * @Path /json
	 * @POST
	 * @Consumes array
	 */
	function testJSON(HTTPRequest $request){
		$inputArray = $request->getData();
		return new HTTPResponse(200,'OK',$inputArray);
	}
	
	/**
	 * @Path /xml
	 * @POST
	 * @Consumes SimpleXMLElement
	 */
	function testXML(HTTPRequest $request){
		$inputXml = $request->getData();
		if(!($inputXml instanceof \SimpleXMLElement)){
			throw new BadInputException("Input is not an xml");
		}
		return new HTTPResponse(200,'OK',$inputXml);
	}
	
	/**
	 * @Path /form
	 * @POST
	 * @Consumes array
	 */
	function testForm(HTTPRequest $request){
		$inputForm = $request->getData();
		if(!is_array($inputForm)){
			throw new BadInputException("Input is not an array");
		}
		return new HTTPResponse(200,'OK',$inputForm);
	}
	
	/**
	 * @Path /file
	 * @POST
	 * @Consumes array
	 */
	function testFile(HTTPRequest $request){
		
		$response = new HTTPResponse();
		
		$inputForm = $request->getData();
		if(!is_array($inputForm)){
			throw new BadInputException("Input is not an array");
		}
		$response->setData($inputForm);
		
		// file 1
		$uploadedFileContent = file_get_contents($inputForm['files']['f1']['tmp_name']);
		$actualFileContent = file_get_contents(dirname(__FILE__).'/../ClientSide/TestHTTPRequest.php');
		if($uploadedFileContent != $actualFileContent){
			throw new BadInputException("File 1 not uploaded correctly");
		}
		$response->setHeader('f1_tmp_name', $inputForm['files']['f1']['tmp_name']);
		$response->setHeader('f1_size', $inputForm['files']['f1']['size']);
		
		
		// file 2
		$uploadedFileContent = file_get_contents($inputForm['files']['f2']['tmp_name']);
		$actualFileContent = file_get_contents(dirname(__FILE__).'/../ClientSide/TestHTTPResponse.php');
		if($uploadedFileContent != $actualFileContent){
			throw new BadInputException("File 2 not uploaded correctly");
		}
		$response->setHeader('f2_tmp_name', $inputForm['files']['f2']['tmp_name']);
		$response->setHeader('f2_size', $inputForm['files']['f2']['size']);
		
		return $response;
	}
	
}