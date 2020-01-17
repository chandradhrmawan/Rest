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

        // store head
          if(empty($head['TARIFF_ID'])){
            $headS    = new TxProfileTariffHdr;
          }else{
            $headS    = TxProfileTariffHdr::find($head['TARIFF_ID']);
          }

          $headS->tariff_type   = $head['TARIFF_TYPE'];
          $headS->tariff_start  = \DB::raw("TO_DATE('".$head['TARIFF_START']."', 'YYYY-MM-DD HH24:MI')");
          $headS->tariff_end    = \DB::raw("TO_DATE('".$head['TARIFF_END']."', 'YYYY-MM-DD HH24:MI')");
          $headS->tariff_no     = $head['TARIFF_NO'];
          $headS->tariff_name   = $head['TARIFF_NAME'];
          $headS->tariff_status = $head['TARIFF_STATUS'];
          $headS->service_code  = $head['SERVICE_CODE'];
          // $headS->branch_id     = 12;
          // $headS->created_by    = 1;
          if (!empty($head['BRANCH_ID'])) {
            $headS->branch_id     = $head['BRANCH_ID'];
          }else{
            $headS->branch_id     = (array)$input['user']->user_branch_id;
          }
          if (!empty($head['BRANCH_CODE'])) {
            $headS->branch_code     = $head['BRANCH_CODE'];
          }else{
            $headS->branch_code   = (array)$input['user']->user_branch_code;
          }
          $headS->created_by    = $head['USER_ID'];
          $headS->created_date  = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
          $headS->save();
        // store head

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

        // store detil
          if (count($input['detil']) > 0) {
            TsTariff::where('tariff_prof_hdr_id', $headS->tariff_id)->delete();
          }
          foreach ($input['detil'] as $list) {
              $isocode    = "";
              $subisocode = "";
              if (!empty($list['ALAT'])) {
                $each       = explode('/', $list['ALAT']);

                $query = "SELECT FNC_CREATE_ISO('EQUIPMENT',";
                if ($each[0] == 'null') {
                  $query .= $each[0].",";
                }else{
                  $query .= "'".$each[0]."',";
                }
                if ($each[1] == 'null') {
                  $query .= $each[1].",";
                }else{
                  $query .= "'".$each[1]."',";
                }
                $query .= "'') ISO FROM dual";
                $alatisocode = \DB::connection('mdm')->select(DB::raw($query));
                $alatisocode = $alatisocode[0]->iso;
                $isocode = $alatisocode;
              }

              if (!empty($list['BARANG'])) {
                $each       = explode('/', $list['BARANG']);
                $query = "SELECT FNC_CREATE_ISO('COMMODITY',";
                if ($each[0] == 'null') {
                  $query .= $each[0].",";
                }else{
                  $query .= "'".$each[0]."',";
                }
                if ($each[1] == 'null') {
                  $query .= $each[1].",";
                }else{
                  $query .= "'".$each[1]."',";
                }
                if ($each[2] == 'null') {
                  $query .= $each[2];
                }else{
                  $query .= "'".$each[2]."'";
                }
                $query .= ") ISO FROM dual";
                $itemisocode = \DB::connection('mdm')->select(DB::raw($query));
                $itemisocode    = $itemisocode[0]->iso;
                if ($isocode == "") {
                  $isocode = $itemisocode;
                }else{
                  $subisocode = $itemisocode;
                }
              }

              if (!empty($list['KONTAINER'])) {
                $each           = explode('/', $list['KONTAINER']);
                $query = "SELECT FNC_CREATE_ISO('CONT',";
                if ($each[0] == 'null') {
                  $query .= $each[0].",";
                }else{
                  $query .= "'".$each[0]."',";
                }
                if ($each[1] == 'null') {
                  $query .= $each[1].",";
                }else{
                  $query .= "'".$each[1]."',";
                }
                if ($each[2] == 'null') {
                  $query .= $each[2];
                }else{
                  $query .= "'".$each[2]."'";
                }
                $query .= ") ISO FROM dual";
                $itemisocode = \DB::connection('mdm')->select(DB::raw($query));
                $itemisocode = $itemisocode[0]->iso;
                if ($isocode == "") {
                  $isocode = $itemisocode;
                }else{
                  $subisocode = $itemisocode;
                }
              }

              // Detail
              if (isset($list['tariff_id']) and !empty($list['tariff_id'])) {
                $detilS                     = TsTariff::find($list['tariff_id']);
              }else{
                $detilS                     = new TsTariff;
              }
              $detilS->tariff_prof_hdr_id = $headS->tariff_id;
              $detilS->service_code       = $headS->service_code;
              $detilS->sub_iso_code       = $subisocode;
              $detilS->iso_code           = $isocode;
              if (!empty($head['BRANCH_ID'])) {
                $detilS->branch_id        = $head['BRANCH_ID'];
              }else{
                $detilS->branch_id        = $input["user"]["user_branch_id"];
              }
              $detilS->nota_id            = $list['LAYANAN'];
              $detilS->tariff_object      = $list['OBJECT_TARIFF'];
              $detilS->group_tariff_id    = $list['GROUP_TARIFF'];
              $detilS->tariff             = $list['TARIFF'];
              $detilS->stacking_area      = $list['AREA'];
              $detilS->tariff_reference   = $list['TARIFF_REFERENCE'];
              $detilS->tariff_status      = $headS->tariff_status;
              $detilS->save();
          }
        // store detil

        $listHeader = TxProfileTariffHdr::find($headS->tariff_id);
        return [ "result" => "Success, store profile tariff data", "header"=>$listHeader];
  }

  public static function storeProfileTariffDetil($input){
    // store detil
      $result = [];
      foreach ($input['store'] as $list) {
        $isocode    = "";
        $subisocode = "";
        if (!empty($list['ALAT'])) {
          $each       = explode('/', $list['ALAT']);

          $query = "SELECT FNC_CREATE_ISO('EQUIPMENT',";
          if ($each[0] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[0]."',";
          }
          if ($each[1] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[1]."',";
          }
          $query .= "'') ISO FROM dual";
          $alatisocode = \DB::connection('mdm')->select(DB::raw($query));
          $alatisocode = $alatisocode[0]->iso;
          $isocode = $alatisocode;
        }

        if (!empty($list['BARANG'])) {
          $each       = explode('/', $list['BARANG']);
          $query = "SELECT FNC_CREATE_ISO('COMMODITY',";
          if ($each[0] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[0]."',";
          }
          if ($each[1] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[1]."',";
          }
          if ($each[2] == 'null') {
            $query .= "''";
          }else{
            $query .= "'".$each[2]."'";
          }
          $query .= ") ISO FROM dual";
          $itemisocode = \DB::connection('mdm')->select(DB::raw($query));
          $itemisocode    = $itemisocode[0]->iso;
          if ($isocode == "") {
            $isocode = $itemisocode;
          }else{
            $subisocode = $itemisocode;
          }
        }

        if (!empty($list['KONTAINER'])) {
          $each           = explode('/', $list['KONTAINER']);
          $query = "SELECT FNC_CREATE_ISO('CONT',";
          if ($each[0] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[0]."',";
          }
          if ($each[1] == 'null') {
            $query .= "'',";
          }else{
            $query .= "'".$each[1]."',";
          }
          if ($each[2] == 'null') {
            $query .= "''";
          }else{
            $query .= "'".$each[2]."'";
          }
          $query .= ") ISO FROM dual";
          $itemisocode = \DB::connection('mdm')->select(DB::raw($query));
          $itemisocode = $itemisocode[0]->iso;
          if ($isocode == "") {
            $isocode = $itemisocode;
          }else{
            $subisocode = $itemisocode;
          }
        }

        $cek = TsTariff::where([
          "tariff_prof_hdr_id" => $list['tariff_prof_hdr_id'],
          "sub_iso_code"       => $subisocode,
          "iso_code"           => $isocode
        ]);
        if (isset($list['tariff_id']) and !empty($list['tariff_id'])) {
          $cek->whereNotIn('tariff_id', [$list['tariff_id']]);
        }
        $cek = $cek->count();
        if ($cek > 0) {
          return [ "Success" => false, "result" => "Fail, store profile tariff detil data is exists!" ];
        }

        // Detail
        if (isset($list['tariff_id']) and !empty($list['tariff_id'])) {
          $detilS                     = TsTariff::find($list['tariff_id']);
        }else{
          $detilS                     = new TsTariff;
        }
        $detilS->tariff_prof_hdr_id = $list['tariff_prof_hdr_id'];
        $detilS->service_code       = $list['service_code'];
        $detilS->sub_tariff         = $list['SUB_TARIFF'];
        $detilS->sub_iso_code       = $subisocode;
        $detilS->iso_code           = $isocode;
        if (!empty($list['BRANCH_ID'])) {
          $detilS->branch_id        = $list['BRANCH_ID'];
        }else{
          $detilS->branch_id        = (array)$input['user']->user_branch_id;
        }
        $detilS->nota_id            = $list['LAYANAN'];
        $detilS->tariff_object      = $list['OBJECT_TARIFF'];
        $detilS->group_tariff_id    = $list['GROUP_TARIFF'];
        $detilS->tariff             = $list['TARIFF'];
        $detilS->stacking_area      = $list['AREA'];
        $detilS->tariff_reference   = $list['TARIFF_REFERENCE'];
        $detilS->tariff_status      = $list['tariff_status'];
        $detilS->via                = $list['VIA'];
        $detilS->fumigasi_type      = $list['FUMIGATION_TYPE'];
        $detilS->pluggin_unit       = $list['PLUGGIN_UNIT'];
        $detilS->save();
        $result[] = $detilS;
      }
      return [ "result" => "Success, store profile tariff detil data", "details"=>$result];
    // store detil
  }

  public static function deleteProfileTariffDetil($input){
    TsTariff::where('tariff_id', $input['id'])->delete();
    return [ "result" => "Success, delete profile tariff detil data" ];
  }

  public static function storeCustomerProfileTariffAndUper($input){
        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->delete();
        foreach ($input['TARIFF'] as $list) {
          DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->insert([
            'CUST_PROFILE_STATUS' => $input['CUST_PROFILE_STATUS'],
            'CUST_PROFILE_ID' => $input['CUST_PROFILE_ID'],
            'CUST_PROFILE_NOTE' => $input['CUST_PROFILE_NOTE'],
            'TARIFF_HDR_ID' => $list['TARIFF_HDR_ID']
          ]);
        }
        DB::connection('eng')->table('TS_UPER')->where('UPER_CUST_ID', $input['CUST_PROFILE_ID'])->delete();
        foreach ($input['UPER'] as $list) {
          DB::connection('eng')->table('TS_UPER')->insert([
            'UPER_CUST_ID' => $input['CUST_PROFILE_ID'],
            'UPER_NOTA' => $list['UPER_NOTA'],
            'UPER_PRESENTASE' => $list['UPER_PRESENTASE'],
            'BRANCH_ID' => $input['BRANCH_ID'],
            'BRANCH_CODE' => $input['BRANCH_CODE']
          ]);
        }
        return [ "result" => "Success, store and set profile tariff and uper customer" ];
  }

  public function listProfileTariffDetil($input){
    $detil = DB::connection('eng')->table('TS_TARIFF')->where('TARIFF_PROF_HDR_ID', $input['tariff_id']);
    $order_key = 'tariff_id';
    if (isset($input['order_key']) and !empty($input['order_key'])) {
      $order_key = $input['order_key'];
      $order_val = "DESC";
      if (isset($input['order_val']) and !empty($input['order_val'])) {
        $order_val = $input['order_val'];
      }
    }
    $limit = 25;
    $start = 0;
    if (isset($input['limit']) and !empty($input['limit'])){
      $limit = $input['limit'];
    }
    if (isset($input['start']) and !empty($input['start'])){
      $start = $input['start'];
    }
    $count = $detil->count();

    $detil->orderBy($order_key,$order_val)->take($limit)->skip($start);
    $detil = $detil->get();

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

      $newDt['tariff_reference_name'] = '';
      if (!empty($list->tariff_reference)) {
        $name_tariff_reference = DB::connection('eng')->table('TM_REFF')->where('reff_id', $list->tariff_reference)->where('reff_tr_id', 2)->get();
        if (count($name_tariff_reference) > 0) {
          $name_tariff_reference = $name_tariff_reference[0];
          $newDt['tariff_reference_name'] = $name_tariff_reference->reff_name;
        }
      }
      foreach ($list as $key => $value) {
        $newDt[$key] = $value;
        if (strtoupper($key) == 'ISO_CODE' and !empty($value)) {
          $package_name = "";
          $package_code = "";
          $commodity_name = "";
          $commodity_unit_code = "";
          $commodity_unit_name = "";
          $commodity_unit_min = "";
          $cont_desc = "";
          $cont_status_desc = "";
          $cont_type_desc = "";
          $equi = DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where('ISO_CODE',$value)->first();
          if (!empty($equi)) {
            foreach ($equi as $keyS => $valueS) {
              if (strtoupper($keyS) != 'ISO_CODE') {
                $newDt[$keyS] = $valueS;
              }
              if (strtoupper($keyS) == 'EQUIPMENT_TYPE_ID') {
                $equipment_type_name = DB::connection('mdm')->table('TM_EQUIPMENT_TYPE')->where('EQUIPMENT_TYPE_ID',$valueS)->first()->equipment_type_name;
              } else if (strtoupper($keyS) == 'EQUIPMENT_UNIT') {
                $get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
                $equipment_unit_code = $get->unit_code;
                $equipment_unit_name = $get->unit_name;
                $equipment_unit_min = $get->unit_min;
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
              }
              if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
                $cont_status_desc = $get->cont_status_desc;
              }
               if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
                $cont_type_desc = $get->cont_type_desc;
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
              }
              if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
                $commodity_name = $get->commodity_name;
              }
              if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
                $commodity_unit_code = $get->unit_code;
                $commodity_unit_name = $get->unit_name;
                $commodity_unit_min = $get->unit_min;
              }
            }
          }
        } else if(strtoupper($key) == 'SUB_ISO_CODE' and !empty($value)){
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
              }
              if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
                $commodity_name = $get->commodity_name;
              }
              if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
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
              }

              if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
                $cont_status_desc = $get->cont_status_desc;
              }

              if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
                $cont_type_desc = $get->cont_type_desc;
              }
            }
          }
        } else if(strtolower($key) == 'group_tariff_id' and !empty($value)){
          $group_tariff_name = DB::connection('eng')->table('TM_GROUP_TARIFF')->where('GROUP_TARIFF_ID',$value)->get()[0]->group_tarif_name;
        } else if(strtolower($key) == 'sub_tariff' and !empty($value)){
          $group_tariff_name = DB::connection('eng')->table('TM_REFF')->where('REFF_TR_ID', 7)->where('REFF_ID',$value)->get()[0]->reff_name;
        } else if(strtolower($key) == 'nota_id' and !empty($value)){
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

      $stacking = DB::connection('eng')->table('TM_REFF')->where([['reff_id','=', $list->stacking_area],['REFF_TR_ID','=', '11']])->select("reff_name as stacking_area_name")->get();
      if (empty($stacking)) {
        $newDt['stacking_area_name'] = '';
      } else {
        foreach ($stacking as $listS) {
          foreach ($listS as $key => $value) {
            $newDt[$key] = $value;
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

    return [ "result" => $response_detil, "count"=>$count ];
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
      $cont_type_desc = "";
      $via_name = "";
      $fumigasi_name = "";
      $plugging_unit_name = "";

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
      $newDt['via_name'] = $via_name;
      $newDt['fumigasi_name'] = $fumigasi_name;
      $newDt['plugging_unit_name'] = $plugging_unit_name;

      $newDt['tariff_reference_name'] = '';
      if (!empty($list->tariff_reference)) {
        $name_tariff_reference = DB::connection('mdm')->table('TM_REFF')->where('reff_id', $list->tariff_reference)->where('reff_tr_id', 2)->get();
        if (count($name_tariff_reference) > 0) {
          $name_tariff_reference = $name_tariff_reference[0];
          $newDt['tariff_reference_name'] = $name_tariff_reference->reff_name;
        }
      }
      foreach ($list as $key => $value) {
        $newDt[$key] = $value;
        if (strtoupper($key) == 'PLUGGIN_UNIT' and !empty($value)) {
          $plugging_unit_name = DB::connection('mdm')->table('TM_UNIT')->where('unit_id', $value)->first();
          $plugging_unit_name = $plugging_unit_name->unit_name;
        }
        if (strtoupper($key) == 'VIA' and !empty($value)) {
          $via_name = DB::connection('mdm')->table('TM_REFF')->where('reff_tr_id',4)->where('reff_id', $value)->first();
          $via_name = $via_name->reff_name;
        }
        if (strtoupper($key) == 'FUMIGASI_TYPE' and !empty($value)) {
          $fumigasi_name = DB::connection('mdm')->table('TM_REFF')->where('reff_tr_id',12)->where('reff_id', $value)->first();
          $fumigasi_name = $fumigasi_name->reff_name;
        }
        if (strtoupper($key) == 'ISO_CODE' and !empty($value)) {
          $package_name = "";
          $package_code = "";
          $commodity_name = "";
          $commodity_unit_code = "";
          $commodity_unit_name = "";
          $commodity_unit_min = "";
          $cont_desc = "";
          $cont_status_desc = "";
          $cont_type_desc = "";
          $equi = DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where('ISO_CODE',$value)->first();
          if (!empty($equi)) {
            foreach ($equi as $keyS => $valueS) {
              if (strtoupper($keyS) != 'ISO_CODE') {
                $newDt[$keyS] = $valueS;
              }
              if (strtoupper($keyS) == 'EQUIPMENT_TYPE_ID') {
                $equipment_type_name = DB::connection('mdm')->table('TM_EQUIPMENT_TYPE')->where('EQUIPMENT_TYPE_ID',$valueS)->first()->equipment_type_name;
              } else if (strtoupper($keyS) == 'EQUIPMENT_UNIT') {
                $get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
                $equipment_unit_code = $get->unit_code;
                $equipment_unit_name = $get->unit_name;
                $equipment_unit_min = $get->unit_min;
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
              }
              if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
                $cont_status_desc = $get->cont_status_desc;
              }
               if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
                $cont_type_desc = $get->cont_type_desc;
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
              }
              if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
                $commodity_name = $get->commodity_name;
              }
              if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_UNIT')->where('UNIT_ID',$valueS)->first();
                $commodity_unit_code = $get->unit_code;
                $commodity_unit_name = $get->unit_name;
                $commodity_unit_min = $get->unit_min;
              }
            }
          }
        } else if(strtoupper($key) == 'SUB_ISO_CODE' and !empty($value)){
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
              }
              if (strtoupper($keyS) == 'COMMODITY_ID' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID',$valueS)->first();
                $commodity_name = $get->commodity_name;
              }
              if (strtoupper($keyS) == 'COMMODITY_UNIT_ID' and !empty($valueS)) {
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
              }

              if (strtoupper($keyS) == 'CONT_STATUS' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_STATUS')->where('CONT_STATUS',$valueS)->first();
                $cont_status_desc = $get->cont_status_desc;
              }

              if (strtoupper($keyS) == 'CONT_TYPE' and !empty($valueS)) {
                $get = DB::connection('mdm')->table('TM_CONT_TYPE')->where('CONT_TYPE',$valueS)->first();
                $cont_type_desc = $get->cont_type_desc;
              }
            }
          }
        } else if(strtolower($key) == 'group_tariff_id' and !empty($value)){
          $group_tariff_name = DB::connection('mdm')->table('TM_GROUP_TARIFF')->where('GROUP_TARIFF_ID',$value)->get()[0]->group_tarif_name;
        } else if(strtolower($key) == 'sub_tariff' and !empty($value)){
          $group_tariff_name = DB::connection('mdm')->table('TM_REFF')->where('REFF_TR_ID', 7)->where('REFF_ID',$value)->get()[0]->reff_name;
        } else if(strtolower($key) == 'nota_id' and !empty($value)){
          $nota = DB::connection('mdm')->table('TM_NOTA')->where('nota_id',$value)->first();
          if (!empty($nota)) {
            foreach ($nota as $keyS => $valueS) {
              if (strtoupper($keyS) != 'SERVICE_CODE') {
                $newDt[$keyS] = $valueS;
              }
            }
          }
        }
      }

      $stacking = DB::connection('mdm')->table('TM_REFF')->where([['reff_id','=', $list->stacking_area],['REFF_TR_ID','=', '11']])->select("reff_name as stacking_area_name")->get();
      if (empty($stacking)) {
        $newDt['stacking_area_name'] = '';
      } else {
        foreach ($stacking as $listS) {
          foreach ($listS as $key => $value) {
            $newDt[$key] = $value;
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
      $newDt['via_name'] = $via_name;
      $newDt['fumigasi_name'] = $fumigasi_name;
      $newDt['plugging_unit_name'] = $plugging_unit_name;
      $response_detil[] = $newDt;
    }
    // return $response_detil;
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
          if ($list['DTL_BL'] == NULL or $list['DTL_BL'] == 'NULL') {
            $setD .= ' detail.DTL_BL := '.$list['DTL_BL'].';';
          }else{
            $setD .= ' detail.DTL_BL := \''.$list['DTL_BL'].'\';';
          }
          $setD .= ' detail.DTL_PKG_ID := '.$list['DTL_PKG_ID'].';';
          $setD .= ' detail.DTL_CMDTY_ID := '.$list['DTL_CMDTY_ID'].';';
          if ($list['DTL_CHARACTER'] == NULL or $list['DTL_CHARACTER'] == 'NULL') {
            $setD .= ' detail.DTL_CHARACTER := '.$list['DTL_CHARACTER'].';';
          }else{
            $setD .= ' detail.DTL_CHARACTER := \''.$list['DTL_CHARACTER'].'\';';
          }
          if ($list['DTL_CONT_SIZE'] == NULL or $list['DTL_CONT_SIZE'] == 'NULL') {
            $setD .= ' detail.DTL_CONT_SIZE := '.$list['DTL_CONT_SIZE'].';';
          }else{
            $setD .= ' detail.DTL_CONT_SIZE := \''.$list['DTL_CONT_SIZE'].'\';';
          }
          if ($list['DTL_CONT_TYPE'] == NULL or $list['DTL_CONT_TYPE'] == 'NULL') {
            $setD .= ' detail.DTL_CONT_TYPE := '.$list['DTL_CONT_TYPE'].';';
          }else{
            $setD .= ' detail.DTL_CONT_TYPE := \''.$list['DTL_CONT_TYPE'].'\';';
          }
          if ($list['DTL_CONT_STATUS'] == NULL or $list['DTL_CONT_STATUS'] == 'NULL') {
            $setD .= ' detail.DTL_CONT_STATUS := '.$list['DTL_CONT_STATUS'].';';
          }else{
            $setD .= ' detail.DTL_CONT_STATUS := \''.$list['DTL_CONT_STATUS'].'\';';
          }
          if ($list['DTL_UNIT_ID'] == NULL or $list['DTL_UNIT_ID'] == 'NULL') {
            $setD .= ' detail.DTL_UNIT_ID := '.$list['DTL_UNIT_ID'].';';
          }else{
            $setD .= ' detail.DTL_UNIT_ID := \''.$list['DTL_UNIT_ID'].'\';';
          }
          $setD .= ' detail.DTL_QTY := '.$list['DTL_QTY'].';';
          if (isset($list['DTL_BM_TYPE']) and $list['DTL_BM_TYPE'] != 'NULL' and $list['DTL_BM_TYPE'] != NULL) {
            $setD .= ' detail.DTL_BM_TYPE := \''.$list['DTL_BM_TYPE'].'\';';
          }else{
            $setD .= ' detail.DTL_BM_TYPE := NULL;';
          }
          if (isset($list['DTL_STACK_AREA']) and $list['DTL_STACK_AREA'] != 'NULL' and $list['DTL_STACK_AREA'] != NULL) {
            $setD .= ' detail.DTL_STACK_AREA := '.$list['DTL_STACK_AREA'].';';
          }else{
            $setD .= ' detail.DTL_STACK_AREA := NULL;';
          }
          if ($list['DTL_TL'] == NULL or $list['DTL_TL'] == 'NULL') {
            // $setD .= ' detail.DTL_TL := '.$list['DTL_TL'].';';
            $setD .= ' detail.DTL_TL := \'N\';';
          }else{
            $setD .= ' detail.DTL_TL := \''.$list['DTL_TL'].'\';';
          }
          $setD .= ' detail.DTL_DATE_IN := '.$list['DTL_DATE_IN'].';';
          $setD .= ' detail.DTL_DATE_OUT_OLD := '.$list['DTL_DATE_OUT_OLD'].';';
          $setD .= ' detail.DTL_DATE_OUT := '.$list['DTL_DATE_OUT'].';';
          $setD .= ' detail.DTL_PFS := \''.$list['DTL_PFS'].'\';' ;
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
          if (isset($list['EQ_QTY_PKG']) and !empty($list['EQ_QTY_PKG'])) {
            $setE .= ' equip.EQ_QTY_PKG := '.$list['EQ_QTY_PKG'].';';
          }
          $setE .= ' list_equip('.$countE.') := equip; ';
        }
    // build eqpt

    // build paysplit
        $paysplit = $input['paysplit'];
        $countP = 0;
        $setP = '';
        foreach ($paysplit as $list) {
          $countP++;
          if ($list['PS_CUST_ID'] == NULL or $list['PS_CUST_ID'] == 'NULL') {
            $setE .= ' paysplit.PS_CUST_ID := '.$list['PS_CUST_ID'].';';
          }else{
            $setE .= ' paysplit.PS_CUST_ID := \''.$list['PS_CUST_ID'].'\';';
          }
          $setE .= ' paysplit.PS_GTRF_ID := '.$list['PS_GTRF_ID'].';';
          $setE .= ' list_paysplit('.$countP.') := paysplit; ';
        }
    // build paysplit

    // build head
        $head = $input['head'];
        if (empty($head['P_USER_ID'])) {
          return ["Success"=>false, 'result_flag' => false, 'result_msg' => 'Fail, user created by is null'];
        }
        if (empty($head['P_SOURCE_ID'])) {
          $head['P_SOURCE_ID'] = 'NPK_BILLING';
        }
        $setH = " P_SOURCE_ID => '".$head['P_SOURCE_ID']."',";
        $setH .= " P_BRANCH_ID => '".$head['P_BRANCH_ID']."',";
        $setH .= " P_BRANCH_CODE => '".$head['P_BRANCH_CODE']."',";
        $setH .= " P_CUSTOMER_ID => '".$head['P_CUSTOMER_ID']."',";
        if (empty($head['P_PBM_INTERNAL']) or $head['P_PBM_INTERNAL'] == 'NULL') {
          $setH .= " P_PBM_INTERNAL => ".$head['P_PBM_INTERNAL'].",";
        }else{
          $setH .= " P_PBM_INTERNAL => '".$head['P_PBM_INTERNAL']."',";
        }
        $setH .= " P_NOTA_ID => '".$head['P_NOTA_ID']."',";
        $setH .= " P_RESTITUTION => '".$head['P_RESTITUTION']."',";
        $setH .= " P_BOOKING_NUMBER => '".$head['P_BOOKING_NUMBER']."',";
        $setH .= " P_REALIZATION => '".$head['P_REALIZATION']."',";
        if (empty($head['P_TRADE']) or $head['P_TRADE'] == 'NULL') {
          $setH .= " P_TRADE => ".$head['P_TRADE'].",";
        }else{
          $setH .= " P_TRADE => '".$head['P_TRADE']."',";
        }
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
      $getConfLink = config('database.connections.eng');
      $link = oci_connect($getConfLink['username'], $getConfLink['password'], $getConfLink['host'].'/'.$getConfLink['database']);
      $sql = " DECLARE
        detail PKG_BILLING.BOOKING_DTL;
        equip PKG_BILLING.BOOKING_EQUIP;
        paysplit PKG_BILLING.BOOKING_PAYSPLIT;
        list_detail PKG_BILLING.BOOKING_DTL_TBL;
        list_equip PKG_BILLING.BOOKING_EQUIP_TBL;
        list_paysplit PKG_BILLING.BOOKING_PAYSPLIT_TBL;
        P_RESULT_FLAG VARCHAR2(200);
        P_RESULT_MSG VARCHAR2(200);
        BEGIN ".$input['detil']." ".$input['eqpt']." ".$input['paysplit'];
      $sql .= " PKG_BILLING.GET_TARIFF( ".$input['head']." );END;";

      // return $sql;
      $stmt = oci_parse($link,$sql);

      // gak nemu buat nerima retun pesan dari prosedur // di ubah cara pengecekannya ngambil dari table TX_LOG
      // oci_bind_by_name($stmt, "P_RESULT_FLAG", $out_status, 40);
      // oci_bind_by_name($stmt, "P_RESULT_MSG", $out_message, 40);
      $query = oci_execute($stmt);

      $head = DB::connection('eng')->table('TX_TEMP_TARIFF_HDR')->where('BOOKING_NUMBER', $input['b_no'])->get();
      if (empty($head)) {
        return ["Success"=>false, 'result_flag' => false, 'result_msg' => 'Fail, prosedur bug', 'no_req' => $input['b_no']];
      }else{
        $head = $head[0];
        $head = (array)$head;
        $result = DB::connection('eng')->table('TX_LOG')->where('TEMP_HDR_ID', $head['temp_hdr_id'])->get();
        $result = $result[0];
        $result = (array)$result;

        $response = ['result_flag' => $result['result_flag'], 'result_msg' => $result['result_msg'], 'no_req' => $input['b_no']];
        if ($result['result_flag'] != 'S') {
          $response["Success"] = false;
        }else{
          $response["Success"] = true;
        }

        $response["query"] = $sql;
      return $response;
      }
  }

  public static function getSimulasiTarif($input) {
    // build head
        $head                       = $input["HEADER"];
        $pbmCek = 'N';
        $countPBM = DB::connection('mdm')->table('TM_PBM_INTERNAL')->where('PBM_ID',$head['P_PBM_ID'])->where('BRANCH_ID',$head['P_BRANCH_ID'])->where('BRANCH_CODE',$head['P_BRANCH_CODE'])->count();
        if ($countPBM > 0) { $pbmCek = 'Y'; }
        $setH                       = [];
        $setH['P_NOTA_ID']          = $head['P_NOTA_ID'];
        $setH['P_BRANCH_ID']        = $head['P_BRANCH_ID'];
        $setH['P_BRANCH_CODE']      = $head['P_BRANCH_CODE'];
        $setH['P_CUSTOMER_ID']      = $head['P_CUSTOMER_ID'];
        $setH['P_RESTITUTION']      = 'N'; // ( N / Y ) DEFAULT N
        $setH['P_PBM_INTERNAL']     = $pbmCek;
        $setH['P_BOOKING_NUMBER']   = $head['P_BOOKING_NUMBER'];
        $setH['P_REALIZATION']      = $head['P_REALIZATION'];
        $setH['P_TRADE']            = $head['P_TRADE'];
        $setH['P_USER_ID']          = $head['P_USER_ID'];
    // build head

    // build detil
        $detil                      = $input["DETAIL"];
        $datein                     = date('Y-m-d');
        $dateout                    = date('Y-m-d', strtotime("+1 day"));
        $setD = [];
        foreach ($detil as $list) {
          $newD                     = [];
          $list                     = (array)$list;
          $newD['DTL_BL']           = $list['DTL_BL'];
          $newD['DTL_PKG_ID']       = $list['DTL_PKG_ID'];
          if ($list['DTL_CMDTY_ID'] == NULL or $list['DTL_CMDTY_ID'] == 'null') {
          $newD['DTL_CMDTY_ID']     = 'null';
          } else {
          $newD['DTL_CMDTY_ID']     = $list['DTL_CMDTY_ID'];
          }
          $newD['DTL_CHARACTER']    = $list['DTL_CHARACTER'];
          $newD['DTL_BM_TYPE']      = $list['DTL_BM_TYPE']; //( BONGKAR / MUAT ) SESUAI INPUTAN DI OM
          //$newD['DTL_STACK_AREA'] = $list['DTL_STACK_AREA']; //( BONGKAR / MUAT ) SESUAI INPUTAN DI OM
          $newD['DTL_CONT_SIZE']    = 'NULL';
          $newD['DTL_CONT_TYPE']    = 'NULL';
          $newD['DTL_CONT_STATUS']  = 'NULL';
          if ($list['DTL_UNIT_ID'] == NULL or $list['DTL_UNIT_ID'] == 'null') {
          $newD['DTL_UNIT_ID']     = 'NULL';
          } else {
          $newD['DTL_UNIT_ID']     = $list['DTL_UNIT_ID'];
          }
          $newD['DTL_QTY']          = $list['DTL_QTY'];
          $newD['DTL_TL']           = $list['DTL_TL'];
          $newD['DTL_DATE_IN']      = empty($datein) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($datein)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
          $newD['DTL_DATE_OUT']     = empty($dateout) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($dateout)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
          $newD['DTL_DATE_OUT_OLD'] = 'NULL';
          $newD['DTL_PFS']          = $list['DTL_PFS'];
          $newD['DTL_STACK_AREA']  = $list['DTL_STACK_AREA_ID'];
          $setD[] = $newD;
        }
    // build detil

    // build eqpt
        $setE = [];
        $eqpt                       = $input["EQUIP"];
        foreach ($eqpt as $list) {
          $newE                     = [];
          $list                     = (array)$list;
          $newE['EQ_TYPE']          = empty($list['EQ_TYPE_ID']) ? 'NULL' : $list['EQ_TYPE_ID'];
          $newE['EQ_QTY']           = empty($list['EQ_QTY']) ? 'NULL' : $list['EQ_QTY'];
          $newE['EQ_UNIT_ID']       = empty($list['EQ_UNIT_ID']) ? 'NULL' : $list['EQ_UNIT_ID'];
          $newE['EQ_GTRF_ID']       = empty($list['EQ_GTRF_ID']) ? 'NULL' : $list['EQ_GTRF_ID'];
          $newE['EQ_PKG_ID']        = empty($list['EQ_PKG_ID']) ? 'NULL' : $list['EQ_PKG_ID'];
          $newE['EQ_QTY_PKG']       = empty($list['EQ_QTY_PKG']) ? 'NULL' : $list['EQ_QTY_PKG'];
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

      $getHS = DB::connection('eng')->table('V_PAY_SPLIT')->where('booking_number',$head['P_BOOKING_NUMBER'])->get();
      foreach ($getHS as $getH){
          $queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$getH->temp_hdr_id."' AND CUSTOMER_ID = '".$getH->customer_id."'";
          $group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));
          $resultD = [];
          foreach ($group_tariff as $grpTrf){
              $grpTrf = (array)$grpTrf;
              $uperD = DB::connection('eng')->table('V_TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$getH->temp_hdr_id)->where('group_tariff_id',$grpTrf['group_tariff_id'])->get();
              $countLine = 0;
              foreach ($uperD as $list){
                  $list = (array)$list;
                  $set_data = [
                      // "uper_hdr_id" => $headU->uper_id,
                      "dtl_line" => $countLine,
                      // "dtl_line_desc" => $list['memoline'],
                      // "dtl_line_context" => , // perlu konfimasi
                      "dtl_service_type" => $list['group_tariff_name'],
                      // Tambahan Mas Adi
                      "dtl_total_tariff" => $list["tariff_uper"],
                      // "dtl_amount" => $list['uper'], // blm fix
                      "dtl_ppn" => $list["ppn"],
                      // "dtl_masa1" => , // cooming soon
                      // "dtl_masa12" => , // cooming soon
                      // "dtl_masa2" => , // cooming soon
                      "dtl_tariff" => $list["tariff"],
                      "dtl_package" => $list["package_name"],
                      "qty" => $list["eq_qty"],
                      "dtl_qty" => $list["qty"],
                      // "dtl_unit" => $list["unit_id"],
                      "dtl_unit_name" => $list["unit_name"],
                      "dtl_group_tariff_id" => $list["group_tariff_id"],
                      "dtl_group_tariff_name" => $list["group_tariff_name"],
                      "dtl_bl" => $list["no_bl"],
                      "dtl_dpp" => $list["dpp"],
                      "dtl_commodity" => $list["commodity_name"],
                      "dtl_equipment" => $list["equipment_name"]
                  ];
                  $resultD[] = $set_data;
              }
            }
          }

      return ["Header"=>$getHS, "Detail"=>$resultD, "calculateRespn" => $tariffResp];
  }
}
