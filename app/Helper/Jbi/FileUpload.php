<?php

namespace App\Helper\Jbi;

use Illuminate\Http\Request;

class FileUpload{

	public static function upload_file($file, $directory, $table = null, $id = null)
	{
		if (!file_exists($directory)){
            mkdir($directory, 0777);
        }
        $path         = $file['PATH'];
        if (!empty($table) and !empty($id)) {
          $path         = $table."_".$id."_".$path;
        }
        $decoded_file = base64_decode($file['BASE64']); // decode the file
        $file 				= explode('/', $path);
        $file 				= $file[count($file)-1];
        $file_dir 		= $directory.$file;
        try {
          file_put_contents($file_dir, $decoded_file);
          $response = true;
        } catch (Exception $e) {
          $response = $e->getMessage();
        }
        return ["response"=>$response, "link"=>$file_dir];
	}
}
