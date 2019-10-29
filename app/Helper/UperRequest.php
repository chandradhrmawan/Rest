<?php

namespace App\Helper;

use Illuminate\Http\Request;
use App\Models\OmCargo\TxHdrUper;
use App\Models\OmCargo\TxPayment;
use Carbon\Carbon;
use App\Helper\ConnectedTOS;

class UperRequest{

	public static function storeUperPayment($input){
    $uper = TxHdrUper::where('uper_no',$input['pay_no'])->first();
    if (empty($uper)) {
      return response()->json(["Success"=>false, "result" => "Fail, uper not found"]);
    }
    if ($uper->uper_paid == 'Y') {
      return response()->json(["Success"=>false, "result" => "Fail, uper already paid"]);
    }
    $datenow    = Carbon::now()->format('Y-m-d');
    $pay = new TxPayment;
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
    $pay->pay_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD')");
    $pay->pay_note = $input['pay_note'];
    $pay->pay_create_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
    $pay->pay_type = $input['pay_type'];
    $pay->save();

    $directory  = 'omcargo/tx_payment/'.$pay->pay_id.'/';
    $response   = FileUpload::upload_file($input['pay_file'], $directory);
    if ($response['response'] == true) {
      TxPayment::where('pay_id',$pay->pay_id)->update([
        'pay_file' => $response['link']
      ]);
    }

    static::updateUperStatus([
      'uper_id' => $uper->uper_id,
      'uper_req_no' => $uper->uper_req_no,
      'uper_paid' => 'Y'
    ]);

    return response()->json(["result" => "Success, paid uper"]);
	}

  private static function updateUperStatus($input){
    $uper = TxHdrUper::where('uper_id',$input['uper_id'])->update(['uper_paid' => $input['uper_paid']]);
    $cekStatus = TxHdrUper::where('uper_req_no',$input['uper_req_no'])->where('uper_paid', 'N')->count();

    if ($cekStatus == 0) { // kirim data request booking ke tos
      ConnectedTOS::sendRequestBooking($input['uper_req_no']);
    }
  }
}