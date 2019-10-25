<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use App\Helper\FileUpload;

class BillingEngine{

	public static function storeProfileTariff($input){
        $head       = $input['header_set'];
        $detil      = $input['detil'];
        $datenow    = Carbon::now()->format('Y-m-d');

		foreach ($detil as $list) {
          if (!empty($list['ALAT'])) {
            $each       = explode('/', $list['ALAT']);
            $subisocode = \DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where([
              "EQUIPMENT_TYPE_ID" => $each[0],
              "EQUIPMENT_UNIT" => $each[1]
            ])->get();
            if (count($subisocode) == 0) {
              return response()->json(["result" => "Fail, iso code not found", "ALAT" => $list]);
            }
          }

          if (!empty($list['BARANG'])) {
            $each       = explode('/', $list['BARANG']);
            $isocode    = \DB::connection('mdm')->table('TM_ISO_COMMODITY')->where("PACKAGE_ID", $each[0]);
            if (empty($each[1]) or $each[1] == "null") {
            	$isocode->whereNull('COMMODITY_ID');
            }else{
            	$isocode->where('COMMODITY_ID',$each[1]);
            }
            if (empty($each[2]) or $each[2] == "null") {
            	$isocode->whereNull('COMMODITY_UNIT_ID');
            }else{
            	$isocode->where('COMMODITY_UNIT_ID',$each[2]);
            }
            $isocode    = $isocode->get();
            if (count($isocode) == 0) {
              return response()->json(["result" => "Fail, iso code not found", "BARANG" => $list]);
            }
          }

          elseif (!empty($list['KONTAINER'])) {
            $each           = explode('/', $list['KONTAINER']);
            $isocode        = \DB::connection('mdm')->table('TM_ISO_CONT')->where([
              "CONT_SIZE"   => $each[0],
              "CONT_TYPE"   => $each[1],
              "CONT_STATUS" => $each[2]
            ])->get();
            if (count($isocode) == 0) {
              return response()->json(["result" => "Fail, iso code not found", "KONTAINER" => $list]);
            }
          }
        }

        // store head

          if(empty($head['TARIFF_ID'])){
            $headS    = new TxProfileTariffHdr;
          }else{
            $headS    = TxProfileTariffHdr::find($head['TARIFF_ID']);
          }

          $headS->tariff_type   = $head['TARIFF_TYPE'];
          $headS->tariff_start  = \DB::raw("TO_DATE('".$head['TARIFF_START']."', 'YYYY-MM-DD')");
          $headS->tariff_end    = \DB::raw("TO_DATE('".$head['TARIFF_END']."', 'YYYY-MM-DD')");
          $headS->tariff_no     = $head['TARIFF_NO'];
          $headS->tariff_status = $head['TARIFF_STATUS'];
          $headS->service_code  = $head['SERVICE_CODE'];
          $headS->branch_id     = 10; // SESSION LOGIN
          $headS->created_by    = 1; // SESSION LOGIN
          $headS->created_date  = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
          $headS->save();
        // store head

        // store detil
          TsTariff::where('TARIFF_PROF_HDR_ID',$headS->tariff_id)->delete();
          foreach ($detil as $list) {
            $isocode    = "";
            $subisocode = "";
            if (!empty($list['ALAT'])) {
              // Get Data with / separater
              $each       = explode('/', $list['ALAT']);
              $alatisocode = \DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where([
                "EQUIPMENT_TYPE_ID" => $each[0],
                "EQUIPMENT_UNIT" => $each[1]
              ])->get();
              $alatisocode = $alatisocode[0]->iso_code;
              $isocode = $alatisocode;
            }

            if (!empty($list['BARANG'])) {
              $each       = explode('/', $list['BARANG']);
              $itemisocode    = \DB::connection('mdm')->table('TM_ISO_COMMODITY')->where("PACKAGE_ID", $each[0]);
              if (empty($each[1]) or $each[1] == "null") {
              	$itemisocode->whereNull('COMMODITY_ID');
              }else{
              	$itemisocode->where('COMMODITY_ID',$each[1]);
              }
              if (empty($each[2]) or $each[2] == "null") {
              	$itemisocode->whereNull('COMMODITY_UNIT_ID');
              }else{
              	$itemisocode->where('COMMODITY_UNIT_ID',$each[2]);
              }
              $itemisocode    = $itemisocode->get();
              $itemisocode    = $itemisocode[0]->iso_code;
              if ($isocode == "") {
                $isocode = $itemisocode;
              }else{
                $subisocode = $itemisocode;
              }
            }

            elseif (!empty($list['KONTAINER'])) {
              $each           = explode('/', $list['KONTAINER']);
              $itemisocode        = \DB::connection('mdm')->table('TM_ISO_CONT')->where([
                "CONT_SIZE"   => $each[0],
                "CONT_TYPE"   => $each[1],
                "CONT_STATUS" => $each[2]
              ])->get();
              $itemisocode = $itemisocode[0]->iso_code;
              if ($isocode == "") {
                $isocode = $itemisocode;
              }else{
                $subisocode = $itemisocode;
              }
            }

            // Detail
            $detilS                     = new TsTariff;
            $detilS->tariff_prof_hdr_id = $headS->tariff_id;
            $detilS->service_code       = $headS->service_code;
            $detilS->sub_iso_code       = $subisocode;
            $detilS->iso_code           = $isocode;
            $detilS->branch_id          = 10; // SESSION LOGIN

            $detilS->nota_id            = $list['LAYANAN'];
            $detilS->tariff_object      = $list['OBJECT_TARIFF'];
            $detilS->group_tariff_id    = $list['GROUP_TARIFF'];
            $detilS->tariff             = $list['TARIFF'];
            // $detilS->tariff_STATUS = $list['TARIFF_STATUS'];
            $detilS->tariff_status      = $headS->tariff_status;
            // $detilS->tariff_DI = $list['TARIFF_DI'];
            // $detilS->SUB_TARIFF = $list['SUB_TARIFF'];
            $detilS->save();
          }
        // store detil

        if (!empty($head['FILE']['PATH'])) {
          if (!empty($headS->tariff_file) and file_exists($headS->tariff_file)) {
            unlink($headS->tariff_file);
          }

          $directory  = 'billing/profile_tariff/'.$headS->tariff_id.'/';
          $response   = FileUpload::upload_file($head['FILE'], $directory);
          if ($response['response'] == true) {
            TxProfileTariffHdr::where('tariff_id',$headS->tariff_id)->update([
              'tariff_name' => $head['TARIFF_NAME'],
              'tariff_file' => $response['link']
            ]);
          }
        }

        return response()->json([
          "result" => "Success, store profile tariff data",
        ]);
	}

