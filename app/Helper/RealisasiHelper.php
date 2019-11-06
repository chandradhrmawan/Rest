<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OmCargo\TxHdrNota;
use Carbon\Carbon;

class RealisasiHelper{

  public static function confirmRealBPRP($input){
    $find = DB::connection('omcargo')->table('TX_HDR_BPRP')->where('bprp_id',$input['id'])->get();
    if (empty($find)) {
      return ['Success' => false, 'result' => 'Fail, not found data!'];
    }
    $find = $find[0];

    // build head
      $setH = [];
      $setH['P_NOTA_ID'] = $find->bprp_req_type == 1 ? 14 : 15;
      $setH['P_BRANCH_ID'] = $find->bprp_branch_id;
      $setH['P_CUSTOMER_ID'] = $find->bprp_cust_id;
      $setH['P_BOOKING_NUMBER'] = $find->bprp_no;
      $setH['P_REALIZATION'] = 'Y';
      $setH['P_TRADE'] = $find->bprp_trade_type;
      $setH['P_USER_ID'] = $find->bprp_create_by;
    // build head

    // build eqpt
      $setE = [];
      $eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find->bprp_req_no)->get();
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

    // build detil
      $setD = [];
      $detil = DB::connection('omcargo')->table('TX_DTL_BPRP')->where('hdr_bprp_id', $find->bprp_id)->get();
      foreach ($detil as $list) {
        $newD = [];
        $list = (array)$list;
        $newD['DTL_BL'] = empty($list['dtl_bl']) ? 'NULL' : $list['dtl_bl'];
        $newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
        $newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
        $newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
        $newD['DTL_CONT_SIZE'] = 'NULL';
        $newD['DTL_CONT_TYPE'] = 'NULL';
        $newD['DTL_CONT_STATUS'] = 'NULL';
        $newD['DTL_UNIT_ID'] = empty($list['dtl_req_unit_id']) ? 'NULL' : $list['dtl_req_unit_id'];
        $newD['DTL_QTY'] = empty($list['dtl_in_qty']) ? 'NULL' : $list['dtl_in_qty'];
        $newD['DTL_TL'] = 'NULL';
        $newD['DTL_DATE_IN'] = empty($list['dtl_datein']) ? 'NULL' : 'to_date(\''.$list['dtl_datein'].'\',\'yyyy-MM-dd\')';
        $newD['DTL_DATE_OUT'] = empty($list['dtl_dateout']) ? 'NULL' : 'to_date(\''.$list['dtl_dateout'].'\',\'yyyy-MM-dd\')';
        $newD['DTL_DATE_OUT_OLD'] = 'NULL';
        // $newD['DTL_DATE_OUT_OLD'] = empty($list['date_out_old']) ? 'NULL' : 'to_date(\''.$list['date_out_old'].'\',\'yyyy-MM-dd\')';
        $setD[] = $newD;
      }
    // build detil

    // set data
      $set_data = [
        'head' => $setH,
        'detil' => $setD,
        'eqpt' => $setE,
        'paysplit' => []
      ];
    // set data

    $tariffResp = BillingEngine::calculateTariff($set_data);

    if ($tariffResp['result_flag'] != 'S') {
      return $tariffResp;
    }
    $datenow    = Carbon::now()->format('Y-m-d');
    $query = "SELECT * FROM V_PAY_SPLIT a JOIN TX_TEMP_TARIFF_SPLIT b ON a.temp_hdr_id=b.temp_hdr_id and a.customer_id=b.customer_id WHERE a.booking_number= '".$find->bprp_no."'";
    $getHS = DB::connection('eng')->select(DB::raw($query));
    foreach ($getHS as $getH) {
      $headN = new TxHdrNota;
      // $headN->nota_id = $getH->, // dari triger
      // $headN->nota_no = $getH->, // dari triger
      $headN->nota_org_id = $getH->branch_org_id;
      $headN->nota_cust_id = $getH->customer_id;
      $headN->nota_cust_name = $getH->alt_name;
      $headN->nota_cust_npwp = $getH->npwp;
      $headN->nota_cust_address = $getH->address;
      $headN->nota_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')"); // ?
      $headN->nota_amount = $getH->total; // ?
      $headN->nota_currency_code = $getH->currency;
      // $headN->nota_status = $getH->; // ?
      $headN->nota_context = 'BRG'; // sementara sampai ada info lebih lanjut
      $headN->nota_sub_context = 'BRG03'; // sementara sampai ada info lebih lanjut
      $headN->nota_terminal = $find->bprp_terminal_id;
      $headN->nota_branch_id = $getH->branch_id;
      $headN->nota_vessel_name = $find->bprp_vessel_name;
      // $headN->nota_faktur_no = $getH->; // ?
      $headN->nota_trade_type = $getH->trade_type;
      $headN->nota_req_no = $find->bprp_no;
      $headN->nota_ppn = $getH->ppn;
      // $headN->nota_paid = $getH->; // pasti null
      // $headN->nota_paid_date = $getH->; // pasti null
      // $headN->rest_payment = $getH->; // pasti null
      $headN->nota_dpp = $getH->dpp;
      $headN->nota_branch_code = $getH->branch_code;
      $headN->save();

      $getD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$getH->temp_hdr_id)->where('group_tariff_id',$getH->group_tariff_id)->get();
      foreach ($getD as $list) {
        DB::connection('omcargo')->table('TX_DTL_NOTA')->insert([
          // "nota_dtl_id" => $list->, // dari triger
          "nota_hdr_id" => $headN->nota_id,
          // "dtl_line" => $list->, // ?
          // "dtl_line_desc" => $list->, // ?
          // "dtl_line_context" => $list->, // ?
          "dtl_service_type" => $list->group_tariff_name,
          // "dtl_amout" => $list->, // ?
          "dtl_ppn" => $list->ppn,
          // "dtl_masa1" => $list->, // ?
          // "dtl_masa12" => $list->, // ?
          // "dtl_masa2" => $list->, // ?
          "dtl_tariff" => $list->tariff,
          "dtl_package" => $list->package_name,
          "dtl_qty" => $list->qty,
          "dtl_unit" => $list->unit_id,
          "dtl_create_date" => $list->\DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')"),
        ]);
      }
    }
    DB::connection('omcargo')->table('TX_HDR_BPRP')->where('bprp_id',$input['id'])->udpate([
      "bprp_status" => 2
    ]);
    return ['result' => 'Success, Confirm BPRP Data!'];
  }
}