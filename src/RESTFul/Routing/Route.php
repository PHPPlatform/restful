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
use PhpPlatform\Errors\Exceptions\Persistence\NoAccessException;
use PhpPlatform\Errors\Exceptions\Persistence\DataNotFoundException;
use PhpPlatform\Errors\Exceptions\Application\ProgrammingError;
use PhpPlatform\Errors\Exceptions\PlatformException;

class Route {
	
	static function run($uri = null){
		
		try{
			
			// find route
			$route = self::findRoute($uri);
			
			$class = $route["class"];
			$method = $route["method"];
			$pathParams = $route['pathParams'];
			$corsAccessControl = $route['corsAccessControl'];
			
			//CORS authentication
			if(array_key_exists('HTTP_ORIGIN', $_SERVER)){
				$origin = $_SERVER['HTTP_ORIGIN'];
			}
			if(isset($origin)){ // origin header is set
				$origin = trim($origin,'/');
				$requestDomain = "http".(isset($_SERVER['HTTPS'])?"s":"")."://".$_SERVER['HTTP_HOST'].($_SERVER['SERVER_PORT'] == 80 ?"":":".$_SERVER['SERVER_PORT']);
				if(strtolower($origin) != strtolower($requestDomain)){ // origin is not same as the requested resource
					if(in_array($origin, $corsAccessControl['AllowOrigins'])){ // origin is allowed
						$corsAccessControl['AllowOrigin'] = $origin;
					}else{ // origin is not allowed
						throw new Unauthorized("CORS ERROR : $origin is not a allowed origin");
					}
				}
			}
			
			$RESTServiceInterfaceName = 'PhpPlatform\RESTFul\RESTService';
			
			if(!in_array($RESTServiceInterfaceName,class_implements($class,true))){
				throw new ProgrammingError("$class does not implement $RESTServiceInterfaceName");
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
				self::addCorsHeaders($httpResponse, $corsAccessControl);
				// flush HTTPResponse
				$httpResponse->flush($httpRequest->getHeader('Accept'));
			}else{
				throw new ProgrammingError("Service method does not return instance of PhpPlatform\RESTFul\HTTPResponse");
			}
			
		}catch (HttpException $h){
			$body = $h->getBody();
			if($h instanceof InternalServerError){
				new ProgrammingError($body); // for logging purpose
				$body = null;
			}
			$httpResponse = new HTTPResponse($h->getCode(),$h->getMessage(),$body);
		}catch (DataNotFoundException $e){ // DataNotFoundException becomes 'Not Found' response
			new NotFound(); // for logging purpose
			$httpResponse = new HTTPResponse(404,'Not Found');
		}catch (NoAccessException $e){ // NoAccessException becomes Unauthorized response 
			new Unauthorized(); // for logging purpose
			$httpResponse = new HTTPResponse(401,'Unauthorized');
		}catch (\Exception $e){
			if(!($e instanceof PlatformException)){
				new ProgrammingError($e->getMessage()); // for logging purposes
			}
			$httpResponse = new HTTPResponse(500,'Internal Server Error');
		}
		self::addCorsHeaders($httpResponse, $corsAccessControl);
		$httpResponse->flush();
	}
	
