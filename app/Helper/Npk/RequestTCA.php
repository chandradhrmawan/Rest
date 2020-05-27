<?php

namespace App\Helper\Npk;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use Carbon\Carbon;
use PHPExcel_IOFactory;

class RequestTCA{

	public static function readExcelImportNoPol($input){
		$path		  = $input['file_path'];
		$decoded_file = base64_decode($input['file_encode']); // decode the file
		$file 		  = explode('/', $path);
        $file 		  = Carbon::now()->format('mdY_h_i_s').$file[count($file)-1];
        $file_dir 	  = '/'.$file;
		try {
          file_put_contents($file_dir, $decoded_file);
          $response = true;
        } catch (Exception $e) {
          $response = $e->getMessage();
        }
        $objPHPExcel = PHPExcel_IOFactory::load($file_dir);
        $sheet = $objPHPExcel->getSheet(0);
        $highestRow = $sheet->getHighestRow();
		// $highestColumn = $sheet->getHighestColumn();
        $responseData = [];
        for ($row = 2; $row <= $highestRow; $row++){
        // $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
        	$responseData[] = ["no_polisi" => $sheet->getCell('A'.$row)->getValue()];
        }
        unlink($file_dir);
        return [
        	'Success' => true,
        	'result' => 'Success, read file!',
        	'req_no' => $responseData
        ];
	}
}