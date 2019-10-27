<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\FileUpload;
use App\Helper\BillingEngine;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrRec;

class StoreController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function api(Request $request) {
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input, $request);
    }

    function validasi($action, $request) {
      $latest   = DB::connection("mdm")->table('JS_VALIDATION')->where('action', 'like', $action."%")->select(["field", "mandatori"])->get();
      $decode   = json_decode(json_encode($latest), true);
      $s        = array();
      foreach ($decode as $data) {
      $s[$data["field"]] = $data["mandatori"];
      }
      $this->validate($request, $s);
      return response($latest);
    }

    function index($input, $request) {
      $this->validasi($input["action"], $request);
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if ($input['start'] != '' && $input['limit'] != '')
        $connect->skip($input['start'])->take($input['limit']);

      $result   = $connect->get();
      $count    = $connect->count();

      return response()->json(["result"=>$result, "count"=>$count]);
    }

    function save($input, $request) {
      $parameter   = $input['parameter'];
      $connect    = \DB::connection($input["db"])->table($input["table"]);
      foreach ($parameter as $value) $connect->insert($parameter);
      return response(["result"=>$parameter, "count"=>count($parameter)]);
    }

    // schema billing_engine
    function storeProfileTariff($input) {
      return BillingEngine::storeProfileTariff($input);
    }
    function storeCustomerProfileTariffAndUper($input){
        return BillingEngine::storeCustomerProfileTariffAndUper($input);
      }
    // schema billing_engine

    // Schema OmCargo
    function storeHdrBm($input) {
      $header        = $input["HEADER"];
      $splitNota     = $input["SPLIT_NOTA"];
      $sewaAlat      = $input["SEWA_ALAT"];
      $retribusiAlat = $input["RETRIBUSI_ALAT"];
      $detail        = $input["DETAIL"];
      $file          = $input["FILE"];
      $datenow       = Carbon::now()->format('m/d/Y');


      if(empty($header["BM_ID"])) {
        $headS       = new TxHdrBm;
      } else {
        $headS       = TxHdrBm::find($header["BM_ID"]);
      }

      // save header
      $headS->BM_BRANCH_ID            = "1";
      $headS->BM_STATUS               = "1";
      $headS->BM_CREATE_BY            = "1";
      $headS->BM_TERMINAL_CODE        = $header["BM_TERMINAL_CODE"];
      $headS->BM_TERMINAL_NAME        = $header["BM_TERMINAL_NAME"];
      $headS->BM_DATE                 = $header['BM_DATE'];
      $headS->BM_PBM_ID               = $header["BM_PBM_ID"];
      $headS->BM_PBM_NAME             = $header["BM_PBM_NAME"];
      $headS->BM_TRADE_TYPE           = $header["BM_TRADE_TYPE"];
      $headS->BM_TRADE_NAME           = $header["BM_TRADE_NAME"];
      $headS->BM_SHIPPING_AGENT_ID    = $header["BM_SHIPPING_AGENT_ID"];
      $headS->BM_SHIPPING_AGENT_NAME  = $header["BM_SHIPPING_AGENT_NAME"];
      $headS->BM_SPLIT                = $header["BM_SPLIT"];
      $headS->BM_CUST_ID              = $header["BM_CUST_ID"];
      $headS->BM_CUST_NAME            = $header["BM_CUST_NAME"];
      $headS->BM_CUST_NPWP            = $header["BM_CUST_NPWP"];
      $headS->BM_CUST_ADDRESS         = $header["BM_CUST_ADDRESS"];
      $headS->BM_PIB_PEB_NO           = $header["BM_PIB_PEB_NO"];
      $headS->BM_PIB_PEB_DATE         = $header["BM_PIB_PEB_DATE"];
      $headS->BM_NPE_SPPB_NO          = $header["BM_NPE_SPPB_NO"];
      $headS->BM_VESSEL_CODE          = $header["BM_VESSEL_CODE"];
      $headS->BM_VESSEL_NAME          = $header["BM_VESSEL_NAME"];
      $headS->BM_VVD_ID               = $header["BM_VVD_ID"];
      $headS->BM_KADE                 = $header["BM_KADE"];
      $headS->BM_VOYIN                = $header["BM_VOYIN"];
      $headS->BM_VOYOUT               = $header["BM_VOYOUT"];
      $headS->BM_ETA                  = $header['BM_ETA'];
      $headS->BM_ETD                  = $header['BM_ETD'];
      $headS->BM_ETB                  = $header['BM_ETB'];
      $headS->BM_ATA                  = $header['BM_ATA'];
      $headS->BM_ATD                  = $header['BM_ATD'];
      $headS->save();
      // save header

      $headS     = TxHdrBm::find($headS->bm_id);
      $reqNumber = $headS->bm_no;

      DB::connection("omcargo")->table('TX_SPLIT_NOTA')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($splitNota as $list) {
        DB::connection("omcargo")->table('TX_SPLIT_NOTA')->insert([
          'req_no'         => $reqNumber,
          'group_tarif_id'   => $list["GROUP_TARIFF_ID"],
          'cust_id'          => $list["CUST_ID"],
          'cust_name'        => $list["CUST_NAME"]
        ]);
      }

      foreach ($retribusiAlat as $list) {
        DB::connection("omcargo")->table('TX_EQUIPMENT')->insert([
          'req_no'            => $reqNumber,
          'group_tariff_id'   => $list["GROUP_TARIFF_ID"],
          'group_tariff_name' => $list["GROUP_TARIFF_NAME"],
          'eq_type_id'        => $list["EQ_TYPE_ID"],
          'eq_type_name'      => $list["EQ_TYPE_NAME"],
          'eq_unit_id'        => $list["EQ_UNIT_ID"],
          'eq_unit_name'      => $list["EQ_UNIT_NAME"],
          'eq_qty'            => $list["EQ_QTY"],
          'package_id'        => $list["PACKAGE_ID"],
          'package_name'      => $list["PACKAGE_NAME"]
        ]);
      }

      // Sewa ALAT
      DB::connection("omcargo")->table('TX_EQUIPMENT')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($sewaAlat as $list) {
        DB::connection("omcargo")->table('TX_EQUIPMENT')->insert([
          'req_no'            => $reqNumber,
          'group_tariff_id'   => $list["GROUP_TARIFF_ID"],
          'group_tariff_name' => $list["GROUP_TARIFF_NAME"],
          'eq_type_id'        => $list["EQ_TYPE_ID"],
          'eq_type_name'      => $list["EQ_TYPE_NAME"],
          'eq_unit_id'        => $list["EQ_UNIT_ID"],
          'eq_unit_name'      => $list["EQ_UNIT_NAME"],
          'eq_qty'            => $list["EQ_QTY"],
          'package_id'        => $list["PACKAGE_ID"],
          'package_name'      => $list["PACKAGE_NAME"]
        ]);
      }

        // Detail
        DB::connection("omcargo")->table('TX_DTL_BM')->where('HDR_BM_ID', '=', $headS->bm_id)->delete();
        foreach ($detail as $list) {
          DB::connection("omcargo")->table('TX_DTL_BM')->insert([
          'hdr_bm_id'         => $headS->bm_id,
          'dtl_bm_type'       => $list["DTL_BM_TYPE"],
          'dtl_bm_type_id'    => $list["DTL_BM_TYPE_ID"],
          'dtl_bm_bl'         => $list["DTL_BM_BL"],
          'dtl_bm_tl'         => $list["DTL_BM_TL"],
          'dtl_pkg_id'        => $list["DTL_PKG_ID"],
          'dtl_pkg_name'      => $list["DTL_PKG_NAME"],
          'dtl_cmdty_id'      => $list["DTL_CMDTY_ID"],
          'dtl_cmdty_name'    => $list["DTL_CMDTY_NAME"],
          'dtl_unit_id'       => $list["DTL_UNIT_ID"],
          'dtl_unit_name'     => $list["DTL_UNIT_NAME"],
          'dtl_cont_size'     => $list["DTL_CONT_SIZE"],
          'dtl_cont_status'   => $list["DTL_CONT_STATUS"],
          'dtl_character_name'=> $list["DTL_CHARACTER_NAME"],
          'dtl_character_id'  => $list["DTL_CHARACTER_ID"],
          'dtl_qty'           => $list["DTL_QTY"]
          ]);
        }

      // Document
      $latest    = DB::connection("omcargo")->table('TX_DOCUMENT')->where('REQ_NO', '=', $reqNumber)->get();
      foreach ($latest as $list) {
        if (!empty($list->doc_path) and file_exists($list->doc_path)) {
             unlink($list->doc_path);
           }
      }

      DB::connection("omcargo")->table('TX_DOCUMENT')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($file as $list) {
        $directory  = 'omkargo/tx_document/'.$headS->bm_id.str_random(5).'/';
        $response   = FileUpload::upload_file($list, $directory);
        if ($response['response'] == true) {
          DB::connection("omcargo")->table('TX_DOCUMENT')->insert([
            'req_no'   => $reqNumber,
            // 'doc_no'   => $reqNumber.str_random(5),
            'doc_no'   => $list["DOC_NO"],
            'doc_name' => $list["PATH"],
            'doc_path' => $response['link']
          ]);
        }
      }

      return response()->json([
        "result" => "Success, store and set Bongkar Muat",
      ]);
    }

    function storeHdrRec($input) {
      $header        = $input["HEADER"];
      $splitNota     = $input["SPLIT_NOTA"];
      $sewaAlat      = $input["SEWA_ALAT"];
      $retribusiAlat = $input["RETRIBUSI_ALAT"];
      $detail        = $input["DETAIL"];
      $file          = $input["FILE"];
      $datenow       = Carbon::now()->format('m/d/Y');


      if(empty($header["REC_ID"])) {
        $headS       = new TxHdrRec;
      } else {
        $headS       = TxHdrRec::find($header["REC_ID"]);
      }

      $headS->REC_DATE           =  $header["REC_DATE"];
      $headS->REC_BRANCH_ID      =  $header["REC_BRANCH_ID"];
      $headS->REC_CUST_ID        =  $header["REC_CUST_ID"];
      $headS->REC_CUST_NAME      =  $header["REC_CUST_NAME"];
      $headS->REC_CUST_ADDRESS   =  $header["REC_CUST_ADDRESS"];
      $headS->REC_TRADE_TYPE     =  $header["REC_TRADE_TYPE"];
      $headS->REC_TRADE_NAME     =  $header["REC_TRADE_NAME"];
      $headS->REC_PIB_PEB_NO     =  $header["REC_PIB_PEB_NO"];
      $headS->REC_PIB_PEB_DATE   =  $header["REC_PIB_PEB_DATE"];
      $headS->REC_NPE_SPPB_NO    =  $header["REC_NPE_SPPB_NO"];
      $headS->REC_SPLIT          =  $header["REC_SPLIT"];
      $headS->REC_VESSEL_CODE    =  $header["REC_VESSEL_CODE"];
      $headS->REC_VESSEL_NAME    =  $header["REC_VESSEL_NAME"];
      $headS->REC_VOYIN          =  $header["REC_VOYIN"];
      $headS->REC_VOYOUT         =  $header["REC_VOYOUT"];
      $headS->REC_VVD_ID         =  $header["REC_VVD_ID"];
      $headS->REC_ETA            =  $header["REC_ETA"];
      $headS->REC_ETB            =  $header["REC_ETB"];
      $headS->REC_ETD            =  $header["REC_ETD"];
      $headS->REC_ATA            =  $header["REC_ATA"];
      $headS->REC_ATD            =  $header["REC_ATD"];
      $headS->REC_TERMINAL_CODE  =  $header["REC_TERMINAL_CODE"];
      $headS->REC_TERMINAL_NAME  =  $header["REC_TERMINAL_NAME"];
      $headS->REC_CREATE_BY      =  $header["REC_CREATE_BY"];
      $headS->REC_CREATE_DATE    =  $header["REC_CREATE_DATE"];
      $headS->REC_STATUS         =  $header["REC_STATUS"];
      $headS->REC_CUST_NPWP      =  $header["REC_CUST_NPWP"];
      $headS->REC_KADE           =  $header["REC_KADE"];
      // $headS->REC_VBOOKING       =  $header["REC_VBOOKING"];
      // $headS->REC_EXTEND_FROM    =  $header["REC_EXTEND_FROM"];
      // $headS->REC_EXTEND_LOOP    =  $header["REC_EXTEND_LOOP"];
      $headS->save();


      $headS     = TxHdrRec::find($headS->rec_id);
      $reqNumber = $headS->rec_no;
      //
      DB::connection("omcargo")->table('TX_SPLIT_NOTA')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($splitNota as $list) {
        DB::connection("omcargo")->table('TX_SPLIT_NOTA')->insert([
          'req_no'           => $reqNumber,
          'group_tarif_id'   => $list["GROUP_TARIFF_ID"],
          'cust_id'          => $list["CUST_ID"],
          'cust_name'        => $list["CUST_NAME"]
        ]);
      }

      // retribusi Alat Tidak Masuk
      foreach ($retribusiAlat as $list) {
        DB::connection("omcargo")->table('TX_EQUIPMENT')->insert([
          'req_no'            => $reqNumber,
          'group_tariff_id'   => $list["GROUP_TARIFF_ID"],
          'group_tariff_name' => $list["GROUP_TARIFF_NAME"],
          'eq_type_id'        => $list["EQ_TYPE_ID"],
          'eq_type_name'      => $list["EQ_TYPE_NAME"],
          'eq_unit_id'        => $list["EQ_UNIT_ID"],
          'eq_unit_name'      => $list["EQ_UNIT_NAME"],
          'eq_qty'            => $list["EQ_QTY"],
          'package_id'        => $list["PACKAGE_ID"],
          'package_name'      => $list["PACKAGE_NAME"]
        ]);
      }
      //
      // Sewa ALAT
      DB::connection("omcargo")->table('TX_EQUIPMENT')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($sewaAlat as $list) {
        DB::connection("omcargo")->table('TX_EQUIPMENT')->insert([
          'req_no'            => $reqNumber,
          'group_tariff_id'   => $list["GROUP_TARIFF_ID"],
          'group_tariff_name' => $list["GROUP_TARIFF_NAME"],
          'eq_type_id'        => $list["EQ_TYPE_ID"],
          'eq_type_name'      => $list["EQ_TYPE_NAME"],
          'eq_unit_id'        => $list["EQ_UNIT_ID"],
          'eq_unit_name'      => $list["EQ_UNIT_NAME"],
          'eq_qty'            => $list["EQ_QTY"],
          'package_id'        => $list["PACKAGE_ID"],
          'package_name'      => $list["PACKAGE_NAME"]
        ]);
      }

      // Detail
        DB::connection("omcargo")->table('TX_DTL_REC')->where('DTL_REC_ID', '=', $headS->bm_id)->delete();
        foreach ($detail as $list) {
          $dtl_in = str_replace("T"," ",$list["DTL_IN"]);
          DB::connection("omcargo")->table('TX_DTL_REC')->insert([
            "hdr_rec_id"        => $headS->bm_id,
            "dtl_pkg_id"        => $list["DTL_PKG_ID"],
            "dtl_pkg_name"      => $list["DTL_PKG_NAME"],
            "dtl_cmdty_id"      => $list["DTL_CMDTY_ID"],
            "dtl_cmdty_name"    => $list["DTL_CMDTY_NAME"],
            "dtl_character_name"=> $list["DTL_CHARACTER_NAME"],
            "dtl_character_id"  => $list["DTL_CHARACTER_ID"],
            "dtl_cont_size"     => $list["DTL_CONT_SIZE"],
            "dtl_unit_id"       => $list["DTL_UNIT_ID"],
            "dtl_unit_name"     => $list["DTL_UNIT_NAME"],
            "dtl_qty"           => $list["DTL_QTY"],
            "dtl_rec_bl"        => $list["DTL_REC_BL"],
            "dtl_in"            => $dtl_in
            // "dtl_create_by"     => "1",
            // "dtl_create_date"   => "2019-10-10",
            // "dtl_bl"            => $list["DTL_BL"],
            // "dtl_cont_type"     => $list["DTL_CONT_TYPE"],
            // "dtl_cont_status"   => $list["DTL_CONT_STATUS"],
            // "dtl_status"        => "",
          ]);
        }

      // Document
      $latest    = DB::connection("omcargo")->table('TX_DOCUMENT')->where('REQ_NO', '=', $reqNumber)->get();
      foreach ($latest as $list) {
        if (!empty($list->doc_path) and file_exists($list->doc_path)) {
             unlink($list->doc_path);
           }
      }

      DB::connection("omcargo")->table('TX_DOCUMENT')->where('REQ_NO', '=', $reqNumber)->delete();
      foreach ($file as $list) {
        $directory  = 'omkargo/tx_document/'.$headS->bm_id.str_random(5).'/';
        $response   = FileUpload::upload_file($list, $directory);
        if ($response['response'] == true) {
          DB::connection("omcargo")->table('TX_DOCUMENT')->insert([
            'req_no'   => $reqNumber,
            'doc_no'   => $list["DOC_NO"],
            'doc_name' => $list["PATH"],
            'doc_path' => $response['link']
            // 'doc_no'   => $reqNumber.str_random(5),
          ]);
        }
      }

      return response()->json([
        "result" => "Success, store and set Receiving",
      ]);
    }

    function saveheaderdetail($input) {
      $data    = $input["data"];
      $count   = count($input["data"]);
      $cek     = $input["HEADER"]["PK"];
      foreach ($data as $data) {
        $val     = $input[$data];
        $connnection  = DB::connection($val["DB"])->table($val["TABLE"]);
        if ($data == "HEADER") {
          $hdr   = json_decode(json_encode($val["VALUE"]), TRUE);
          if ($hdr[0][$cek] == '') {
            foreach ($val["VALUE"] as $value) {
              $insert       = $connnection->insert([$value]);
            }
          } else {
            foreach ($val["VALUE"] as $value) {
              $insert       = $connnection->where($cek,$hdr[0][$cek])->update($value);
            }
          }
          $header   = $connnection->orderby($val["PK"], "desc")->first();
          $header   = json_decode(json_encode($header), TRUE);
        }
        else if($data == "FILE") {
          foreach ($val["VALUE"] as $list) {
            $directory  = $val["DB"].'/'.$val["TABLE"].'/'.str_random(5).'/';
            $response   = FileUpload::upload_file($list, $directory);
            $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_name'=>$list["PATH"],'doc_path'=>$response['link']];
            if ($response['response'] == true) {
              $connnection->insert([$addVal]);
              }
            }
        } else {
          foreach ($val["VALUE"] as $value) {
            $addVal = [$val["FK"][0]=>$header[$val["FK"][1]]]+$value;
            $insert = $connnection->insert([$addVal]);
            }
          }
        }
      return response()->json(["result"=>"Save or Update Success", "header" => $header]);
    }

}
