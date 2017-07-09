<?php

namespace PhpPlatform\Tests\RESTFul\ClientSide;

use PhpPlatform\Mock\Config\MockSettings;
use Guzzle\Http\Client;
use PhpPlatform\Tests\RESTFul\TestBase;
use PhpPlatform\RESTFul\Package;
use Guzzle\Http\Exception\BadResponseException;

class TestReCaptcha extends TestBase {
	
	function testReCaptcha(){
		
		MockSettings::setSettings(Package::Name, 'recaptcha.enable', true);
		
		// mock verifysite url
		MockSettings::setSettings(Package::Name, 'recaptcha.url', APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/mock-test-site');
		
		// test all OK
		$client = new Client();
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/test');
		$request->setHeader('Php-Platform-Recaptcha-Response', 'qbnhdytteoanbkjyD4f7oesnhmdgbtDmjhaldhCRttyugr3m268nkjduyrj');
		$response = $client->send($request);
		
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("", $response->getBody(true));
		
		//test without recaptcha header
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/test');
		try{
			$response = $client->send($request);
		}catch (BadResponseException $e){
			$response = $e->getResponse();
			$this->clearErrorLog();
		}
		$this->assertEquals(401, $response->getStatusCode());
		$this->assertEquals("Unauthorized", $response->getReasonPhrase());
		$this->assertEquals("", $response->getBody());
		
		//test with wrong recaptcha header
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/test');
		$request->setHeader('Php-Platform-Recaptcha-Response', 'qbnhdytteoanbkjyD4f7oesnhmdshgtenmlkl');
		try{
			$response = $client->send($request);
		}catch (BadResponseException $e){
			$response = $e->getResponse();
			$this->clearErrorLog();
		}
		$this->assertEquals(401, $response->getStatusCode());
		$this->assertEquals("Unauthorized", $response->getReasonPhrase());
		$this->assertEquals("", $response->getBody());
		
		// test the service without recaptcha
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/test-norecaptcha-service');
		$response = $client->send($request);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getReasonPhrase());
		$this->assertEquals("", $response->getBody());
		
		// test with recaptcha disabled
		MockSettings::setSettings(Package::Name, 'recaptcha.enable', false);
		$request = $client->post(APP_DOMAIN.'/'.APP_PATH.'/test/recaptcha/test');
		$response = $client->send($request);
		$this->assertEquals(200, $response->getStatusCode());
		$this->assertEquals("OK", $response->getReasonPhrase());
		$this->assertEquals("", $response->getBody());
		
	}

}