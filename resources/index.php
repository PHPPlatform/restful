<?php

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
$_SERVER['REQUEST_URI'] == $requestUri;
PhpPlatform\RESTFul\Routing\Route::run($requestUri);

?>