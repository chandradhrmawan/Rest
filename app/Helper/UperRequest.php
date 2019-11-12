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
                  'uper_context' => 'BRG',
                  'uper_sub_context' => 'BRG03',
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
              return ["Success"=>false, "result" => "Fail, uper not found"];
            }
        }

        $datenow    = Carbon::now()->format('Y-m-d');
        $pay = new TxPayment;
        if (isset($input['encode']) and $input['encode'] == 'true') {
          $pay->pay_status = 2;
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
        $pay->pay_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD')");
        $pay->pay_note = $input['pay_note'];
        $pay->pay_create_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
        $pay->pay_type = $input['pay_type'];
        $pay->pay_dest_bank_code = $input['pay_dest_bank_code'];
        $pay->pay_dest_bank_name = $input['pay_dest_bank_name'];
        $pay->pay_dest_account_no = $input['pay_dest_account_no'];
        $pay->pay_dest_account_name = $input['pay_dest_account_name'];
        $pay->save();
        $directory  = 'omcargo/tx_payment/'.$pay->pay_id.'/';
        $response   = FileUpload::upload_file($input['pay_file'], $directory);
        if ($response['response'] == true) {
          TxPayment::where('pay_id',$pay->pay_id)->update([
            'pay_file' => $response['link']
          ]);
        }
        $pay = TxPayment::find($pay->pay_id);
        if ($input['pay_type'] == 1){
            if ($pay->pay_status == 1) {
              static::updateUperStatus([
                'uper_id' => $uper->uper_id,
                'uper_req_no' => $uper->uper_req_no,
                'uper_paid' => 'Y'
              ]);
            }
            return ["result" => "Success, paid uper"];
        } else if ($input['pay_type'] == 2) {
            return ["result" => "Success, paid nota"];
        }
	}

  public static function confirmPaymentUper($input){
    $pay = TxPayment::find($input['id']);
    if ($input['approved'] == 'true') {
      $pay->pay_status = 1;
    }else{
      $pay->pay_status = 3;
    }
    $pay->save();
    if ($input['approved'] == 'true') {
      $uper = TxHdrUper::where('uper_no',$pay->pay_no)->first();
      static::updateUperStatus([
        'uper_id' => $uper->uper_id,
        'uper_req_no' => $uper->uper_req_no,
        'uper_paid' => 'Y'
      ]);
    }
    return ["result" => "Success, confirm uper payment"];
  }

  private static function updateUperStatus($input){
      $uper = TxHdrUper::where('uper_id',$input['uper_id'])->update(['uper_paid' => $input['uper_paid']]);
      $cekStatus = TxHdrUper::where('uper_req_no',$input['uper_req_no'])->where('uper_paid', 'N')->count();

      if ($cekStatus == 0) {
        ConnectedExternalApps::sendRequestBooking($input['uper_req_no']);
      }
  }
}
