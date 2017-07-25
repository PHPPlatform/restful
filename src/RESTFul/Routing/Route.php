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
use PhpPlatform\Errors\Exceptions\Http\_2XX\OK;

class Route {
	
	private static $headers = array();
	
	static function run($uri = null){
		
		try{
			//cors authentication
			if(array_key_exists('HTTP_ORIGIN', $_SERVER)){
				$origin = $_SERVER['HTTP_ORIGIN'];
			}elseif(array_key_exists('HTTP_REFERER', $_SERVER)){
				$origin = $_SERVER['HTTP_REFERER'];
			}
			if(isset($origin)){
				$origin = trim($origin,'/');
				$requestDomain = "http".(isset($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] == 80 ?"":":".$_SERVER['SERVER_PORT']);
				if(strtolower($origin) != strtolower($requestDomain)){
					$allowedOrigins = Settings::getSettings(Package::Name,'CORS.AllowedOrigins');
					if(in_array($origin, $allowedOrigins)){
						self::$headers['Access-Control-Allow-Origin'] = $origin;
						self::$headers['Access-Control-Allow-Methods'] = implode(", ", Settings::getSettings(Package::Name,'CORS.AllowedMethods'));
						self::$headers['Access-Control-Allow-Headers'] = implode(", ", Settings::getSettings(Package::Name,'CORS.AllowedHeaders'));
						
						$allowCredentails = Settings::getSettings(Package::Name,'CORS.AllowCredentials');
						if(is_bool($allowCredentails)){
							$allowCredentails = $allowCredentails ? 'true':'false';
						}
						self::$headers['Access-Control-Allow-Credentials'] = $allowCredentails;
						self::$headers['Access-Control-Max-Age'] = Settings::getSettings(Package::Name,'CORS.MaxAge');
					}else{
						throw new Unauthorized("CORS ERROR : $origin is not a allowed origin");
					}					
				}
			}
			
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
				foreach (self::$headers as $name=>$value){
					$httpResponse->setHeader($name, $value);
				}
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
			$httpResponse = new HTTPResponse($h->getCode(),$message);
		}catch (\Exception $e){
			new InternalServerError($e->getMessage()); // for logging purposes
			$httpResponse = new HTTPResponse(500,'Internal Server Error');
		}
		foreach (self::$headers as $name=>$value){
			$httpResponse->setHeader($name, $value);
		}
		$httpResponse->flush();
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
			if($method == "OPTIONS"){
				// for OPTIONS return OK , if Access-Control-Request-Method is found in $route configurations
				$_method = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
				if(array_key_exists($_method, $route)){
					throw new OK();
				}else{
					throw new MethodNotAllowed("CORS ERROR, $_method is not Allowed");
				}
			}else{
				throw new MethodNotAllowed("$method method is not Allowed");
			}
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