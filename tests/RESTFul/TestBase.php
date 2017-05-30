<?php

namespace PhpPlatform\Tests\RestFul;

use PhpPlatform\Mock\Config\MockSettings;
use PhpPlatform\Config\SettingsCache;

abstract class TestBase extends \PHPUnit_Framework_TestCase {
	
	private static $errorLogDir = null;
	
	static function setUpBeforeClass(){
		parent::setUpBeforeClass();
		
		// create a temporary error log directory
		$errorLogDir = sys_get_temp_dir().'/php-platform/restful/errors/'.microtime(true);
		mkdir($errorLogDir,0777,true);
		chmod($errorLogDir, 0777);
		
		self::$errorLogDir = $errorLogDir;
		
		// clear caches
		SettingsCache::getInstance()->reset();
		
		MockSettings::setSettings("php-platform/restful", "webroot", APP_PATH);
		
	}
	
	function setUp(){
		$errorlogFile = self::$errorLogDir.'/'. $this->getName();
		
		// create an temporary error log
		MockSettings::setSettings('php-platform/errors', 'traces', array(
				"Persistence"=>$errorlogFile,
				"Application"=>$errorlogFile,
				"Http"=>$errorlogFile,
				"System"=>$errorlogFile
		));
	}
	
	function tearDown(){
		// display error log if any
		$errorlogFile = self::$errorLogDir.'/'. $this->getName();
		if(file_exists($errorlogFile)){
			echo PHP_EOL.file_get_contents($errorlogFile).PHP_EOL;
			unlink($errorlogFile);
		}
	}
	
	function clearErrorLog(){
		$errorlogFile = self::$errorLogDir.'/'. $this->getName();
		if(file_exists($errorlogFile)){
			unlink($errorlogFile);
		}
	}
	
	static function tearDownAfterClass(){
		// delete error log directory
		rmdir(self::$errorLogDir);
	}
	
}