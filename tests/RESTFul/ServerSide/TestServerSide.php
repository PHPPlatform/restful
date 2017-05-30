<?php

namespace PhpPlatform\Tests\RESTFul\ServerSide;

use PhpPlatform\Tests\RestFul\TestBase;

/**
 * Coverage for this repo is generated in a differrent way other than passing --coverage-clover switch to phpunit 
 * 
 * Every test case generates a coverage as php file in a temp directory and
 * in the end all these coverages are combinded to generate the aggregated coverage.xml
 *
 */
abstract class TestServerSide extends TestBase {
	
	private static $coverage = null;
	
	static function setUpBeforeClass(){
		parent::setUpBeforeClass();
		
		// start coverage
		if(defined('APP_COVERAGE') && APP_COVERAGE == "true"){
			$filter = new \PHP_CodeCoverage_Filter();
			$filter->addDirectoryToWhitelist(dirname(__FILE__).'/../../../src');
			
			self::$coverage = new \PHP_CodeCoverage(null,$filter);
			self::$coverage->start('testRESTful');
		}
	}
	
	static function tearDownAfterClass(){
		
		if(self::$coverage instanceof \PHP_CodeCoverage){
			self::$coverage->stop();
			$writer = new \PHP_CodeCoverage_Report_PHP();
			$coverageFileName = md5(get_called_class()).'.php';
			$writer->process(self::$coverage, COVERAGE_DIR.'/'.$coverageFileName);
		}
		
	}
	
}