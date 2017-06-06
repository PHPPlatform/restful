<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use Guzzle\Http\Client;

class TestHTTPRequest extends TestBase{
	
	function testGetters(){
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/getters?p1=qv1');
		$request->addHeader("h1", "hv1");
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"method":"GET","protocol":"http","host":"localhost","appPath":"/'.APP_PATH.'","uri":"/test/http-request/getters","queryParam_p1":"qv1","header_h1":"hv1"}', $response->getBody(true));
	}
	
	function testText(){
		$client = new Client();
		$textContent = 'This is my Text';
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/text',array("Content-Type"=>"text/plain","Content-Length"=>strlen($textContent)),$textContent);
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($textContent, $response->getBody(true));
	}
	
	function testJSON(){
		$client = new Client();
		$jsonContent = '{"name":"raaghu","children":[{"name":"shri"},{"name":"di"}]}';
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/json',array("Content-Type"=>"application/json","Content-Length"=>strlen($jsonContent)),$jsonContent);
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals($jsonContent, $response->getBody(true));
	}
	
	function testXML(){
		$client = new Client();
		$xmlContent = '<person name="raaghu"><children><person name="shri"/><person name="di"/></children></person>';
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/xml',array("Content-Type"=>"application/xml","Content-Length"=>strlen($xmlContent)),$xmlContent);
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('<?xml version="1.0"?>'."\n".$xmlContent."\n", $response->getBody(true));
	}
	
	function testForm(){
		$client = new Client();
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/form',null,array("n1"=>"v1","n2"=>"v2"));
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals('{"n1":"v1","n2":"v2"}', $response->getBody(true));
	}
	
	function testFile(){
		$client = new Client();
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/file',null,array("n1"=>"v1","n2"=>"v2","f1"=>"@".__FILE__,"f2"=>"@".dirname(__FILE__).'/TestHTTPResponse.php'));
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		
		$f1_tmp_name = $response->getHeader('f1_tmp_name');
		$f1_tmp_name = str_replace('\\', '\\\\', $f1_tmp_name);
		$f1_size     = $response->getHeader('f1_size');
		$f2_tmp_name = $response->getHeader('f2_tmp_name');
		$f2_tmp_name = str_replace('\\', '\\\\', $f2_tmp_name);
		$f2_size     = $response->getHeader('f2_size');
		
		$responseBody = '{"files":{"f1":{"name":"TestHTTPRequest.php","type":"text/x-php","tmp_name":"'.$f1_tmp_name.'","error":0,"size":'.$f1_size.'},"f2":{"name":"TestHTTPResponse.php","type":"text/x-php","tmp_name":"'.$f2_tmp_name.'","error":0,"size":'.$f2_size.'}},"data":{"n1":"v1","n2":"v2"}}';
		$this->assertEquals($responseBody, $response->getBody(true));
	}
	
}