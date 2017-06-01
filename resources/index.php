<?php

ob_start();

require_once dirname(__FILE__).'/vendor/autoload.php';

PhpPlatform\Errors\ErrorHandler::handleError();

if (get_magic_quotes_gpc()) {
    $process = array(&$_GET, &$_POST, &$_COOKIE, &$_REQUEST);
    while (list($key, $val) = each($process)) {
        foreach ($val as $k => $v) {
            unset($process[$key][$k]);
            if (is_array($v)) {
                $process[$key][stripslashes($k)] = $v;
                $process[] = &$process[$key][stripslashes($k)];
            } else {
                $process[$key][stripslashes($k)] = stripslashes($v);
            }
        }
    }
    unset($process);
}

$requestUri = $_REQUEST["__route__"];
$requestUriActual = $_SERVER['REQUEST_URI'];
$positionOfQueryString = strpos($requestUriActual, '?');
if($positionOfQueryString !== false){
	$requestUriActual = substr($requestUriActual, 0,$positionOfQueryString);
}
$appPathEnd = strpos($requestUriActual, $requestUri);
if($appPathEnd !== false){
	$appPath = substr($requestUriActual, 0, $appPathEnd);
}
$_SERVER['REQUEST_URI'] = $requestUri;
$_SERVER['PLATFORM_APPLICATION_PATH'] = $appPath;
PhpPlatform\RESTFul\Routing\Route::run($requestUri);

?>