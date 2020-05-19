<?php

namespace App\Helper\Npk;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

use App\Models\OmCargo\TxHdrNota;
use App\Models\OmCargo\TxPayment;
use App\Models\OmCargo\TxHdrUper;

use App\Helper\Globalconfig\FileUpload;
use App\Helper\Npk\ConnectedExternalAppsNPK;

class RealisasiHelper{

  public static function confirmRealBM($input){
    $find = DB::connection('omcargo')->table('TX_HDR_REALISASI')->leftJoin('TX_HDR_BM', 'TX_HDR_REALISASI.REAL_REQ_NO', '=', 'TX_HDR_BM.BM_NO')->where('real_id',$input['id'])->get();
    if (empty($find)) {
      return ['Success' => false, 'result' => 'Fail, not found data!'];
    }
    $find = $find[0];
    $countPBM = DB::connection('mdm')->table('TM_PBM_INTERNAL')->where('PBM_ID',$find->bm_pbm_id)->where('BRANCH_ID',$find->bm_branch_id)->where('BRANCH_CODE',$find->bm_branch_code)->count();
    if ($countPBM > 0) { $pbmCek = 'Y'; }
    else{ $pbmCek = 'N'; }
    // build head
      $setH = [];
      $setH['P_NOTA_ID'] = 13;
      $setH['P_BRANCH_ID'] = $find->real_branch_id;
      $setH['P_BRANCH_CODE'] = $find->real_branch_code;
      $setH['P_CUSTOMER_ID'] = $find->bm_cust_id;
      $setH['P_PBM_INTERNAL'] = $pbmCek;
      $setH['P_BOOKING_NUMBER'] = $find->real_no;
      $setH['P_REALIZATION'] = 'Y';
      $setH['P_RESTITUTION'] = 'N';
      $setH['P_TRADE'] = $find->bm_trade_type;
      $setH['P_USER_ID'] = $find->real_create_by;
    // build head

    // build eqpt
      $setE = [];
      $eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find->real_no)->get();
      foreach ($eqpt as $list) {
        $newE = [];
        $list = (array)$list;
        $newE['EQ_TYPE'] = empty($list['eq_type_id']) ? 'NULL' : $list['eq_type_id'];
        $newE['EQ_QTY'] = empty($list['eq_qty']) ? 'NULL' : $list['eq_qty'];
        $newE['EQ_UNIT_ID'] = empty($list['eq_unit_id']) ? 'NULL' : $list['eq_unit_id'];
        $newE['EQ_GTRF_ID'] = empty($list['group_tariff_id']) ? 'NULL' : $list['group_tariff_id'];
        $newE['EQ_PKG_ID'] = empty($list['package_id']) ? 'NULL' : $list['package_id'];
        $newE['EQ_QTY_PKG'] = empty($list['unit_qty']) ? 'NULL' : $list['unit_qty'];
        $setE[] = $newE;
      }
    // build eqpt

    // build detil
      $setD = [];
      // return $detil = DB::connection('omcargo')->table('TX_DTL_REALISASI')->where('hdr_real_id', $find->real_id)->get();
      $detil = DB::connection('omcargo')->table('TX_DTL_REALISASI')->leftJoin('TX_DTL_BM', 'TX_DTL_REALISASI.DTL_BM_ID', '=', 'TX_DTL_BM.DTL_BM_ID')->where('hdr_real_id', $find->real_id)->get();
      foreach ($detil as $list) {
        $newD = [];
        $list = (array)$list;
        if (empty($list['dtl_bm_id'])) {
          return ['Success' => false, 'result' => 'Fail, not found detil bm on real detil id : '.$list['dtl_real_id']];
        }
        $newD['DTL_VIA'] = 'NULL';
        $getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', 13)->where('BRANCH_ID',$find->bm_branch_id)->where('GROUP_TARIFF_ID', 15)->count();
        if ($getPFS > 0) {
          $newD['DTL_PFS'] = 'Y';
        }else{
          $newD['DTL_PFS'] = 'N';
        }
        $newD['DTL_BL'] = empty($list['dtl_bm_bl']) ? 'NULL' : $list['dtl_bm_bl'];
        $newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
        $newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
        $newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
        $newD['DTL_CONT_SIZE'] = empty($list['dtl_cont_size']) ? 'NULL' : $list['dtl_cont_size'];
        $newD['DTL_CONT_TYPE'] = empty($list['dtl_cont_type']) ? 'NULL' : $list['dtl_cont_type'];
        $newD['DTL_CONT_STATUS'] = empty($list['dtl_cont_status']) ? 'NULL' : $list['dtl_cont_status'];
        $newD['DTL_UNIT_ID'] = empty($list['dtl_unit_id']) ? 'NULL' : $list['dtl_unit_id'];
        $newD['DTL_QTY'] = empty($list['dtl_real_qty']) ? 'NULL' : $list['dtl_real_qty'];
        $newD['DTL_BM_TYPE'] = empty($list['dtl_bm_type']) ? 'NULL' : $list['dtl_bm_type'];
        $newD['DTL_STACK_AREA'] = 'NULL';
        $newD['DTL_TL'] = empty($list['dtl_bm_tl']) ? 'NULL' : $list['dtl_bm_tl'];
        $newD['DTL_DATE_IN'] = 'NULL';
        $newD['DTL_DATE_OUT'] = 'NULL';
        $newD['DTL_DATE_OUT_OLD'] = 'NULL';
        $setD[] = $newD;
      }
    // build detil

