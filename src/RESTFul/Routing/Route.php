<?php

namespace PhpPlatform\RESTFul\Routing;

use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_4XX\MethodNotAllowed;
use PhpPlatform\Errors\Exceptions\Http\_4XX\NotFound;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;
use PhpPlatform\Errors\Exceptions\Http\HttpException;
use PhpPlatform\RESTFul\HTTPRequest;
use PhpPlatform\RESTFul\HTTPResponse;
use PhpPlatform\RESTFul\Package;
use PhpPlatform\Annotations\Annotation;
use PhpPlatform\Errors\Exceptions\Http\_4XX\Unauthorized;

class Route {
	
	static function run($uri = null){
		
		try{
			// find route
			$route = self::findRoute($uri);
			
			$class = $route["class"];
			$method = $route["method"];
			$pathParams = $route['pathParams'];
			
			$RESTServiceInterfaceName = 'PhpPlatform\RESTFul\RESTService';
			
			if(!in_array($RESTServiceInterfaceName,class_implements($class,true))){
				throw new InternalServerError("$class does not implement $RESTServiceInterfaceName");
			}
			
			$serviceClassAnnotations = Annotation::getAnnotations($class,null,null,$method);
			$serviceMethodAnnotations = $serviceClassAnnotations["methods"][$method];
			
			// validate recaptcha
			self::validateReCaptcha($serviceMethodAnnotations);
			
			$consumes = null;
			if(array_key_exists('Consumes', $serviceMethodAnnotations)){
				$consumes = $serviceMethodAnnotations['Consumes'];
			}
			$_SERVER['PLATFORM_SERVICE_CONSUMES'] = $consumes;
			
			// initialize HTTPRequest
			$httpRequest = HTTPRequest::getInstance();
			
			// invoke service
			$routeClassReflection = new \ReflectionClass($class);
			$routeMethodReflection = $routeClassReflection->getMethod($method);
			$routeMethodReflection->setAccessible(true);
			$routeInstance = $routeClassReflection->newInstance();
			$httpResponse = $routeMethodReflection->invokeArgs($routeInstance, array_merge(array($httpRequest),$pathParams));
			
			if($httpResponse instanceof HTTPResponse){
				// flush HTTPResponse
				$httpResponse->flush($httpRequest->getHeader('Accept'));
			}else{
				throw new InternalServerError("Service method does not return instance of PhpPlatform\RESTFul\HTTPResponse");
			}
			
		}catch (HttpException $h){
			$message = $h->getMessage();
			if($h instanceof InternalServerError){
				$message = "Internal Server Error";
			}
			(new HTTPResponse($h->getCode(),$message))->flush();
		}catch (\Exception $e){
			new InternalServerError($e->getMessage()); // for logging purposes
			(new HTTPResponse(500,'Internal Server Error'))->flush();
		}
				
	}
	
	/**
	 * This method finds the Service Class and Method for the given $uri 
	 * 
	 * @param string $uri , if null or not specified the defaulr value in $_SERVER['REQUEST_URI'] will be considered
	 * @throws NotFound
	 * @throws MethodNotAllowed
	 * @throws InternalServerError
	 * @return array of containing class, method and pathParams 
	 */
	private static function findRoute($uri = null){
		$method = $_SERVER['REQUEST_METHOD'];
		if(!isset($uri)){
			$uri = $_SERVER['REQUEST_URI'];
		}
		
		$urlPaths = array_diff(explode("/",$uri),array(""));
		
		$route = Settings::getSettings(Package::Name,"routes");
		
		$pathParams = array();
		foreach($urlPaths as $urlPath){
			if(!isset($route["children"])){
				$route["children"] = array();
			}
			
			if(array_key_exists(urlencode($urlPath),$route["children"])){
				$route = $route["children"][urlencode($urlPath)];
			}else if(array_key_exists("*",$route["children"])){
				$route = $route["children"]["*"];
				$pathParams[] = $urlPath;
			}else {
				throw new NotFound("Resource at " . implode("/", $urlPaths) . " Not Found");
			}
		}
		
		if(!isset($route["methods"])){
			throw new NotFound("Resource at " . implode("/", $urlPaths) . " Not Found");
		}else{
			$route = $route["methods"];
		}
		
		if(!isset($route[$method])){
			throw new MethodNotAllowed("$method method is not Allowed");
		}
		
		$route = $route[$method];
		
		// check for existenace of the class and method
		if(!(array_key_exists("class", $route) && 
			array_key_exists("method", $route) && 
			method_exists($route["class"], $route["method"]))){
				throw new InternalServerError("class and/or method does not exists for route at " . implode("/", $urlPaths));
		}
		$route["pathParams"] = $pathParams;
		
		return $route;
	}
	
	private static function validateReCaptcha($annotations){
		if(array_key_exists('ReCaptcha', $annotations)){
			$isReCaptchaEnabled = Settings::getSettings(Package::Name,"recaptcha.enable");
			if($isReCaptchaEnabled){
				// verify recaptcha
				$siteVerifyUrl = Settings::getSettings(Package::Name,"recaptcha.url");
				$siteSecret = Settings::getSettings(Package::Name,"recaptcha.secret");
				
				$ch = curl_init();
				
				curl_setopt($ch, CURLOPT_URL,$siteVerifyUrl);
				curl_setopt($ch, CURLOPT_POST, 1);
				curl_setopt($ch, CURLOPT_POSTFIELDS,
						"secret=$siteSecret&response=".$_SERVER['HTTP_PHP_PLATFORM_RECAPTCHA_RESPONSE']);
				
				// receive server response ...
				curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
				
				$server_output = curl_exec ($ch);
				
				curl_close ($ch);
				
				$server_output = json_decode($server_output,true);
				
				if(!(is_array($server_output) && $server_output['success'] === true)){
					// captcha not verified
					// https://developers.google.com/recaptcha/docs/verify
					throw new Unauthorized();
				}
				
			}
		}
	}
	
}