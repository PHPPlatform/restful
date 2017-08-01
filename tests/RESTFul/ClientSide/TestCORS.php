<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Tests\RESTFul\TestBase;
use Guzzle\Http\Client;
use PhpPlatform\Mock\Config\MockSettings;
use PhpPlatform\RESTFul\Package;
use Guzzle\Http\Exception\BadResponseException;

class TestCORS extends TestBase {
	
	function testForNoCORSHeadersForSameOrigin(){
		MockSettings::setSettings(Package::Name, 'CORS', array(
				"AllowOrigins"=>array('http://example.com'),
				"AllowMethods"=>array('GET'),
				"AllowHeaders"=>array(),
				"AllowCredentials"=>false,
				"MaxAge"=>1000
		));
		
		$client = new Client();
		
		// without Origin header in the request
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$response = $client->send($request);
		
		$this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Methods'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Headers'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertNull($response->getHeader('Access-Control-Max-Age'));
		
		// with Origin header in the request
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$request->setHeader('Origin', APP_DOMAIN);
		$response = $client->send($request);
		
		$this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Methods'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Headers'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertNull($response->getHeader('Access-Control-Max-Age'));
	}
	
	function testNotAllowedOrigin(){
		MockSettings::setSettings(Package::Name, 'CORS', array(
				"AllowOrigins"=>array('http://example.com'),
				"AllowMethods"=>array('GET'),
				"AllowHeaders"=>array(),
				"AllowCredentials"=>false,
				"MaxAge"=>1000
		));
		
		$client = new Client();
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$request->setHeader('Origin', "http://mydomain.com");
		try{
		    $response = $client->send($request);
		}catch (BadResponseException $e){
			$response = $e->getResponse();
			$this->clearErrorLog();
		}
		$this->assertEquals(401, $response->getStatusCode());
		$this->assertEquals("CORS ERROR : http://mydomain.com is not a allowed origin", $response->getReasonPhrase());
		
		$this->assertNull($response->getHeader('Access-Control-Allow-Origin'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Methods'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Headers'));
		$this->assertNull($response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertNull($response->getHeader('Access-Control-Max-Age'));
	}
	
	function testAllowedOrigin(){
		MockSettings::setSettings(Package::Name, 'CORS', array(
				"AllowOrigins"=>array('http://example.com'),
				"AllowMethods"=>array('GET'),
				"AllowHeaders"=>array(),
				"AllowCredentials"=>false,
				"MaxAge"=>1000
		));
		
		$client = new Client();
		
		// allowed method
		$request = $client->get(APP_DOMAIN.'/'.APP_PATH.'/test/route/simple');
		$request->setHeader('Origin', "http://example.com");
		$response = $client->send($request);
		
		$this->assertEquals('http://example.com',$response->getHeader('Access-Control-Allow-Origin'));
		$this->assertEquals('GET',$response->getHeader('Access-Control-Allow-Methods'));
		$this->assertEquals('',$response->getHeader('Access-Control-Allow-Headers'));
		$this->assertEquals('false',$response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertEquals('1000',$response->getHeader('Access-Control-Max-Age'));
		$this->assertEquals('',$response->getHeader('Access-Control-Expose-Headers'));
		
		// not allowed method
		$jsonContent = '{"name":"raaghu","children":[{"name":"shri"},{"name":"di"}]}';
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/json',array("Content-Type"=>"application/json","Content-Length"=>strlen($jsonContent)),$jsonContent);
		$request->setHeader('Origin', "http://example.com");
		$response = $client->send($request);
		
		$this->assertEquals('http://example.com',$response->getHeader('Access-Control-Allow-Origin'));
		$this->assertEquals('GET',$response->getHeader('Access-Control-Allow-Methods'));
		$this->assertEquals('',$response->getHeader('Access-Control-Allow-Headers'));
		$this->assertEquals('false',$response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertEquals('1000',$response->getHeader('Access-Control-Max-Age'));
		$this->assertEquals('Customer-Headers-For-CORS, location',$response->getHeader('Access-Control-Expose-Headers'));
		
		// for OPTIONS method (preflight request)
		$request = $client->options(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/json');
		$request->setHeader('Origin', "http://example.com");
		$request->setHeader('Access-Control-Request-Method', "POST");
		$response = $client->send($request);
		
		$this->assertEquals('http://example.com',$response->getHeader('Access-Control-Allow-Origin'));
		$this->assertEquals('GET',$response->getHeader('Access-Control-Allow-Methods'));
		$this->assertEquals('',$response->getHeader('Access-Control-Allow-Headers'));
		$this->assertEquals('false',$response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertEquals('1000',$response->getHeader('Access-Control-Max-Age'));
		$this->assertEquals('',$response->getHeader('Access-Control-Expose-Headers'));
		
		
		// with allowed headers , credentials and differrent max-age 
		MockSettings::setSettings(Package::Name, 'CORS', array(
				"AllowOrigins"=>array('http://example.com'),
				"AllowMethods"=>array('GET','POST'),
				"AllowHeaders"=>array('Content-Type','Php-Platform-Session-Cookie','Accept'),
				"AllowCredentials"=>'true',
				"MaxAge"=>500
		));
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/http-request/json',array("Content-Type"=>"application/json","Content-Length"=>strlen($jsonContent)),$jsonContent);
		$request->setHeader('Origin', "http://example.com");
		$response = $client->send($request);
		
		$this->assertEquals('http://example.com',$response->getHeader('Access-Control-Allow-Origin'));
		$this->assertEquals('GET, POST',$response->getHeader('Access-Control-Allow-Methods'));
		$this->assertEquals('Content-Type, Php-Platform-Session-Cookie, Accept',$response->getHeader('Access-Control-Allow-Headers'));
		$this->assertEquals('true',$response->getHeader('Access-Control-Allow-Credentials'));
		$this->assertEquals('500',$response->getHeader('Access-Control-Max-Age'));
		$this->assertEquals('Customer-Headers-For-CORS, location',$response->getHeader('Access-Control-Expose-Headers'));
				
	}
	
	
}