	public static function storeCustomerProfileTariffAndUper($input){
        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->delete();
        foreach ($input['TARIFF'] as $list) {
	        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->insert([
	        	'CUST_PROFILE_ID' => $input['CUST_PROFILE_ID'],
	        	'TARIFF_HDR_ID' => $list['TARIFF_HDR_ID']
	        ]);
        }
        DB::connection('eng')->table('TS_UPER')->where('UPER_CUST_ID', $input['CUST_PROFILE_ID'])->delete();
        foreach ($input['UPER'] as $list) {
	        DB::connection('eng')->table('TS_UPER')->insert([
	        	'UPER_CUST_ID' => $input['CUST_PROFILE_ID'],
	        	'UPER_NOTA' => $list['UPER_NOTA'],
	        	'UPER_PRESENTASE' => $list['UPER_PRESENTASE'],
	        	'BRANCH_ID' => 12
	        ]);
        }
        return response()->json([
          "result" => "Success, store and set profile tariff and uper customer",
        ]);
    }

    public static function viewProfileTariff($input){
    	$header = TxProfileTariffHdr::find($input['TARIFF_ID']);
    	$detil = DB::connection('eng')->table('TS_TARIFF')->where('TARIFF_PROF_HDR_ID', $input['TARIFF_ID'])->orderBy('TARIFF_ID', 'DESC')->get();
    	$response_detil = [];
    	foreach ($detil as $list) {
    		$newDt = [];
    		$equipment_type_name = "";
    		$equipment_unit_name = "";
    		$equipment_unit_min = "";
    		$equipment_unit_code = "";
    		$package_name = "";
    		$package_code = "";
    		$commodity_name = "";
    		$commodity_unit_code = "";
    		$commodity_unit_name = "";
    		$commodity_unit_min = "";
    		$cont_desc = "";
    		$cont_status_desc = "";
    		$cont_type_desc = "";

    		foreach ($list as $key => $value) {
    			$newDt[$key] = $value;
    			if (strtoupper($key) == 'ISO_CODE' and !empty($value)) {
    				$equi = DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where('ISO_CODE',$value)->first();
    				if (!empty($equi)) {
    					foreach ($equi as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'ISO_CODE') {
    							$newDt[$keyS] = $valueS;
    						}
    						if (strtoupper($keyS) == 'EQUIPMENT_TYPE_ID') {
    							$equipment_type_name = DB::connection('mdm')->table('TM_EQUIPMENT_TYPE')->where('EQUIPMENT_TYPE_ID',$valueS)->first()->equipment_type_name;
    						}else if (strtoupper($keyS) == 'EQUIPMENT_UNIT') {
    							$get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
    							$equipment_unit_code = $get->unit_code;
    							$equipment_unit_name = $get->unit_name;
    							$equipment_unit_min = $get->unit_min;
    						}
    					}
    				}
    				$como = DB::connection('mdm')->table('TM_ISO_COMMODITY')->where('ISO_CODE',$value)->first();
    				if (!empty($como)) {
    					foreach ($como as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'ISO_CODE') {
    							$newDt[$keyS] = $valueS;
    						}
    						if (strtoupper($keyS) == 'PACKAGE_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_PACKAGE')->where('PACKAGE_ID',$valueS)->first();
    							$package_name = $get->package_name;
    							$package_code = $get->package_code;
    						}else if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
    							$commodity_name = $get->commodity_name;
    						}else if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
    							$commodity_unit_code = $get->unit_code;
    							$commodity_unit_name = $get->unit_name;
    							$commodity_unit_min = $get->unit_min;
    						}
    					}
    				}
    				$cont = DB::connection('mdm')->table('TM_ISO_CONT')->where('ISO_CODE',$value)->first();
    				if (!empty($cont)) {
    					foreach ($cont as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'ISO_CODE') {
    							$newDt[$keyS] = $valueS;
    						}
    						if (strtoupper($keyS) == 'CONT_SIZE' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_SIZE')->where('CONT_SIZE',$valueS)->first();
    							$cont_desc = $get->cont_desc;
    						}else if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
    							$cont_status_desc = $get->cont_status_desc;
    						}else if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
    							$cont_type_desc = $get->cont_type_desc;
    						}
    					}
    				}
    			}else if(strtoupper($key) == 'SUB_ISO_CODE' and !empty($value)){
    				$como = DB::connection('mdm')->table('TM_ISO_COMMODITY')->where('ISO_CODE',$value)->first();
    				if (!empty($como)) {
    					foreach ($como as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'ISO_CODE') {
    							$newDt[$keyS] = $valueS;
    						}
    						if (strtoupper($keyS) == 'PACKAGE_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_PACKAGE')->where('PACKAGE_ID',$valueS)->first();
    							$package_name = $get->package_name;
    							$package_code = $get->package_code;
    						}else if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
    							$commodity_name = $get->commodity_name;
    						}else if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
    							$commodity_unit_code = $get->unit_code;
    							$commodity_unit_name = $get->unit_name;
    							$commodity_unit_min = $get->unit_min;
    						}
    					}
    				}
    				$cont = DB::connection('mdm')->table('TM_ISO_CONT')->where('ISO_CODE',$value)->first();
    				if (!empty($cont)) {
    					foreach ($cont as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'ISO_CODE') {
    							$newDt[$keyS] = $valueS;
    						}
    						if (strtoupper($keyS) == 'CONT_SIZE' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_SIZE')->where('CONT_SIZE',$valueS)->first();
    							$cont_desc = $get->cont_desc;
    						}else if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
    							$cont_status_desc = $get->cont_status_desc;
    						}else if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
    							$get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
    							$cont_type_desc = $get->cont_type_desc;
    						}
    					}
    				}
    			}
    		}

    		$newDt['equipment_type_name'] = $equipment_type_name;
    		$newDt['equipment_unit_code'] = $equipment_unit_code;
    		$newDt['equipment_unit_name'] = $equipment_unit_name;
    		$newDt['equipment_unit_min'] = $equipment_unit_min;
    		$newDt['package_name'] = $package_name;
    		$newDt['package_code'] = $package_code;
    		$newDt['commodity_name'] = $commodity_name;
    		$newDt['commodity_unit_code'] = $commodity_unit_code;
    		$newDt['commodity_unit_name'] = $commodity_unit_name;
    		$newDt['commodity_unit_min'] = $commodity_unit_min;
    		$newDt['cont_desc'] = $cont_desc;
    		$newDt['cont_status_desc'] = $cont_status_desc;
    		$newDt['cont_type_desc'] = $cont_type_desc;
    		$response_detil[] = $newDt;
    	}
    	return response()->json([
    		'TxProfileTariffHdr' => $header,
    		'TsTariff' => $response_detil
    	]);
    }

    public static function viewCustomerProfileTariff($input){
    	$TsCustomerProfile = DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->leftJoin('TX_PROFILE_TARIFF_HDR', 'TS_CUSTOMER_PROFILE.TARIFF_HDR_ID', '=', 'TX_PROFILE_TARIFF_HDR.TARIFF_ID')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->get();
    	$TsUper = DB::connection('eng')->table('TS_UPER')->leftJoin('TM_NOTA', 'TS_UPER.UPER_NOTA', '=', 'TM_NOTA.NOTA_ID')->where('UPER_CUST_ID', $input['CUST_PROFILE_ID'])->get();
    	return response()->json([
    		"TsCustomerProfile" => $TsCustomerProfile,
    		"TsUper" => $TsUper
    	]);
    }
}
