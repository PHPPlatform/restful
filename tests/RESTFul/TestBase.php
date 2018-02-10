<?php

namespace PhpPlatform\Tests\RESTFul;

use PhpPlatform\Mock\Config\MockSettings;
use PhpPlatform\Config\SettingsCache;
use PhpPlatform\RESTFul\Routing\Build;

abstract class TestBase extends \PHPUnit_Framework_TestCase {
	
	private static $errorLogDir = null;
	private static $coverage = null;
	
	static function setUpBeforeClass(){
		parent::setUpBeforeClass();
		
		// generate coverage for tests run in php-cli process
		if(defined('COVERAGE_DIR')){
			$filter = new \PHP_CodeCoverage_Filter();
			$filter->addDirectoryToWhitelist(dirname(__FILE__).'/../../src');
			self::$coverage = new \PHP_CodeCoverage(null,$filter);
			self::$coverage->start('testRESTful');
		}
		
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
		
		if(defined('COVERAGE_DIR')){
			self::$coverage->stop();
			$writer = new \PHP_CodeCoverage_Report_PHP();
			$coverageFileName = md5(microtime()).'.php';
			$writer->process(self::$coverage, COVERAGE_DIR.'/'.$coverageFileName);
		}
	}
	
}