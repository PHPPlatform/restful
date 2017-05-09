<?php

namespace PhpPlatform\RESTFul\Serialization;

use PhpPlatform\RESTFul\Serialization\Deserialize;
use PhpPlatform\Errors\Exceptions\Http\_4XX\BadRequest;
use PhpPlatform\Errors\Exceptions\Http\_5XX\InternalServerError;

class MultiPartFormToArrayDeserialization implements Deserialize {
	
	/**
	 * deserializes the $data
	 * @param string $data
	 *
	 * @return array PHP array with information about the uploaded files
	 * 			array['files']  array of files 
	 * 					  ['fileName'] name of file input 
	 *                               array of file information , refer http://php.net/manual/en/features.file-upload.post-method.php for more info
	 *               ['data']   form data
	 *        
	 */
	public static function deserialize($data) {
		
		$data = array();
		
		// actual content is the files
		// validate files for error
		foreach ($_FILES as $name =>$_FILE){
			switch ($_FILE['error']){
				case UPLOAD_ERR_INI_SIZE : throw new BadRequest("Exceeded File size for $name");
				case UPLOAD_ERR_FORM_SIZE : throw new BadRequest("Exceeded File size for $name");
				case UPLOAD_ERR_PARTIAL : throw new BadRequest("$name File Uploaded Partially");
				case UPLOAD_ERR_NO_FILE : throw new BadRequest("No File Uploaded for $name");
				
				case UPLOAD_ERR_NO_TMP_DIR : throw new InternalServerError("No Temporary Directory to write $name");
				case UPLOAD_ERR_CANT_WRITE : throw new InternalServerError("Cant write $name to Disk");
				case UPLOAD_ERR_EXTENSION : throw new InternalServerError("PHP extention caused the error to upload $name");
			}
		}
		$data['files'] = $_FILES;
		$_FILES = array();
		
		// if data present along with file , populate into object
		$data['data'] = $_POST;
		$_POST = array();
		
		return $data;
	}
}