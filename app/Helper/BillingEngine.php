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
	        	'BRANCH_ID' => $input['BRANCH_ID']
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
          $setD .= ' detail.DTL_BL := \''.$list['DTL_BL'].'\';';
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

	public static function getSimulasiTarif($input) {
		// build head
				$head 											= $input["HEADER"];
				$setH 											= [];
				$setH['P_NOTA_ID'] 					= $head['P_NOTA_ID'];
				$setH['P_BRANCH_ID'] 				= $head['P_BRANCH_ID'];
				$setH['P_CUSTOMER_ID'] 			= $head['P_CUSTOMER_ID'];
				$setH['P_BOOKING_NUMBER'] 	= $head['P_BOOKING_NUMBER'];
				$setH['P_REALIZATION'] 			= $head['P_REALIZATION'];
				$setH['P_TRADE'] 						= $head['P_TRADE'];
				$setH['P_USER_ID'] 					= $head['P_USER_ID'];
		// build head

		// build detil
				$detil 											= $input["DETAIL"];
				$datein 										= date('Y-m-d');
				$dateout 										= date('Y-m-d', strtotime("+1 day"));
				$setD = [];
				foreach ($detil as $list) {
					$newD 										= [];
					$list 										= (array)$list;
					$newD['DTL_BL'] 					= $list['DTL_BL'];
					$newD['DTL_PKG_ID'] 			= $list['DTL_PKG_ID'];
					$newD['DTL_CMDTY_ID'] 		= $list['DTL_CMDTY_ID'];
					$newD['DTL_CHARACTER'] 		= $list['DTL_CHARACTER'];
					$newD['DTL_CONT_SIZE'] 		= 'NULL';
					$newD['DTL_CONT_TYPE'] 		= 'NULL';
					$newD['DTL_CONT_STATUS'] 	= 'NULL';
					$newD['DTL_UNIT_ID'] 			= $list['DTL_UNIT_ID'];
					$newD['DTL_QTY'] 					= $list['DTL_QTY'];
					$newD['DTL_TL'] 					= $list['DTL_TL'];
					$newD['DTL_DATE_IN'] 			= empty($datein) ? 'NULL' : 'to_date(\''.$datein.'\',\'yyyy-MM-dd\')';
					$newD['DTL_DATE_OUT'] 		= empty($dateout) ? 'NULL' : 'to_date(\''.$dateout.'\',\'yyyy-MM-dd\')';
					$newD['DTL_DATE_OUT_OLD'] = 'NULL';
					$setD[] = $newD;
				}
		// build detil

		// build eqpt
				$setE = [];
				$eqpt 											= $input["EQUIP"];
				foreach ($eqpt as $list) {
					$newE 										= [];
					$list 										= (array)$list;
					$newE['EQ_TYPE'] 					= empty($list['EQ_TYPE_ID']) ? 'NULL' : $list['EQ_TYPE_ID'];
					$newE['EQ_QTY'] 					= empty($list['EQ_QTY']) ? 'NULL' : $list['EQ_QTY'];
					$newE['EQ_UNIT_ID'] 			= empty($list['EQ_UNIT_ID']) ? 'NULL' : $list['EQ_UNIT_ID'];
					$newE['EQ_GTRF_ID'] 			= empty($list['EQ_GTRF_ID']) ? 'NULL' : $list['EQ_GTRF_ID'];
					$newE['EQ_PKG_ID'] 				= empty($list['EQ_PKG_ID']) ? 'NULL' : $list['EQ_PKG_ID'];
					$setE[] = $newE;
				}
		// build eqpt

		// set data
			$set_data = [
				'head' => $setH,
				'detil' => $setD,
				'eqpt' => $setE,
				'paysplit' => []
			];

			$tariffResp = BillingEngine::calculateTariff($set_data);
			if ($tariffResp['result_flag'] != 'S') {
				return $tariffResp;
			}

			$getH = DB::connection('eng')->table('V_PAY_SPLIT')->where('booking_number',$head['P_BOOKING_NUMBER'])->get();
			return $getH;
		}
	}
