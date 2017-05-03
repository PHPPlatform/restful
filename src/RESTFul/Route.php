<?php

namespace PhpPlatform\RESTFul;

use PhpPlatform\Config\Settings;
use PhpPlatform\Errors\Exceptions\Http\_4XX\NotFound;
use PhpPlatform\Errors\Exceptions\Http\_4XX\MethodNotAllowed;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class Route {
	
	const THIS_PACKAGE_NAME = 'php-platform/restful';
	
	static function run($url = null){
		$method = $_SERVER['REQUEST_METHOD'];
		if(!isset($url)){
			$url = $_SERVER['REQUEST_URI'];
		}
		
		$webroot = Settings::getSettings(self::THIS_PACKAGE_NAME,"webroot");
		
		if(strpos($webroot,"/") !== 0){ // prepend / if needed
			$webroot = "/".$webroot;
		}
		
		if(strpos($url,"/") !== 0){ // prepend / if needed
			$url = "/".$url;
		}
		
		if(strpos($url,$webroot) !== 0){
			throw new NotFound();
		}else{ // real url is after webroot
			$url = substr($url,strlen($webroot));
		}
		
		$urlPaths = array_diff(explode("/",$url),array(""));
		
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
		
		$class = $route["class"];
		$method = $route["method"];
		
		$intRESTFulName = 'icircle\restful\RESTFul';
		
		if(!in_array($intRESTFulName,class_implements($class,true))){
			throw new InternalServerError("$class does not implement $intRESTFulName");
		}
		
		try{
			$reflectionMethod = new \ReflectionMethod($class,$method);
			$reflectionClass  = new \ReflectionClass($class);
		}catch (\ReflectionException $re){
			throw new InternalServerError("Resource at " . implode("/", $urlPaths) . " Not Found");
		}
		
		$classObj = $reflectionClass->newInstance();
		
	}
}