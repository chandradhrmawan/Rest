<?php

namespace App\Helper;

use Illuminate\Http\Request;

class FileUpload{

	public static function upload_file($file, $directory)
	{
		if (!file_exists($directory)){
            mkdir($directory, 0777);
        }
        $decoded_file = base64_decode($file['BASE64']); // decode the file
        $file = explode('/', $file['PATH']);
        $file = $file[count($file)-1];
        $file_dir = $directory.$file;
        try {
          file_put_contents($file_dir, $decoded_file);
          $response = true;
        } catch (Exception $e) {
          $response = $e->getMessage();
        }
        return ["response"=>$response, "link"=>$file_dir];
	}
}
