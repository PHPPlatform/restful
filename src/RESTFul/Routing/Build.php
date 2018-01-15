<?php

namespace PhpPlatform\RESTFul\Routing;

use Composer\Autoload\ClassLoader;
use PhpPlatform\RESTFul\Package;
use PhpPlatform\Config\Settings;
use PhpPlatform\Annotations\Annotation;
use PhpPlatform\Config\SettingsCache;
use PhpPlatform\RESTFul\RESTService;

class Build{

    private static $routes = array();
    private static $excludedNamespaces = array(
    		"Symfony\\"
    );

    static function run(){

        $classLoaderReflection = new \ReflectionClass(new ClassLoader());
        $classLoaderDir = dirname($classLoaderReflection->getFileName());

        $psr4 = array();
        if(is_file($classLoaderDir.'/autoload_psr4.php')){
            $psr4 = require $classLoaderDir.'/autoload_psr4.php';
        }

        try{
            self::$routes = Settings::getSettings(Package::Name,"routes");
        }catch (\Exception $e){
            self::$routes = array();
        }
        try{
            foreach($psr4 as $namespace=>$paths){
                foreach($paths as $path){
                    self::processPSR4Dir($namespace,$path);
                }
            }
            self::writeConfig();
        }catch (\Exception $e){
            throw $e;
        }

    }
    
    private static function writeConfig(){
    	$configFilePath = Settings::getConfigFile(Package::Name);
    	$config = json_decode(file_get_contents($configFilePath),true);
    	
    	if($config["routes"] != self::$routes){
    		$config["routes"] = self::$routes;
    		$configJson = json_encode($config,JSON_PRETTY_PRINT|JSON_UNESCAPED_SLASHES);
    		file_put_contents($configFilePath, $configJson);
    		SettingsCache::getInstance()->reset();
    	}
    }

    private static function processPSR4Dir($namespace,$path){
    	foreach (self::$excludedNamespaces as $excludedNameSpace){
    		if(strpos($namespace, $excludedNameSpace) === 0){
    			// this namespace is excluded
    			return;
    		}
    	}
        if(is_file($path)){
            $className = $namespace;
            // if className ends in \ , remove the ending \
            if(strrpos($className,"\\") === strlen($className) - 1){
                $className = substr($className,0,strlen($className) - 1);
            }

            // if className ends in .php , remove the ending .php
            if(strrpos($className,".php") === strlen($className) - 4){
                $className = substr($className,0,strlen($className) - 4);
            }

            $fileNameSpace = self::extract_namespace($path);
            $classNameSpace = substr($className,0,strrpos($className,"\\"));
            if($classNameSpace === $fileNameSpace && class_exists($className,true)){
                self::processClass($className);
            }
        }else if(is_dir($path)){
            $children = array_diff(scandir($path),array('.','..'));
            foreach($children as $child){
                self::processPSR4Dir($namespace.$child."\\",$path.'/'.$child);
            }
        }
    }

