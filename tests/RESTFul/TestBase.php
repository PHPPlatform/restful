<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\Mock\Config\MockSettings;
use PhpPlatform\Config\SettingsCache;
use PhpPlatform\RESTFul\Routing\Build;

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
		
		Build::run();
		
		/**
		 * @desc HACK : same file is used by SettingsCache , tests are run from root user and apache is run from www-data , causing permission issues to access this shared cache file
		 */
		chmod(sys_get_temp_dir().'/settingscache236512233125', 0777);
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
	    parent::tearDown();
		// display error log if any on error
	    $errorlogFile = self::$errorLogDir.'/'. md5($this->getName());
	    if(file_exists($errorlogFile)){
	        if($this->hasFailed()){
	            echo PHP_EOL.file_get_contents($errorlogFile).PHP_EOL;
	        }
	        unlink($errorlogFile);
	    }}
	
	function clearErrorLog(){
		$errorlogFile = self::$errorLogDir.'/'. $this->getName();
		if(file_exists($errorlogFile)){
			unlink($errorlogFile);
		}
	}
	
	function assertContainsAndClearLog($message){
		$errorlogFile= self::$errorLogDir.'/'. $this->getName();
		$log = "";
		if(file_exists($errorlogFile)){
			$log = file_get_contents($errorlogFile);
		}
		$this->assertContains($message, $log);
		unlink($errorlogFile);
	}
	
	static function tearDownAfterClass(){
		// delete error log directory
		rmdir(self::$errorLogDir);
	}
	
}