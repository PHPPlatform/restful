<?php

namespace PhpPlatform\RESTFul;

use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_4XX\NotFound;
use PhpPlatform\Errors\Exceptions\Http\_4XX\MethodNotAllowed;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class Route {
	
	const THIS_PACKAGE_NAME = 'php-platform/restful';
	
	static function run($uri = null){
		
		$route = self::findRoute($uri);
		
		$class = $route["class"];
		$method = $route["method"];
		$pathParams = $route['pathParams'];
		
		$intRESTFulName = 'PhpPlatform\RESTFul\RESTService';
		
		if(!in_array($intRESTFulName,class_implements($class,true))){
			throw new InternalServerError("$class does not implement $intRESTFulName");
		}
		
		// initialize HTTPRequest 
		$httpRequest = HTTPRequest::getInstance();
		
		$routeClassReflection = new \ReflectionClass($class);
		$routeMethodReflection = $routeClassReflection->getMethod($method);
		$routeMethodReflection->setAccessible(true);
		$routeInstance = $routeClassReflection->newInstance();
		
		$httpResponse = $routeMethodReflection->invokeArgs($routeInstance, array_merge(array($httpRequest),$pathParams));
		
		$httpResponse->flush();
		
	}
	
	private static function findRoute($uri){
		$method = $_SERVER['REQUEST_METHOD'];
		if(!isset($uri)){
			$uri = $_SERVER['REQUEST_URI'];
		}
		
		$appPath = Settings::getSettings(self::THIS_PACKAGE_NAME,"appPath");
		
		if(strpos($appPath,"/") !== 0){ // prepend / if needed
			$appPath = "/".$appPath;
		}
		
		if(strpos($uri,"/") !== 0){ // prepend / if needed
			$uri = "/".$uri;
		}
		
		if(strpos($uri,$appPath) !== 0){
			throw new NotFound();
		}else{ // real uri is after webroot
			$uri = substr($uri,strlen($appPath));
		}
		
		$_SERVER['REQUEST_URI'] = $uri;
		$_SERVER['PLATFORM_APPLICATION_PATH'] = $appPath;
		
		$urlPaths = array_diff(explode("/",$uri),array(""));
		
		$route = Settings::getSettings(self::THIS_PACKAGE_NAME,"routes");
		
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
				throw new InternalServerError("Resource at " . implode("/", $urlPaths) . " Not Found");
		}
		$route["pathParams"] = $pathParams;
		
		return $route;
	}
	
	
}