    private static function processClass($className){

        $intRESTFulName = 'PhpPlatform\RESTFul\RESTService';
        if(in_array($intRESTFulName,class_implements($className,true))){

            $pathAnnotation = "Path";
            $GETVerbAnnotation = "GET";
            $POSTVerbAnnotation = "POST";
            $PUTVerbAnnotation = "PUT";
            $HEADVerbAnnotation = "HEAD";
            $DELETEVerbAnnotation = "DELETE";
            $PATCHVerbAnnotation = "PATCH";


            $annotations = Annotation::getAnnotations($className);
            $classUrlPath = "";
            if(array_key_exists($pathAnnotation,$annotations["class"])){
                $classUrlPath = $annotations["class"][$pathAnnotation];
            }
            $classUrlPath = self::normalizeUrlPath($classUrlPath);

            $methodsAnnotations = $annotations["methods"];
            
            foreach($methodsAnnotations as $methodName=>$methodAnnotations){

                $methodHTTPVerbs = array();
                if(array_key_exists($GETVerbAnnotation,$methodAnnotations)){
                    $methodHTTPVerbs[] = $GETVerbAnnotation;
                }
                if(array_key_exists($POSTVerbAnnotation,$methodAnnotations)){
                    $methodHTTPVerbs[] = $POSTVerbAnnotation;
                }
                if(array_key_exists($PUTVerbAnnotation,$methodAnnotations)){
                    $methodHTTPVerbs[] = $PUTVerbAnnotation;
                }
                if(array_key_exists($PATCHVerbAnnotation,$methodAnnotations)){
                	$methodHTTPVerbs[] = $PATCHVerbAnnotation;
                }
                if(array_key_exists($DELETEVerbAnnotation,$methodAnnotations)){
                    $methodHTTPVerbs[] = $DELETEVerbAnnotation;
                }
                if(count($methodHTTPVerbs) == 0){
                    $methodHTTPVerbs[] = $GETVerbAnnotation;
                }
                if(in_array($GETVerbAnnotation,$methodHTTPVerbs) && !in_array($HEADVerbAnnotation,$methodHTTPVerbs)){
                    // for GET without HEAD , HEAD will be added
                    $methodHTTPVerbs[] = $HEADVerbAnnotation;

                }elseif(!in_array($GETVerbAnnotation,$methodHTTPVerbs) && in_array($HEADVerbAnnotation,$methodHTTPVerbs)){
                    // for HEAD without GET , throw exception
                    throw new \Exception("HEAD method is defined with out defining GET method");
                }

                if(array_key_exists($pathAnnotation,$methodAnnotations)){
                	$methodUrlPath = $methodAnnotations[$pathAnnotation];
                    $methodUrlPath = self::normalizeUrlPath($methodUrlPath);

                    $completePath = self::normalizeUrlPath($classUrlPath.$methodUrlPath);
                    $paths = array_diff(explode("/",$completePath),array(""));

                    $curRoute = &self::$routes;
                    foreach($paths as $path){
                        if(!array_key_exists("children",$curRoute)){
                            $curRoute["children"] = array();
                        }
                        $curRoute = &$curRoute["children"];

                        // if $path is enclosed in {}, then its a path variable
                        if(strpos($path,"{") === 0 && strrpos($path,"}") === strlen($path)-1){
                            $path = "*";
                        }else{
                            $path = urlencode($path);
                        }
                        if(!array_key_exists($path,$curRoute)){
                            $curRoute[$path] = array();
                        }
                        $curRoute = &$curRoute[$path];
                    }

                    if(!array_key_exists("methods",$curRoute)){
                        $curRoute["methods"] = array();
                    }
                    $curRoute = &$curRoute["methods"];

                    foreach($methodHTTPVerbs as $methodHTTPVerb){
                        if(array_key_exists($methodHTTPVerb,$curRoute)){
                            $existingClass = $curRoute[$methodHTTPVerb]["class"];
                            $existingMethod = $curRoute[$methodHTTPVerb]["method"];

                            if($existingClass.":".$existingMethod != $className.":".$methodName){
                                if(is_a($className,$existingClass,true)){
                                    $curRoute[$methodHTTPVerb] = array("class"=>$className,"method"=>$methodName);
                                }else if(is_a($existingClass,$className,true)){
                                    // dont do anything
                                }else{
                                    throw new \Exception("Ambiguous Path definition { $existingClass : $existingMethod } and { $className : $methodName }");
                                }
                            }
                        }else{
                            $curRoute[$methodHTTPVerb] = array("class"=>$className,"method"=>$methodName);
                        }
                    }
                }
            }
        }
    }

    private static function normalizeUrlPath($urlPath){
        $urlPath = str_replace("\\","/",$urlPath);
        if(strpos($urlPath,"/") !== 0){
            $urlPath = "/".$urlPath;
        }
        if(strlen($urlPath) > 1 && strrpos($urlPath,"/") === strlen($urlPath)-1){
            $urlPath = substr($urlPath,0,strlen($urlPath) -1);
        }
        return $urlPath;
    }

    private static function extract_namespace($file) {
        $ns = NULL;
        $handle = fopen($file, "r");
        if ($handle) {
            while (($line = fgets($handle)) !== false) {
                if (strpos(trim($line), 'namespace') === 0) {
                    $parts = explode(' ', $line);
                    $ns = rtrim(trim($parts[1]), ';');
                    break;
                }
            }
            fclose($handle);
        }
        return $ns;
    }

}