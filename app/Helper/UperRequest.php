<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Illuminate\Http\Request;
use App\Models\OmCargo\TxHdrUper;
use App\Models\OmCargo\TxPayment;
use Carbon\Carbon;
use App\Helper\ConnectedExternalApps;
use App\Helper\RequestBooking;
use App\Models\OmCargo\TxHdrNota;

class UperRequest{

  public static function viewTempUper($input){
      $input['table'] = strtoupper($input['table']);
      $config = RequestBooking::config($input['table']);
      $find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
      $find = (array)$find[0];

      $result = [];

      $query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$find[$config['head_no']]."'";
      $getHS = DB::connection('eng')->select(DB::raw($query));
      foreach ($getHS as $getH){
          $queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$getH->temp_hdr_id."' AND CUSTOMER_ID = '".$getH->customer_id."'";
          $group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));
          $resultD = [];
          foreach ($group_tariff as $grpTrf){
              $grpTrf = (array)$grpTrf;
              $uperD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$getH->temp_hdr_id)->where('group_tariff_id',$grpTrf['group_tariff_id'])->get();
              $countLine = 0;
              foreach ($uperD as $list){
                  $list = (array)$list;
                  $set_data = [
                      // "uper_hdr_id" => $headU->uper_id,
                      "dtl_line" => $countLine,
                      "dtl_line_desc" => $list['memoline'],
                      // "dtl_line_context" => , // perlu konfimasi
                      "dtl_service_type" => $list['group_tariff_name'],
                      "dtl_amount" => $list['total_uper'],
                      "dtl_ppn" => $list["ppn_uper"],
                      "dtl_masa" => $list["day_period"],
                      // "dtl_masa1" => , // cooming soon
                      // "dtl_masa12" => , // cooming soon
                      // "dtl_masa2" => , // cooming soon
                      "dtl_masa_reff" => $list["stack_combine"],
                      "dtl_total_tariff" => $list["tariff_uper"],
                      "dtl_tariff" => $list["tariff"],
                      "dtl_package" => $list["package_name"],
                      "dtl_qty" => $list["qty"],
                      "dtl_eq_qty" => $list["eq_qty"],
                      "dtl_unit" => $list["unit_id"],
                      "dtl_unit_name" => $list["unit_name"],
                      "dtl_group_tariff_id" => $list["group_tariff_id"],
                      "dtl_group_tariff_name" => $list["group_tariff_name"],
                      "dtl_bl" => $list["no_bl"],
                      "dtl_dpp" => $list["tariff_cal_uper"],
                      "dtl_commodity" => $list["commodity_name"],
                      "dtl_equipment" => $list["equipment_name"]
                  ];
                  $resultD[] = $set_data;
              }
          }

          // build head
              $head = [
                'uper_org_id' => $getH->branch_org_id,
                'uper_cust_id' => $getH->customer_id,
                'uper_cust_name' => $getH->alt_name,
                'uper_cust_npwp' => $getH->npwp,
                'uper_cust_address' => $getH->address,
                'uper_amount' => $getH->uper_total,
                'uper_currency_code' => $getH->currency,
                'uper_status' => 'P',
                // Tambahan Mas Adi
                'uper_service_code' => $getH->nota_service_code,
                'uper_branch_account' => $getH->branch_account,
                'uper_context' => $getH->nota_context,
                'uper_sub_context' => $getH->nota_sub_context,
                'uper_terminal_code' => $find[$config['head_terminal_code']],
                'uper_branch_id' => $getH->branch_id,
                'uper_branch_code' => $getH->branch_code,
                'uper_vessel_name' => $find[$config['head_vessel_name']],
                'uper_faktur_no' => '-',
                'uper_trade_type' => $getH->trade_type,
                'uper_req_no' => $getH->booking_number,
                'uper_ppn' => $getH->ppn,
                'uper_percent' => $getH->uper_percent,
                'uper_dpp' => $getH->dpp,
                'uper_nota_id' => $getH->nota_id,
                'uper_req_date' =>  $find[$config['head_date']]
              ];
              if ($config['head_pbm_id'] != null) {
                  $head['uper_pbm_id'] = $find[$config['head_pbm_id']];
              }
              if ($config['head_pbm_name'] != null) {
                  $head['uper_pbm_name'] = $find[$config['head_pbm_name']];
              }
              if ($config['head_shipping_agent_id'] != null) {
                  $head['uper_shipping_agent_id'] = $find[$config['head_shipping_agent_id']];
              }
              if ($config['head_shipping_agent_name'] != null) {
                  $head['uper_shipping_agent_name'] = $find[$config['head_shipping_agent_name']];
              }
              if ($config['head_terminal_name'] != null) {
                  $head['uper_terminal_name'] = $find[$config['head_terminal_name']];
              }
              $head['detil'] = $resultD;
          // build head

          $result[] = $head;
      }

      return [ "Success" => true, "result" => $result];
  }

	public static function storePayment($input){
        if (!isset($input['pay_id']) and empty($input['pay_id'])) {
          $cekPayment = TxPayment::where('pay_no', $input['pay_no'])->where('pay_req_no', $input['pay_req_no'])->count();
          if ($cekPayment > 0) {
            return ["Success"=>false, "result" => "Fail, payment already exist!"];
          }
        }

        if ($input['pay_type'] == 1) {
            $uper = TxHdrUper::where('uper_no',$input['pay_no'])->first();
            if (empty($uper)) {
              return ["Success"=>false, "result" => "Fail, uper not found"];
            }
            if ($uper->uper_paid == 'Y') {
              return ["Success"=>false, "result" => "Fail, uper already paid"];
            }
        } else if ($input['pay_type'] == 2) {
            $nota = TxHdrNota::where('nota_no',$input['pay_no'])->first();
            if (empty($nota)) {
              return ["Success"=>false, "result" => "Fail, nota not found"];
            }
        }

        // store pay
          $datenow    = Carbon::now()->format('Y-m-d');
          if (isset($input['pay_id']) and !empty($input['pay_id'])) {
            $pay = TxPayment::find($input['pay_id']);
            if (!empty($input['pay_file']['PATH']) or !empty($input['pay_file']['BASE64']) or !empty($input['pay_file'])) {
              if (file_exists($pay->pay_file)){
                unlink($pay->pay_file);
              }
            }
          }else{
            $pay = new TxPayment;
            if (empty($input['pay_file']['PATH']) or empty($input['pay_file']['BASE64']) or empty($input['pay_file'])) {
              return ["Success"=>false, "result" => "Fail, file is required"];
            }
          }
          
          if (isset($input['encode']) and $input['encode'] == 'true') {
            $pay->pay_status = 2;
          } else {
            $pay->pay_status = 1;
          }
          if (isset($input['pay_currency'])) {
            $pay->pay_currency = $input['pay_currency'];
          }
          $pay->pay_no = $input['pay_no'];
          $pay->pay_req_no = $input['pay_req_no'];
          $pay->pay_method = $input['pay_method'];
          $pay->pay_cust_id = $input['pay_cust_id'];
          $pay->pay_cust_name = $input['pay_cust_name'];
          $pay->pay_bank_code = $input['pay_bank_code'];
          $pay->pay_bank_name = $input['pay_bank_name'];
          $pay->pay_branch_id = $input['pay_branch_id'];
          // $pay->pay_branch_code = $input['pay_branch_code']; // kemungkinan ditambah
          $pay->pay_account_no = $input['pay_account_no'];
          $pay->pay_account_name = $input['pay_account_name'];
          $pay->pay_amount = $input['pay_amount'];
          $pay->pay_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD HH24:mi:ss')");
          $pay->pay_note = $input['pay_note'];
          $pay->pay_create_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:mi:ss')");
          $pay->pay_type = $input['pay_type'];
          // $pay->pay_sender_bank_code = $input['pay_sender_bank_code'];
          // $pay->pay_sender_bank_name = $input['pay_sender_bank_name'];
          // $pay->pay_sender_account_no = $input['pay_sender_account_no'];
          // $pay->pay_sender_account_name = $input['pay_sender_account_name'];
          $pay->pay_create_by = $input['pay_create_by'];
          $pay->save();

          if (!empty($input['pay_file']['PATH']) and !empty($input['pay_file']['BASE64']) and !empty($input['pay_file'])) {
            $directory  = 'omcargo/tx_payment/'.$pay->pay_id.'/';
            $response   = FileUpload::upload_file($input['pay_file'], $directory);
            if ($response['response'] == true) {
              TxPayment::where('pay_id',$pay->pay_id)->update([
                'pay_file' => $response['link']
              ]);
            }
          }
        // store pay

        $pay = TxPayment::find($pay->pay_id);
        if ($input['pay_type'] == 1){
            if ($pay->pay_status == 1) {
              $res = ConnectedExternalApps::sendUperPutReceipt($uper->uper_id, $pay);
              if ($res['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
                TxPayment::where('pay_id',$pay->pay_id)->update(['pay_status'=>2]);
                static::updateUperStatus([
                  'uper_id' => $uper->uper_id,
                  'uper_req_no' => $uper->uper_req_no,
                  'uper_paid_date' => $input['pay_date'],
                  'uper_no' => $uper->uper_no,
                  'uper_paid' => 'F'
                ]);
                return ["Success"=>false, "result" => "Fail, send receipt", 'pay_no' => $pay->pay_no, 'note' => $res['arResponseDoc']['esbBody'][0]['errorMessage']];
              }
              static::updateUperStatus([
                'uper_id' => $uper->uper_id,
                'uper_req_no' => $uper->uper_req_no,
                'uper_paid_date' => $input['pay_date'],
                'uper_no' => $uper->uper_no,
                'uper_paid' => 'Y'
              ]);
            } else if ($pay->pay_status == 2) {
                static::updateUperStatus([
                  'uper_id' => $uper->uper_id,
                  'uper_req_no' => $uper->uper_req_no,
                  'uper_paid_date' => $input['pay_date'],
                  'uper_no' => $uper->uper_no,
                  'uper_paid' => 'V'
                ]);
            }
            return ["result" => "Success, store paid uper", 'pay_no' => $pay->pay_no];
        } else if ($input['pay_type'] == 2) {
            $res = ConnectedExternalApps::sendNotaPutReceipt($nota->nota_id, $pay);
            ConnectedExternalApps::notaProformaPutApply($nota->nota_id, $pay);
            static::updateNotaStatus([
              'nota_id' => $nota->nota_id,
              'nota_paid' => 'Y'
            ]);
            return ["result" => "Success, store paid nota", 'pay_no' => $pay->pay_no];
        }
	}

  public static function confirmPaymentUper($input){
    $pay = TxPayment::find($input['id']);
    $pay->pay_note = $input['pay_note'];
    if ($input['approved'] == 'true') {
      $pay->pay_status = 1;
    }else{
      $pay->pay_status = 3;
    }
    $pay->save();

    if ($input['approved'] == 'true') {
      $uper = TxHdrUper::where('uper_no',$pay->pay_no)->first();
      $res = ConnectedExternalApps::sendUperPutReceipt($uper->uper_id, $pay);
      if ($res['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
        TxPayment::where('pay_id',$pay->pay_id)->update(['pay_status'=>2]);
        return ["Success"=>false, "result" => "Fail, send receipt", 'pay_no' => $pay->pay_no, 'note' => $res['arResponseDoc']['esbBody'][0]['errorMessage']];
      }
      static::updateUperStatus([
        'uper_id' => $uper->uper_id,
        'uper_req_no' => $uper->uper_req_no,
        'uper_no' => $uper->uper_no,
        'uper_paid' => 'Y'
      ]);
    }
    return ["result" => "Success, confirm uper payment", 'pay_no' => $pay->pay_no];
  }

  public static function updateUperStatus($input){
    if (isset($input['uper_id'])) {
      TxHdrUper::where('uper_id',$input['uper_id'])->update([
        'uper_paid' => $input['uper_paid']
      ]);
      if (isset($input['uper_paid_date'])) {
        TxHdrUper::where('uper_id',$input['uper_id'])->update([
          'uper_paid_date' => \DB::raw("TO_DATE('".$input['uper_paid_date']."', 'YYYY-MM-DD HH24:mi:ss')")
        ]);
      }
      $req_no = $input['uper_req_no'];
    }else if (isset($input['uper_no'])){
      TxHdrUper::where('uper_no',$input['uper_no'])->update([
        'uper_paid' => $input['uper_paid']
      ]);
      $req_no = TxHdrUper::where('uper_no',$input['uper_no'])->first();
      $req_no = $req_no->uper_req_no;
    }
    static::sendRequestBooking(['req_no' => $req_no]);
    return ["result" => "Success, confirm uper", "uper_no" => $input['uper_no'] ];
  }

  private static function sendRequestBooking($input){
    $cekStatus = TxHdrUper::where('uper_req_no',$input['req_no'])->whereIn('uper_paid', ['N', 'W', 'V', 'F'])->count();
    if ($cekStatus == 0) {
      ConnectedExternalApps::sendRequestBooking(['req_no' => $input['uper_req_no'], 'paid_date' => $input['uper_paid_date']]);
    }
  }

  private static function updateNotaStatus($input){
      TxHdrNota::where('nota_id',$input['nota_id'])->update(['nota_paid' => $input['nota_paid']]);
      // ConnectedExternalApps::sendNotaProforma($input);
  }
}
