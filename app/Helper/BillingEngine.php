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
              return ["Success"=>false, "result" => "Fail, iso code not found alat", "ALAT" => $list];
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
              return ["Success"=>false, "result" => "Fail, iso code not found barang", "BARANG" => $list];
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
              return ["Success"=>false, "result" => "Fail, iso code not found kontainer", "KONTAINER" => $list];
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
          $headS->branch_id     = 12; // SESSION LOGIN
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

        return [ "result" => "Success, store profile tariff data"];
	}

	public static function storeCustomerProfileTariffAndUper($input){
        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->delete();
        foreach ($input['TARIFF'] as $list) {
	        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->insert([
	        	'CUST_PROFILE_STATUS' => $input['CUST_PROFILE_STATUS'],
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
        return [ "result" => "Success, store and set profile tariff and uper customer" ];
    }

  public static function viewProfileTariff($input){
    	$header = TxProfileTariffHdr::find($input['TARIFF_ID']);
    	$detil = DB::connection('eng')->table('TS_TARIFF')->where('TARIFF_PROF_HDR_ID', $input['TARIFF_ID'])->orderBy('TARIFF_ID', 'DESC')->get();
    	$response_detil = [];
    	foreach ($detil as $list) {
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

    		$newDt = [];
    		$newDt['equipment_type_id'] = '';
    		$newDt['equipment_unit'] = '';
    		$newDt['equipment_type_name'] = $equipment_type_name;
    		$newDt['equipment_unit_code'] = $equipment_unit_code;
    		$newDt['equipment_unit_name'] = $equipment_unit_name;
    		$newDt['equipment_unit_min'] = $equipment_unit_min;
    		$newDt['cont_size'] = '';
    		$newDt['cont_type'] = '';
    		$newDt['cont_status'] = '';
    		$newDt['cont_desc'] = $cont_desc;
    		$newDt['cont_status_desc'] = $cont_status_desc;
    		$newDt['cont_type_desc'] = $cont_type_desc;
    		$newDt['package_id'] = '';
    		$newDt['commodity_id'] = '';
    		$newDt['commodity_unit_id'] = '';
    		$newDt['package_name'] = $package_name;
    		$newDt['package_code'] = $package_code;
    		$newDt['commodity_name'] = $commodity_name;
    		$newDt['commodity_unit_code'] = $commodity_unit_code;
    		$newDt['commodity_unit_name'] = $commodity_unit_name;
    		$newDt['commodity_unit_min'] = $commodity_unit_min;

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
    			}else if(strtolower($key) == 'group_tariff_id' and !empty($value)){
					$group_tariff_name = DB::connection('eng')->table('TM_GROUP_TARIFF')->where('GROUP_TARIFF_ID',$value)->get()[0]->group_tarif_name;
				}else if(strtolower($key) == 'nota_id' and !empty($value)){
    				$nota = DB::connection('eng')->table('TM_NOTA')->where('nota_id',$value)->first();
    				if (!empty($nota)) {
    					foreach ($nota as $keyS => $valueS) {
    						if (strtoupper($keyS) != 'SERVICE_CODE') {
	    						$newDt[$keyS] = $valueS;
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
    		$newDt['group_tarif_name'] = $group_tariff_name;
    		$response_detil[] = $newDt;
    	}
    	return [
    		'TxProfileTariffHdr' => $header,
    		'TsTariff' => $response_detil
    	];
    }

  public static function viewCustomerProfileTariff($input){
    	$TsCustomerProfile = DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->leftJoin('TX_PROFILE_TARIFF_HDR', 'TS_CUSTOMER_PROFILE.TARIFF_HDR_ID', '=', 'TX_PROFILE_TARIFF_HDR.TARIFF_ID')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->get();
    	$TsUper = DB::connection('eng')->table('TS_UPER')->leftJoin('TM_NOTA', 'TS_UPER.UPER_NOTA', '=', 'TM_NOTA.NOTA_ID')->where('UPER_CUST_ID', $input['CUST_PROFILE_ID'])->get();
    	return [
    		"TsCustomerProfile" => $TsCustomerProfile,
    		"TsUper" => $TsUper
    	];
    }

  public static function calculateTariff($input){
    	// build detil
	    	$detil = $input['detil'];
	    	$countD = 0;
	    	$setD = '';
	    	foreach ($detil as $list) {
	    		$countD++;
	    		$setD .= ' detail.DTL_PKG_ID := '.$list['DTL_PKG_ID'].';';
	    		$setD .= ' detail.DTL_CMDTY_ID := '.$list['DTL_CMDTY_ID'].';';
	    		$setD .= ' detail.DTL_CHARACTER := \''.$list['DTL_CHARACTER'].'\';';
	    		$setD .= ' detail.DTL_CONT_SIZE := '.$list['DTL_CONT_SIZE'].';';
	    		$setD .= ' detail.DTL_CONT_TYPE := '.$list['DTL_CONT_TYPE'].';';
	    		$setD .= ' detail.DTL_CONT_STATUS := '.$list['DTL_CONT_STATUS'].';';
	    		$setD .= ' detail.DTL_UNIT_ID := '.$list['DTL_UNIT_ID'].';';
	    		$setD .= ' detail.DTL_QTY := '.$list['DTL_QTY'].';';
	    		$setD .= ' detail.DTL_TL := \''.$list['DTL_TL'].'\';';
	    		$setD .= ' detail.DTL_DATE_IN := '.$list['DTL_DATE_IN'].';';
	    		$setD .= ' detail.DTL_DATE_OUT_OLD := '.$list['DTL_DATE_OUT_OLD'].';';
	    		$setD .= ' detail.DTL_DATE_OUT := '.$list['DTL_DATE_OUT'].';';
	    		$setD .= ' list_detail('.$countD.') := detail; ';
	    	}
		// build detil

	    // build eqpt
	    	$eqpt = $input['eqpt'];
	    	$countE = 0;
	    	$setE = '';
	    	foreach ($eqpt as $list) {
	    		$countE++;
	    		$setE .= ' equip.EQ_TYPE := '.$list['EQ_TYPE'].';';
	    		$setE .= ' equip.EQ_QTY := '.$list['EQ_QTY'].';';
	    		$setE .= ' equip.EQ_UNIT_ID := '.$list['EQ_UNIT_ID'].';';
	    		$setE .= ' equip.EQ_GTRF_ID := '.$list['EQ_GTRF_ID'].';';
	    		$setE .= ' equip.EQ_PKG_ID := '.$list['EQ_PKG_ID'].';';
	    		$setE .= ' list_equip('.$countE.') := equip; ';
	    	}
		// build eqpt

	    // build paysplit
	    	$paysplit = $input['paysplit'];
	    	$countP = 0;
	    	$setP = '';
	    	foreach ($paysplit as $list) {
	    		$countP++;
	    		$setE .= ' paysplit.PS_CUST_ID := \''.$list['PS_CUST_ID'].'\';';
	    		$setE .= ' paysplit.PS_GTRF_ID := '.$list['PS_GTRF_ID'].';';
	    		$setE .= ' list_paysplit('.$countP.') := paysplit; ';
	    	}
		// build paysplit

		// build head
	    	$head = $input['head'];
	    	$setH = " P_SOURCE_ID => 'NPK_BILLING',";
	    	$setH .= " P_BRANCH_ID => '".$head['P_BRANCH_ID']."',";
	    	$setH .= " P_CUSTOMER_ID => '".$head['P_CUSTOMER_ID']."',";
	    	$setH .= " P_NOTA_ID => '".$head['P_NOTA_ID']."',";
	    	$setH .= " P_BOOKING_NUMBER => '".$head['P_BOOKING_NUMBER']."',";
	    	$setH .= " P_REALIZATION => '".$head['P_REALIZATION']."',";
	    	$setH .= " P_TRADE => '".$head['P_TRADE']."',";
	    	$setH .= " P_DETAIL => list_detail,";
	    	$setH .= " P_EQUIPMENT => list_equip,";
	    	$setH .= " P_PAY_SPLIT => list_paysplit,";
	    	$setH .= " P_USER_ID => ".$head['P_USER_ID'].",";
	    	$setH .= " P_RESULT_FLAG => P_RESULT_FLAG,";
	    	$setH .= " P_RESULT_MSG => P_RESULT_MSG ";
		// build head

		// set data
	    	$set_data = [
	    		'b_no' => $head['P_BOOKING_NUMBER'],
	    		'head' => $setH,
	    		'detil' => $setD,
	    		'eqpt' => $setE,
	    		'paysplit' => $setP
	    	];
		// set data

		return static::calculateTariffExcute($set_data);
    }

  private static function calculateTariffExcute($input){
    	$head = DB::connection('eng')->table('TX_TEMP_TARIFF_HDR')->where('BOOKING_NUMBER', $input['b_no'])->get();
    	if (!empty($head)) {
    		$head = $head[0];
    		DB::connection('eng')->table('TX_LOG')->where('TEMP_HDR_ID', $head->temp_hdr_id)->delete();
    		DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID', $head->temp_hdr_id)->delete();
    		DB::connection('eng')->table('TX_TEMP_TARIFF_SPLIT')->where('TEMP_HDR_ID', $head->temp_hdr_id)->delete();
    		DB::connection('eng')->table('TX_TEMP_TARIFF_HDR')->where('BOOKING_NUMBER', $input['b_no'])->delete();
    	}
    	$link = oci_connect('BILLING_ENGINE', 'billing_engine', '10.88.48.124/NPKSBILD');
    	$sql = " DECLARE
		    detail PKG_TARIFF.BOOKING_DTL;
		    equip PKG_TARIFF.BOOKING_EQUIP;
		    paysplit PKG_TARIFF.BOOKING_PAYSPLIT;
		    list_detail PKG_TARIFF.BOOKING_DTL_TBL;
		    list_equip PKG_TARIFF.BOOKING_EQUIP_TBL;
		    list_paysplit PKG_TARIFF.BOOKING_PAYSPLIT_TBL;
		    P_RESULT_FLAG VARCHAR2(200);
		    P_RESULT_MSG VARCHAR2(200);
		BEGIN ".$input['detil']." ".$input['eqpt']." ".$input['paysplit'];
    	$sql .= " PKG_TARIFF.GET_TARIFF( ".$input['head']." );END;";

    	// return $sql;
    	$stmt = oci_parse($link,$sql);

    	// gak nemu buat nerima retun pesan dari prosedur // di ubah cara pengecekannya ngambil dari table TX_LOG
    	// oci_bind_by_name($stmt, "P_RESULT_FLAG", $out_status, 40);
    	// oci_bind_by_name($stmt, "P_RESULT_MSG", $out_message, 40);
    	$query = oci_execute($stmt);

		$head = DB::connection('eng')->table('TX_TEMP_TARIFF_HDR')->where('BOOKING_NUMBER', $input['b_no'])->get();
		if (empty($head)) {
    		return ["Success"=>false, 'result_flag' => false, 'result_msg' => 'Fail, prosedur bug'];
    	}else{
    		$head = $head[0];
    		$head = (array)$head;
    		$result = DB::connection('eng')->table('TX_LOG')->where('TEMP_HDR_ID', $head['temp_hdr_id'])->get();
    		$result = $result[0];
    		$result = (array)$result;

    		$response = ['result_flag' => $result['result_flag'], 'result_msg' => $result['result_msg']];
    		if ($result['result_flag'] != 'S') {
    			$response["Success"] = false;
    		}else{
    			$response["Success"] = true;
    		}
			return $response;
    	}
    }

	public static function getSimulasiTarif($input){

			// build head
				$setH = [];
				$setH['P_NOTA_ID'] 				= $input["HEADER"]["P_SOURCE_ID"];
				$setH['P_BRANCH_ID'] 			= $input["HEADER"]["P_BRANCH_ID"];
				$setH['P_CUSTOMER_ID'] 		= $input["HEADER"]["P_CUSTOMER_ID"];
				$setH['P_BOOKING_NUMBER'] = $input["HEADER"]["P_BOOKING_NUMBER"];
				$setH['P_REALIZATION'] 		= 'N';
				$setH['P_DATE_IN'] 				= NULL;
				$setH['P_DATE_OUT']			  = NULL;
				$setH['P_TRADE'] 				  = $input["HEADER"]["P_TRADE"];
				$setH['P_USER_ID'] 			  = $input["HEADER"]["P_USER_ID"];
			// build head

			// build detil
				$setD = [];
				$detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']])->get();
				foreach ($detil as $list) {
					$newD = [];
					$list = (array)$list;
					$newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
					$newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
					$newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
					$newD['DTL_CONT_SIZE'] = empty($list['dtl_cont_size']) ? 'NULL' : $list['dtl_cont_size'];
					$newD['DTL_CONT_TYPE'] = empty($list['dtl_cont_type']) ? 'NULL' : $list['dtl_cont_type'];
					$newD['DTL_CONT_STATUS'] = empty($list['dtl_cont_status']) ? 'NULL' : $list['dtl_cont_status'];
					$newD['DTL_UNIT_ID'] = empty($list['dtl_unit_id']) ? 'NULL' : $list['dtl_unit_id'];
					$newD['DTL_QTY'] = empty($list['dtl_qty']) ? 'NULL' : $list['dtl_qty'];

					if ($config['head_tab_detil_tl'] != null) {
						$newD['DTL_TL'] = empty($list[$config['head_tab_detil_tl']]) ? 'NULL' : $list[$config['head_tab_detil_tl']];
					}else{
						$newD['DTL_TL'] = 'NULL';
					}

					if ($config['head_tab_detil_date_in'] != null) {
						if ($input['table'] == 'TX_HDR_REC') {
							$newD['DTL_DATE_IN'] = empty($list[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.$list[$config['head_tab_detil_date_in']].'\',\'yyyy-MM-dd\')';
						}else{
							$newD['DTL_DATE_IN'] = empty($find[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.$find[$config['head_tab_detil_date_in']].'\',\'yyyy-MM-dd\')';
						}
					}else{
						$newD['DTL_DATE_IN'] = 'NULL';
					}

					if ($config['head_tab_detil_date_out_old'] != null and ($input['table'] == 'TX_HDR_DEL' and $find['del_extend_status'] != 'N') ) {
						$findEx = DB::connection('omcargo')->select(DB::raw("
							SELECT
							X.DTL_OUT AS date_out_old,
							Y.DTL_OUT AS date_out
							FROM (
							SELECT
							DEL_ID,DEL_NO,DTL_OUT,DEL_EXTEND_FROM
							FROM
							TX_HDR_DEL A
							JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
							) X
							JOIN (
							SELECT
							DEL_ID,DEL_NO,DTL_OUT,DEL_EXTEND_FROM
							FROM
							TX_HDR_DEL A
							JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
							) Y
							ON X.DEL_NO=Y.DEL_EXTEND_FROM WHERE Y.DEL_NO='".$find[$config['head_no']]."'
							"));
						if (empty($findEx)) {
							$newD['DTL_DATE_OUT_OLD'] = 'NULL';
							$newD['DTL_DATE_OUT'] = 'NULL';
						}else{
							$findEx = $findEx[0];
							$findEx = (array)$findEx;
							$newD['DTL_DATE_OUT_OLD'] = empty($findEx['date_out_old']) ? 'NULL' : 'to_date(\''.$findEx['date_out_old'].'\',\'yyyy-MM-dd\')';
							$newD['DTL_DATE_OUT'] = empty($findEx['date_out']) ? 'NULL' : 'to_date(\''.$findEx['date_out'].'\',\'yyyy-MM-dd\')';
						}
					}else{
						$newD['DTL_DATE_OUT_OLD'] = 'NULL';

						if ($config['head_tab_detil_date_out'] != null) {
							if ($input['table'] == 'TX_HDR_DEL') {
								$newD['DTL_DATE_OUT'] = empty($list[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.$list[$config['head_tab_detil_date_out']].'\',\'yyyy-MM-dd\')';
							}else{
								$newD['DTL_DATE_OUT'] = empty($find[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.$find[$config['head_tab_detil_date_out']].'\',\'yyyy-MM-dd\')';
							}
						}else{
							$newD['DTL_DATE_OUT'] = 'NULL';
						}
					}

					$setD[] = $newD;
				}
			// build detil

			// build eqpt
				$setE = [];
				$eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find[$config['head_no']])->get();
				foreach ($eqpt as $list) {
					$newE = [];
					$list = (array)$list;
					$newE['EQ_TYPE'] = empty($list['eq_type_id']) ? 'NULL' : $list['eq_type_id'];
					$newE['EQ_QTY'] = empty($list['eq_qty']) ? 'NULL' : $list['eq_qty'];
					$newE['EQ_UNIT_ID'] = empty($list['eq_unit_id']) ? 'NULL' : $list['eq_unit_id'];
					$newE['EQ_GTRF_ID'] = empty($list['group_tariff_id']) ? 'NULL' : $list['group_tariff_id'];
					$newE['EQ_PKG_ID'] = empty($list['package_id']) ? 'NULL' : $list['package_id'];
					$setE[] = $newE;
				}
			// build eqpt

			// build paysplit
				$setP = [];
				$paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find[$config['head_no']])->get();
				$paysplit = (array)$paysplit;
				foreach ($paysplit as $list) {
					$newP = [];
					$list = (array)$list;
					$newP['PS_CUST_ID'] = $list['cust_id'];
					$newP['PS_GTRF_ID'] = $list['group_tarif_id'];
					$setP[] = $newP;
				}
			// build paysplit

			// set data
				$set_data = [
					'head' => $setH,
					'detil' => $setD,
					'eqpt' => $setE,
					'paysplit' => $setP
				];
			// set data

			// return $tariffResp = BillingEngine::calculateTariff($set_data);
			$tariffResp = BillingEngine::calculateTariff($set_data);

			if ($tariffResp['result_flag'] == 'S') {
				DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 2
				]);
			}
			return $tariffResp;
	    }

	    public static function approvalRequest($input){
	    	$input['table'] = strtoupper($input['table']);
			$config = static::config($input['table']);
			$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['result' => "Fail, requst not found!", "Success" => false];
			}
			$find = (array)$find[0];
			if ($input['approved'] == 'false') {
				DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 4
				]);
				return ['result' => "Success, rejected requst"];
			}
			$uper = DB::connection('eng')->table('V_PAY_SPLIT')->where('BOOKING_NUMBER',$find['head_no'])->get();
			if (empty($uper)) {
				return ['result' => "Fail, uper and tariff not found!", "Success" => false];
			}
			$uper = $uper[0];
			$uper = (array)$uper;
			$cekU = DB::connection('eng')->table('TX_LOG')->where('TEMP_HDR_ID',$uper['temp_hdr_id'])->get();
			if ($cekU[0]->result_flag != 'S') {
				return ['result' => "Fail", 'logs' => $cekU[0]];
			}
			$uperD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$uper['temp_hdr_id'])->get();

			$datenow    = Carbon::now()->format('Y-m-d');
			// $branch_code = DB::connection('mdm')->table('TM_BRANCH')->where('BRANCH_ID',$uper['branch_id'])->get();
			// $branch_code = $branch_code[0]->branch_code;
			$headU = new TxHdrUper;
			// $headU->uper_no // dari triger
			$headU->uper_org_id = $uper['branch_org_id'];
			$headU->uper_cust_id = $uper['customer_id'];
			$headU->uper_cust_name = $uper['alt_name'];
			$headU->uper_cust_npwp = $uper['npwp'];
			$headU->uper_cust_address = $uper['address'];
			$headU->uper_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
			$headU->uper_amount = $uper['uper_total'];
			$headU->uper_currency_code = $uper['currency'];
			$headU->uper_status = 'P'; // blm fix
			$headU->uper_context = 'BRG'; // blm fix
			$headU->uper_sub_context = 'BRG03'; // blm fix
			$headU->uper_terminal_code = $find[$config['head_terminal_code']];
			$headU->uper_branch_id = $uper['branch_id'];
			// $headU->uper_branch_code = $branch_code; // kemungkinan ditambah
			$headU->uper_vessel_name = $find[$config['head_vessel_name']];
			$headU->uper_faktur_no = '12576817'; // ? dari triger bf i
			$headU->uper_trade_type = $uper['trade_type'];
			$headU->uper_req_no = $uper['booking_number'];
			$headU->uper_ppn = $uper['ppn'];
			// $headU->uper_paid // ? pasti null
			// $headU->uper_paid_date // ? pasti null
			$headU->uper_percent = $uper['uper_percent'];
			$headU->uper_dpp = $uper['dpp'];
			if ($config['head_pbm_id'] != null) {
				$headU->uper_pbm_id = $find[$config['head_pbm_id']];
			}
			if ($config['head_pbm_name'] != null) {
				$headU->uper_pbm_name = $find[$config['head_pbm_name']];
			}
			if ($config['head_shipping_agent_id'] != null) {
				$headU->uper_shipping_agent_id = $find[$config['head_shipping_agent_id']];
			}
			if ($config['head_shipping_agent_name'] != null) {
				$headU->uper_shipping_agent_name = $find[$config['head_shipping_agent_name']];
			}
			$headU->uper_req_date = $find[$config['head_date']];
			if ($config['head_terminal_name'] != null) {
				$headU->uper_terminal_name = $find[$config['head_terminal_name']];
			}
			$headU->uper_nota_id = $uper['nota_id'];
			$headU->save();

			foreach ($uperD as $list) {
				$list = (array)$list;
				$set_data = [
					"uper_hdr_id" => $headU->uper_id,
					// "dtl_line" => , // perlu konfimasi
					// "dtl_line_desc" => , // perlu konfimasi
					// "dtl_line_context" => , // perlu konfimasi
					"dtl_service_type" => $list['group_tariff_name'],
					"dtl_amout" => $list['uper'], // blm fix
					"dtl_ppn" => $list["ppn"],
					// "dtl_masa1" => , // cooming soon
					// "dtl_masa12" => , // cooming soon
					// "dtl_masa2" => , // cooming soon
					"dtl_tariff" => $list["tariff"],
					// "dtl_package" => , // cooming soon
					"dtl_qty" => $list["qty"],
					"dtl_unit" => $list["unit_id"],
					"dtl_group_tariff_id" => $list["group_tariff_id"],
					"dtl_group_tariff_name" => $list["group_tariff_name"],
					// "dtl_bl" => $list[""], // tunggu dari adi
					"dtl_dpp" => $list["tariff_cal"],
					"dtl_commodity" => $list["commodity_name"],
					"dtl_equipment" => $list["equipment_name"],
					"dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")
				];
				DB::connection('omcargo')->table('TX_DTL_UPER')->insert($set_data);
			}

			DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 3
			]);

			return ['result' => "Success, approved request!"];
	    }

	    private static function config($input){
	    	$requst_config = [
	        	"TX_HDR_BM" => [
	        		"head_nota_id" => 13,
	        		"head_tab" => "TX_HDR_BM",
	        		"head_tab_detil" => "TX_DTL_BM",
	        		"head_tab_detil_tl" => "dtl_bm_tl",
	        		"head_tab_detil_date_in" => null,
	        		"head_tab_detil_date_out" => null,
	        		"head_tab_detil_date_out_old" => null,
	        		"head_status" => "bm_status",
	        		"head_primery" => "bm_id",
	        		"head_forigen" => "hdr_bm_id",
	        		"head_no" => "bm_no",
	        		"head_by" => "bm_create_by",
	        		"head_date" => "bm_date",
	        		"head_branch" => "bm_branch_id",
	        		"head_cust" => "bm_cust_id",
	        		"head_trade" => "bm_trade_type",
	        		"head_terminal_code" => "bm_terminal_code",
	        		"head_terminal_name" => "bm_terminal_name",
	        		"head_pbm_id" => "bm_pbm_id",
	        		"head_pbm_name" => "bm_pbm_name",
	        		"head_shipping_agent_id" => "bm_shipping_agent_id",
	        		"head_shipping_agent_name" => "bm_shipping_agent_name",
	        		"head_vessel_code" => "bm_vessel_code",
	        		"head_vessel_name" => "bm_vessel_name"
	        	],
	        	"TX_HDR_REC" => [
	        		"head_nota_id" => "14",
	        		"head_tab" => "TX_HDR_REC",
	        		"head_tab_detil" => "TX_DTL_REC",
	        		"head_tab_detil_tl" => null,
	        		"head_tab_detil_date_in" => 'dtl_in',
	        		"head_tab_detil_date_out" => 'rec_atd',
	        		"head_tab_detil_date_out_old" => null,
	        		"head_status" => "rec_status",
	        		"head_primery" => "rec_id",
	        		"head_forigen" => "hdr_rec_id",
	        		"head_no" => "rec_no",
	        		"head_by" => "rec_create_by",
	        		"head_date" => "rec_date",
	        		"head_branch" => "rec_branch_id",
	        		"head_cust" => "rec_cust_id",
	        		"head_trade" => "rec_trade_type",
	        		"head_terminal_code" => "rec_terminal_code",
	        		"head_terminal_name" => "rec_terminal_name",
	        		"head_pbm_id" => null,
	        		"head_pbm_name" => null,
	        		"head_shipping_agent_id" => null,
	        		"head_shipping_agent_name" => null,
	        		"head_vessel_code" => "rec_vessel_code",
	        		"head_vessel_name" => "rec_vessel_name"
	        	],
	        	"TX_HDR_DEL" => [
	        		"head_nota_id" => "15",
	        		"head_tab" => "TX_HDR_DEL",
	        		"head_tab_detil" => "TX_DTL_DEL",
	        		"head_tab_detil_tl" => null,
	        		"head_tab_detil_date_in" => 'del_atd',
	        		"head_tab_detil_date_out" => 'dtl_out',
	        		"head_tab_detil_date_out_old" => 'extension',
	        		"head_status" => "del_status",
	        		"head_primery" => "del_id",
	        		"head_forigen" => "hdr_del_id",
	        		"head_no" => "del_no",
	        		"head_by" => "del_create_by",
	        		"head_date" => "del_date",
	        		"head_branch" => "del_branch_id",
	        		"head_cust" => "del_cust_id",
	        		"head_trade" => "del_trade_type",
	        		"head_terminal_code" => "del_terminal_code",
	        		"head_terminal_name" => "del_terminal_name",
	        		"head_pbm_id" => null,
	        		"head_pbm_name" => null,
	        		"head_shipping_agent_id" => null,
	        		"head_shipping_agent_name" => null,
	        		"head_vessel_code" => "del_vessel_code",
	        		"head_vessel_name" => "del_vessel_name"
	        	]
	        ];

	        return $requst_config[$input];
	    }

}