	/**
	 * This method finds the Service Class and Method for the given $uri
	 *
	 * @param string $uri , if null or not specified the defaulr value in $_SERVER['REQUEST_URI'] will be considered
	 * @throws NotFound
	 * @throws MethodNotAllowed
	 * @throws InternalServerError
	 * @return array of containing class, method, pathParams and corsAcessControl Parameters
	 */
	private static function findRoute($uri = null){
		$method = $_SERVER['REQUEST_METHOD'];
		if(!isset($uri)){
			$uri = $_SERVER['REQUEST_URI'];
		}
		
		$urlPaths = array_diff(explode("/",$uri),array(""));
		
		$route = Settings::getSettings(Package::Name,"routes");
		
		// global cors headers
		$corsAccessControl = Settings::getSettings(Package::Name,"CORS");
		
		$pathParams = array();
		foreach($urlPaths as $urlPath){
			if(!isset($route["children"])){
				$route["children"] = array();
			}
			
			if(array_key_exists("CORS",$route)){
				$corsAccessControl = self::mergeCorsHeaders($corsAccessControl,$route["CORS"]);
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
			$validMethod = false;
			if($method == "OPTIONS"){
				// for OPTIONS return OK , if Access-Control-Request-Method is found in $route configurations
				$_method = $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'];
				if(array_key_exists($_method, $route)){
					// copy cors headers from original method
					$route[$method] = array(
							"class"=>'PhpPlatform\RESTFul\CORS\PreFlight',
							"method"=>'preFlight'
					);
					if(isset($route[$_method]["CORS"])){
						$route[$method]['CORS'] = $route[$_method]["CORS"];
					}
					$validMethod = true;
				}
			}
			if(!$validMethod){
				throw new MethodNotAllowed("$method method is not Allowed");
			}
		}
		
		$route = $route[$method];
		if(array_key_exists("CORS",$route)){
			$corsAccessControl = self::mergeCorsHeaders($corsAccessControl,$route["CORS"]);
		}
		
		// check for existenace of the class and method
		if(!(array_key_exists("class", $route) &&
				array_key_exists("method", $route) &&
				method_exists($route["class"], $route["method"]))){
					throw new ProgrammingError("class and/or method does not exists for route at " . implode("/", $urlPaths));
		}
		$route["pathParams"] = $pathParams;
		$route["corsAccessControl"] = $corsAccessControl;
		
		return $route;
	}
	
	private static function mergeCorsHeaders($headers1,$headers2){
		
		foreach (array('AllowOrigins','AllowMethods','AllowHeaders') as $headerName){
			if(array_key_exists($headerName, $headers2) && is_array($headers2[$headerName])){
				if(!array_key_exists($headerName,$headers1)){
					$headers1[$headerName] = array();
				}
				foreach ($headers2[$headerName] as $origin){
					if(strpos($origin, "!") === 0){
						$index = array_search(substr($origin, 1), $headers1[$headerName]);
						if($index !== false){
							unset($headers1[$headerName][$index]);
						}
					}else if(!in_array($origin, $headers1[$headerName])){
						$headers1[$headerName][] = $origin;
					}
				}
			}
		}
		
		if(array_key_exists('AllowCredentials', $headers2)){
			$headers1['AllowCredentials'] = $headers2['AllowCredentials'];
		}
		if(array_key_exists('MaxAge', $headers2)){
			$headers1['MaxAge'] = $headers2['MaxAge'];
		}
		return $headers1;
		
	}
	
	/**
	 *
	 * @param HTTPResponse $httpResponse
	 * @param array $corsAccessControl
	 */
	private static function addCorsHeaders($httpResponse,$corsAccessControl = null){
		
		if($httpResponse instanceof HTTPResponse && is_array($corsAccessControl) && isset($corsAccessControl['AllowOrigin'])){
			// find existing headers to set them as Access-Control-Expose-Headers
			$existingHeadersRP = new \ReflectionProperty(get_class($httpResponse), 'headers');
			$existingHeadersRP->setAccessible(true);
			$existingHeaders = $existingHeadersRP->getValue($httpResponse);
			
			$existingHeaderNames = array_keys($existingHeaders);
			
			// Access-Control-Allow-Origin
			$httpResponse->setHeader('Access-Control-Allow-Origin',      $corsAccessControl['AllowOrigin']);
			
			// Access-Control-Allow-Credentials
			if(is_bool($corsAccessControl['AllowCredentials'])){
				$corsAccessControl['AllowCredentials'] = $corsAccessControl['AllowCredentials']?'true':'false';
			}
			$httpResponse->setHeader('Access-Control-Allow-Credentials', $corsAccessControl['AllowCredentials']);
			
			// Access-Control-Max-Age
			$httpResponse->setHeader('Access-Control-Max-Age',           $corsAccessControl['MaxAge']);
			
			// Access-Control-Allow-Headers
			if(is_array($corsAccessControl['AllowHeaders'])){
				$httpResponse->setHeader('Access-Control-Allow-Headers',     implode(", ",array_unique($corsAccessControl['AllowHeaders'])));
			}
			
			// Access-Control-Allow-Methods
			if(is_array($corsAccessControl['AllowMethods'])){
				$httpResponse->setHeader('Access-Control-Allow-Methods',     implode(", ",array_unique($corsAccessControl['AllowMethods'])));
			}
			
			// Access-Control-Expose-Headers
			$httpResponse->setHeader('Access-Control-Expose-Headers',    implode(", ",$existingHeaderNames));
		}
		
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