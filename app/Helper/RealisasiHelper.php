<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\OmCargo\TxHdrNota;

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

    $getH = DB::connection('eng')->table('V_PAY_SPLIT')->where('booking_number',$find->bprp_no)->get();
    $getH = $getH[0];

    $headN = new TxHdrNota;
    // $headN->nota_id = $getH->, // dari triger
    // $headN->nota_no = $getH->, // dari triger
    $headN->nota_org_id = $getH->branch_org_id;
    $headN->nota_cust_id = $getH->customer_id;
    $headN->nota_cust_name = $getH->alt_name;
    $headN->nota_cust_npwp = $getH->npwp;
    $headN->nota_cust_address = $getH->address;
    // $headN->nota_date = $getH->; // ?
    // $headN->nota_amount = $getH->; // ?
    $headN->nota_currency_code = $getH->currency;
    // $headN->nota_status = $getH->; // ?
    // $headN->nota_context = $getH->; // ?
    // $headN->nota_sub_context = $getH->; // ?
    // $headN->nota_terminal = $getH->; // ?
    $headN->nota_branch_id = $getH->branch_id;
    // $headN->nota_vessel_name = $getH->; // ?
    // $headN->nota_faktur_no = $getH->; // ?
    $headN->nota_trade_type = $getH->trade_type;
    // $headN->nota_req_no = $getH->; // ?
    $headN->nota_ppn = $getH->ppn;
    // $headN->nota_paid = $getH->; // pasti null
    // $headN->nota_paid_date = $getH->; // pasti null
    // $headN->rest_payment = $getH->; // pasti null
    $headN->nota_dpp = $getH->dpp;
    $headN->nota_branch_code = $getH->branch_code;
    $headN->save();

    $getD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('temp_hdr_id',$getH->temp_hdr_id)->get();
    foreach ($getD as $list) {
      DB::connection('omcargo')->table('TX_DTL_NOTA')->insert([

      ]);
    }
    return ['result' => 'Success, Confirm BPRP Data!'];
  }
}