    // build paysplit
      $setP = [];
      if ($find->bm_split == 'Y') {
        $paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find->real_req_no)->get();
        $paysplit = (array)$paysplit;
        foreach ($paysplit as $list) {
          $newP = [];
          $list = (array)$list;
          $newP['PS_CUST_ID'] = $list['cust_id'];
          $newP['PS_GTRF_ID'] = $list['group_tarif_id'];
          $setP[] = $newP;
        }
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

    if ($tariffResp['result_flag'] != 'S') {
      return $tariffResp;
    }
    static::migrateNotaData($find->real_no,$find->real_req_no,$find->bm_vessel_name,$find->bm_ukk,$find->bm_terminal_name,'TX_HDR_BM', 'bm_no');
    DB::connection('omcargo')->table('TX_HDR_REALISASI')->where('real_id',$input['id'])->update([
      "real_status" => 2
    ]);
    return ['result' => 'Success, Confirm RBM Data!', 'no_req' => $find->bm_no, 'tariffResp' => $tariffResp];
  }

  public static function confirmRealBPRP($input){
    $find = DB::connection('omcargo')->table('TX_HDR_BPRP')->where('bprp_id',$input['id'])->get();
    if (empty($find)) {
      return ['Success' => false, 'result' => 'Fail, not found data!'];
    }
    $find = $find[0];
    $nota_id = $find->bprp_req_type == 1 ? 14 : 15;
    // build head
      $setH = [];
      $setH['P_NOTA_ID'] = $nota_id;
      $setH['P_BRANCH_ID'] = $find->bprp_branch_id;
      $setH['P_BRANCH_CODE'] = $find->bprp_branch_code;
      $setH['P_CUSTOMER_ID'] = $find->bprp_cust_id;
      $setH['P_PBM_INTERNAL'] = 'N';
      $setH['P_BOOKING_NUMBER'] = $find->bprp_no;
      $setH['P_REALIZATION'] = 'Y';
      $setH['P_RESTITUTION'] = 'N';
      $setH['P_TRADE'] = $find->bprp_trade_type;
      $setH['P_USER_ID'] = $find->bprp_create_by;
    // build head

    // build eqpt
      $setE = [];
      $eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find->bprp_no)->get();
      foreach ($eqpt as $list) {
        $newE = [];
        $list = (array)$list;
        $newE['EQ_TYPE'] = empty($list['eq_type_id']) ? 'NULL' : $list['eq_type_id'];
        $newE['EQ_QTY'] = empty($list['eq_qty']) ? 'NULL' : $list['eq_qty'];
        $newE['EQ_UNIT_ID'] = empty($list['eq_unit_id']) ? 'NULL' : $list['eq_unit_id'];
        $newE['EQ_GTRF_ID'] = empty($list['group_tariff_id']) ? 'NULL' : $list['group_tariff_id'];
        $newE['EQ_PKG_ID'] = empty($list['package_id']) ? 'NULL' : $list['package_id'];
        $newE['EQ_QTY_PKG'] = empty($list['unit_qty']) ? 'NULL' : $list['unit_qty'];
        $setE[] = $newE;
      }
    // build eqpt

    // build detil
      $setD = [];
      $detil = DB::connection('omcargo')->table('TX_DTL_BPRP')->where('hdr_bprp_id', $find->bprp_id)->get();
      foreach ($detil as $list) {
        $newD = [];
        $list = (array)$list;
        $newD['DTL_VIA'] = 'NULL';
        $getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', $nota_id)->where('BRANCH_ID',$find->bprp_branch_id)->where('GROUP_TARIFF_ID', 15)->count();
        if ($getPFS > 0) {
          $newD['DTL_PFS'] = 'Y';
        }else{
          $newD['DTL_PFS'] = 'N';
        }
        $newD['DTL_BL'] = empty($list['dtl_bl']) ? 'NULL' : $list['dtl_bl'];
        $newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
        $newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
        $newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
        $newD['DTL_CONT_SIZE'] = 'NULL';
        $newD['DTL_CONT_TYPE'] = 'NULL';
        $newD['DTL_CONT_STATUS'] = 'NULL';
        $newD['DTL_UNIT_ID'] = empty($list['dtl_req_unit_id']) ? 'NULL' : $list['dtl_req_unit_id'];
        $newD['DTL_QTY'] = empty($list['dtl_in_qty']) ? 'NULL' : $list['dtl_in_qty'];
        $newD['DTL_BM_TYPE'] = 'NULL';
        $newD['DTL_STACK_AREA'] = empty($list['dtl_stacking_type_id']) ? 'NULL' : $list['dtl_stacking_type_id'];
        $newD['DTL_TL'] = 'NULL';
        $newD['DTL_DATE_IN'] = empty($list['dtl_datein']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list['dtl_datein'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
        $newD['DTL_DATE_OUT'] = empty($list['dtl_dateout']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list['dtl_dateout'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
        $newD['DTL_DATE_OUT_OLD'] = 'NULL';
        // $newD['DTL_DATE_OUT_OLD'] = empty($list['date_out_old']) ? 'NULL' : 'to_date(\''.$list['date_out_old'].'\',\'yyyy-MM-dd\')';
        $setD[] = $newD;
      }
    // build detil

    // build paysplit
      $setP = [];
      $cek_slpit = DB::connection('omcargo')->select(DB::raw("SELECT DEL_SPLIT AS SPLIT_STATUS FROM TX_HDR_DEL WHERE DEL_NO = '".$find->bprp_req_no."' UNION ALL SELECT REC_SPLIT AS SPLIT_STATUS FROM TX_HDR_REC WHERE REC_NO = '".$find->bprp_req_no."'"));
      $cek_slpit = $cek_slpit[0];
      if ($cek_slpit->split_status == 'Y') {
        $paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find->bprp_req_no)->get();
        $paysplit = (array)$paysplit;
        foreach ($paysplit as $list) {
          $newP = [];
          $list = (array)$list;
          $newP['PS_CUST_ID'] = $list['cust_id'];
          $newP['PS_GTRF_ID'] = $list['group_tarif_id'];
          $setP[] = $newP;
        }
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

    $tariffResp = BillingEngine::calculateTariff($set_data);

    if ($tariffResp['result_flag'] != 'S') {
      return $tariffResp;
    }
    if ($find->bprp_req_type == 1) {
      $tabReq = "TX_HDR_REC";
      $reqNo  = "rec_no";
    } else if ($find->bprp_req_type == 2) {
      $tabReq = "TX_HDR_DEL";
      $reqNo  = "del_no";
    }
    static::migrateNotaData($find->bprp_no,$find->bprp_req_no,$find->bprp_vessel_name,$find->bprp_ukk,$find->bprp_terminal_name,$tabReq,$reqNo);
    DB::connection('omcargo')->table('TX_HDR_BPRP')->where('bprp_id',$input['id'])->update([
      "bprp_status" => 2
    ]);
    return ['result' => 'Success, Confirm BPRP Data!', 'no_req' => $find->bprp_no, 'tariffResp' => $tariffResp];
  }

  private static function migrateNotaData($booking_number,$req_no,$vessel_name,$ukk,$terminal_id, $tabReq, $reqNo){
    $hdr_id = TxHdrNota::where('nota_real_no',$booking_number)->pluck('nota_id');
    DB::connection('omcargo')->table('TX_DTL_NOTA')->whereIn('nota_hdr_id',$hdr_id)->delete();

    $datenow    = Carbon::now()->format('Y-m-d');
    $query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number = '".$booking_number."'";
    $getHS = DB::connection('eng')->select(DB::raw($query));
    foreach ($getHS as $getH) {
      // store head
        $app_id = DB::connection('omcargo')->table($tabReq)->where($reqNo, $req_no)->get();
        $app_id = $app_id[0];

        $findOldHead = TxHdrNota::where([
          'nota_real_no'=>$booking_number,
          'nota_cust_id'=>$getH->customer_id,
          'nota_branch_code'=>$getH->branch_code
        ])->get();

        if (count($findOldHead) == 1) {
          $headN = TxHdrNota::find($findOldHead[0]->nota_id);
        }else{
          $headN = new TxHdrNota;
        }
        // $headN->nota_id = $getH->, // dari triger
        // $headN->nota_no = $getH->, // dari triger
        $headN->app_id = $app_id->app_id;
        $headN->nota_group_id = $getH->nota_id;
        $headN->nota_org_id = $getH->branch_org_id;
        $headN->nota_cust_id = $getH->customer_id;
        $headN->nota_cust_name = $getH->alt_name;
        $headN->nota_cust_npwp = $getH->npwp;
        $headN->nota_cust_address = $getH->address;
        $headN->nota_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')"); // ?
        $headN->nota_amount = $getH->total; // ?
        $headN->nota_currency_code = $getH->currency;
        $headN->nota_status = 1; // ?
        $headN->nota_context = $getH->nota_context;
        $headN->nota_sub_context = $getH->nota_sub_context;
        $headN->nota_service_code = $getH->nota_service_code;
        $headN->nota_branch_account = $getH->branch_account;
        $headN->nota_tax_code = $getH->tax_code;
        $headN->nota_terminal = $terminal_id;
        $headN->nota_branch_id = $getH->branch_id;
        $headN->nota_branch_code = $getH->branch_code; // add new
        $headN->nota_vessel_name = $vessel_name;
        $headN->nota_ukk = $ukk;
        // $headN->nota_faktur_no = $getH->; // ?
        $headN->nota_trade_type = $getH->trade_type;
        $headN->nota_req_no = $req_no;
        $headN->nota_real_no = $getH->booking_number;
        $headN->nota_ppn = $getH->ppn;
        // $headN->nota_paid = $getH->; // pasti null
        // $headN->nota_paid_date = $getH->; // pasti null
        // $headN->rest_payment = $getH->; // pasti null
        $headN->nota_dpp = $getH->dpp;
        $headN->nota_branch_code = $getH->branch_code;
        $headN->save();
      // store head

      $queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$getH->temp_hdr_id."' AND CUSTOMER_ID = '".$getH->customer_id."'";
      $group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));

      $countLine = 0;
      DB::connection('omcargo')->table('TX_DTL_NOTA')->where('nota_hdr_id', $headN->nota_id)->delete();
      foreach ($group_tariff as $grpTrf){
        $getD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$getH->temp_hdr_id)->where('group_tariff_id',$grpTrf->group_tariff_id)->get();
        foreach ($getD as $list) {
          $countLine++;
          DB::connection('omcargo')->table('TX_DTL_NOTA')->insert([
            "dtl_group_tariff_id" => $list->group_tariff_id,
            "dtl_group_tariff_name" => $list->group_tariff_name,
            "dtl_bl" => $list->no_bl,
            "dtl_dpp" => $list->tariff_cal,
            "dtl_commodity" => $list->commodity_name,
            "dtl_equipment" => $list->equipment_name,
            "dtl_masa_reff" => $list->stack_combine,
            // "nota_dtl_id" => $list->, // dari triger
            "nota_hdr_id" => $headN->nota_id,
            "dtl_line" => $countLine,
            "dtl_line_desc" => $list->memoline,
            // "dtl_line_context" => $list->, // ?
            "dtl_service_type" => $list->group_tariff_name,
            "dtl_amount" => $list->total,
            "dtl_ppn" => $list->ppn,
            "dtl_masa" => $list->day_period,
            // "dtl_masa1" => $list->, // ?
            // "dtl_masa12" => $list->, // ?
            // "dtl_masa2" => $list->, // ?
            "dtl_tariff" => $list->tariff,
            "dtl_package" => $list->package_name,
            "dtl_eq_qty" => $list->eq_qty,
            "dtl_qty" => $list->qty,
            "dtl_unit" => $list->unit_id,
            "dtl_unit_qty" => $list->unit_qty,
            "dtl_unit_name" => $list->unit_name,
            "dtl_sub_tariff" => $list->sub_tariff,
            "dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")
          ]);
        }
      }
    }
  }

  public static function rejectedProformaNota($input){
    $count = TxHdrNota::where('nota_real_no',$input['req_no'])->count();
    if ($count == 0) {
      return ['result' => 'Fail, proforma not found!', 'no_req' => $input['req_no'], 'Success' => false];
    }

    if (!empty($input['file']) and !empty($input['file']['PATH']) and !empty($input['file']['BASE64'])) {
      if (empty($input['proforma_no'])) {
        return ['Success' => false, 'result' => 'Fail, proforma no is null!'];
      }
      $cekOldDoc  = DB::connection('omcargo')->table('TX_DOCUMENT')->where('req_no', $input['proforma_no'])->get();
      if (count($cekOldDoc) > 0) {
        unlink($cekOldDoc[0]->doc_path);
        DB::connection('omcargo')->table('TX_DOCUMENT')->where('req_no', $input['proforma_no'])->delete();
      }
      // Ubah
      $directory  = 'omcargo/TX_DOCUMENT/'.date('d-m-Y').'/';
      $response   = FileUpload::upload_file($input['file'], $directory, "TX_NOTA_REJECT_PROFORMA", $input['req_no']);

      // $directory  = 'omcargo/TX_DOCUMENT/rejectedProformaNota'.$input['req_no'].'/';
      // $response   = FileUpload::upload_file($input['file'], $directory);
      DB::connection('omcargo')->table('TX_DOCUMENT')->insert([
        'REQ_NO' => $input['proforma_no'],
        'DOC_NO' => $input['file']['DOC_NO'],
        'DOC_NAME' => $input['file']['DOC_NAME'],
        'DOC_PATH' => $response['link']
      ]);
    }

    TxHdrNota::where('nota_real_no',$input['req_no'])->update([
      "nota_status"=>3
    ]);
    DB::connection('omcargo')->table('TX_HDR_BPRP')->where('bprp_no',$input['req_no'])->update([
      "bprp_status"=>1
    ]);
    DB::connection('omcargo')->table('TX_HDR_REALISASI')->where('real_no',$input['req_no'])->update([
      "real_status"=>1
    ]);
    return ['result' => 'Success, rejected proforma!', 'no_req' => $input['req_no']];
  }

  public static function approvedProformaNota($input){
    $nota = TxHdrNota::find($input['id']);
    if ($nota->nota_status == 2) {
      return [
        'Success' => false,
        'result' => 'Fail, approved proforma, proforma already approved!',
        'req_no' => $nota->nota_req_no,
        'nota_no' => $nota->nota_no
      ];
    }
    $datenow    = Carbon::now()->format('Y-m-d');
    TxHdrNota::where('nota_id', $input['id'])->update(['nota_status'=>2, 'nota_date'=>\DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")]);
    $count = TxHdrNota::where('nota_real_no', $nota->nota_real_no)->whereIn('nota_status', [1,3])->count();
    $sendNotaTracking = [];
    if ($count == 0) {
      $loop = TxHdrNota::where('nota_real_no', $nota->nota_real_no)->get();
      foreach ($loop as $list) {
        $sendNota = ConnectedExternalAppsNPK::sendNotaProforma($list->nota_id);
        // if ($sendNota['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
        //   return [
        //     'sendNotaErrCode' => $sendNota['arResponseDoc']['esbBody'][0]['errorCode'],
        //     'sendNotaErrMsg' => $sendNota['arResponseDoc']['esbBody'][0]['errorMessage'],
        //     'Success'=> false,
        //     'result' => 'Fail, send proforma to invoice!'
        //   ];
        // }
        $sendNotaTracking[] = $sendNota;
        $pay = TxPayment::where('pay_req_no', $list->nota_req_no)->where('pay_cust_id', $list->nota_cust_id)->first();
        if (!empty($pay)) {
          // ConnectedExternalAppsNPK::notaProformaPutApply($list->nota_id, $pay);
          if ($pay->pay_amount >= $list->nota_amount) {
            TxHdrNota::where('nota_id', $input['id'])->update(['nota_paid'=>'L']);
          }else{
            TxHdrNota::where('nota_id', $input['id'])->update(['nota_paid'=>'I']);
          }
        }else{
          TxHdrNota::where('nota_id', $input['id'])->update(['nota_paid'=>'I']);
        }
      }
    }

    return [
      'Success' => true,
      'result' => 'Success, approved proforma!',
      'req_no' => $nota->nota_req_no,
      'nota_no' => $nota->nota_no,
      'sendNotaTracking' => $sendNotaTracking
    ];
  }

}
