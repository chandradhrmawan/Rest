<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helper\FileUpload;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\RequestBooking;
use App\Helper\UperRequest;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrRec;
use App\Helper\GlobalHelper;
use App\Helper\ConnectedExternalApps;
use App\Helper\RealisasiHelper;
use App\Models\Mdm\TmTruckCompany;

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
      $input  = $request->input();
      if (isset($input['encode']) and $input['encode'] == 'true') {
        $request = json_decode($input['request'], true);
        $input = json_decode($input['request'], true);
        $input['encode'] = 'true';
      }
      $action = $input["action"];
      $request = $request;
      $response = $this->$action($input, $request);

      if (isset($input['encode']) and $input['encode'] == 'true') {
        return response()->json(['response' => json_encode($response)]);
      }else{
        if (isset($response['Success']) and $response['Success'] == false) {
          return response()->json($response, 401);
        }else{
          return response()->json($response);
        }
      }
    }

    public function store_cust($input, $request){
      $set_data = $input['parameter'][0];
      $cek = DB::connection('mdm')->table('TM_CUSTOMER')->where((string)trim('CUSTOMER_ID'), (string)trim($input['parameter'][0]['CUSTOMER_ID']))->count();
      if ($cek == 0) {
        DB::connection('mdm')->table('TM_CUSTOMER')->insert($set_data);
      }else{
        DB::connection('mdm')->table('TM_CUSTOMER')->where((string)trim('CUSTOMER_ID'), (string)trim($input['parameter'][0]['CUSTOMER_ID']))->update($set_data);
      }

      return ['Success' => true, 'result' => 'Success, store customer', 'data' => DB::connection('mdm')->table('TM_CUSTOMER')->where('CUSTOMER_ID', $input['parameter'][0]['CUSTOMER_ID'])->get()];
    }

    public function testlain($input, $request){
      return ConnectedExternalApps::sendRequestBooking(['req_no' => $input['req_no'], 'paid_date' => $input['paid_date']]);
      return ConnectedExternalApps::sendNotaProforma(30);
    }

    public function testview_file(){
      $file = file_get_contents(url("omcargo/tx_payment/5/users.png"));
      return base64_encode($file);
    }

    function confirmUperFromEinvAndSimkue($input, $request){
      return UperRequest::updateUperStatus($input);
    }

    function rejectedProformaNota($input){
      return RealisasiHelper::rejectedProformaNota($input);
    }

    function approvedProformaNota($input){
      return RealisasiHelper::approvedProformaNota($input);
    }

    function confirmRealBM($input){
      return RealisasiHelper::confirmRealBM($input);
    }

    function confirmRealBPRP($input){
      return RealisasiHelper::confirmRealBPRP($input);
    }

    function truckRegistration($input){
      if (empty($input['truck_cust_id'])) {
        $new = new TmTruckCompany;
        $new->comp_name = $input['truck_cust_name'];
        $new->comp_address = $input['truck_cust_address'];
        $new->comp_branch_id = $input['truck_branch_id'];
        $new->save();
        $input['truck_cust_id'] = $new->comp_id;
      }
      $set_data = [
        "truck_plat_no" => strtoupper($input['truck_plat_no']),
        "truck_rfid_code" => strtoupper($input['truck_rfid']),
        "customer_name" => strtoupper($input['truck_cust_name']),
        "customer_address" => strtoupper($input['truck_cust_address']),
        "cdm_customer_id" => strtoupper($input['truck_cust_id']),
        "truck_type" => strtoupper($input['truck_type']),
        "date" => date('d-m-Y', strtotime($input['truck_plat_exp']))
      ];

      $set_data_self = [
        "truck_id" => str_replace(' ','',$input['truck_plat_no']),
        "truck_name" => $input['truck_name'],
        "truck_plat_no" => $input['truck_plat_no'],
        "truck_cust_id" => $input['truck_cust_id'],
        "truck_cust_name" => $input['truck_cust_name'],
        "truck_branch_id" => $input['truck_branch_id'],
        "truck_date" => $input['truck_date'],
        "truck_cust_address" => $input['truck_cust_address'],
        "truck_type" => $input['truck_type'],
        "truck_terminal_code" => $input['truck_terminal_code'],
        "truck_plat_exp" => $input['truck_plat_exp'],
        "truck_stnk_no" => $input['truck_stnk_no'],
        "truck_stnk_exp" => $input['truck_stnk_exp'],
        "truck_rfid" => $input['truck_rfid'],
        "truck_type_name" => $input['truck_type_name']
      ];
      if ($input['type'] == "CREATE") {
        DB::connection('mdm')->table('TM_TRUCK')->insert($set_data_self);
        return ConnectedExternalApps::truckRegistration($set_data);
      }else{
        DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',$input['truck_id'])->update($set_data_self);
        return ConnectedExternalApps::updateTid($set_data);
      }
    }

    function sendTCA($input, $request){
      $head = DB::connection('omcargo')->table('TX_HDR_TCA')->where('tca_id', $input['id'])->get();
      $head = $head[0];
      if ($head->tca_status == 2) {
        return ["Success"=>false, "result" => 'TCA already send!'];
      }
      if ($head->tca_req_type  == 1) {
        $reques = DB::connection('omcargo')->table('TX_HDR_REC')->where('rec_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->rec_vvd_id;
        $vvdName = $reques->rec_vessel_name;
        $vvdVI = $reques->rec_voyin;
        $vvdVO = $reques->rec_voyout;
      }else if ($head->tca_req_type  == 2) {
        $reques = DB::connection('omcargo')->table('TX_HDR_DEL')->where('del_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->del_vvd_id;
        $vvdName = $reques->del_vessel_name;
        $vvdVI = $reques->del_voyin;
        $vvdVO = $reques->del_voyout;
      }else if ($head->tca_req_type  == 3) {
        $reques = DB::connection('omcargo')->table('TX_HDR_BM')->where('bm_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->bm_vvd_id;
        $vvdName = $reques->bm_vessel_name;
        $vvdVI = $reques->bm_voyin;
        $vvdVO = $reques->bm_voyout;
      }
      $loop = DB::connection('omcargo')->table('TX_DTL_TCA')->where('tca_hdr_id', $input['id'])->get();
      $detil = [];
      foreach ($loop as $list) {
        $truck = DB::connection('mdm')->table('TM_TRUCK')->where('truck_id', $list->tca_truck_id)->get();
        if (count($truck) == 0) {
          return ["Success"=>false, 'result_msg' => 'Fail, not found '.$list->tca_truck_id.' on mdm.tm_truck'];
        }
        $truck = $truck[0];
        $terminal = DB::connection('mdm')->table('TM_TERMINAL')->where('terminal_code', $head->tca_terminal_code)->get();
        $terminal = $terminal[0];
        $detil[] = [
          "vNoRequest" => $head->tca_req_no,
          "vTruckId" => $truck->truck_id,
          "vTruckNumber" => $truck->truck_plat_no,
          "vBlNumber" => $head->tca_bl,
          "vTcaCompany" => $head->tca_cust_name,
          "vEi" => $head->tca_req_type == 1 ? 'I' : 'E',
          "vRfidCode" => $truck->truck_rfid,
          "vIdServiceType" => $head->tca_req_type,
          "vServiceType" => $head->tca_req_type_name,
          "vIdTruck" => $truck->truck_id_seq,
          "vIdVvd" => $vvdID,
          "vIdTerminal" => $terminal->terminal_id
        ];
      }
      $set_data = [
        "vVessel" => $vvdName,
        "vVin" => $vvdVI,
        "vVout" => $vvdVO,
        "vNoRequest" => $head->tca_req_no,
        "vCustomerName" => $head->tca_cust_name,
        "vCustomerId" => $head->tca_cust_id,
        "vPkgName" => $head->tca_pkg_name,
        "vQty" => $head->tca_qty,
        "vTon" => $head->tca_qty,
        "vBlNumber" => $head->tca_bl,
        "vBlDate" => date('Y-m-d', strtotime($head->tca_bl_date)),
        "vEi" => $head->tca_req_type == 1 ? 'I' : 'E',
        "vHsCode" => $head->tca_hs_code,
        "vIdServicetype" => $head->tca_req_type,
        "vServiceType" => $head->tca_req_type_name,
        "vIdVvd" => $vvdID,
        "vIdTerminal" => $terminal->terminal_id,
        "detail" => $detil
      ];

      return ConnectedExternalApps::createTCA($set_data, $input['id']);
    }

    function closeTCA($input, $request){
      return ConnectedExternalApps::closeTCA($input);
    }

    function save($input, $request) {
      return GlobalHelper::save($input);
    }

    function publicCreate($input, $request){
      DB::connection($input['schema'])->table($input['table'])->insert($input['set_data']);
      return response()->json([
        "result" => "Success, create ".$input['table']." data",
      ]);
    }

    function publicUpdate($input, $request){
      DB::connection($input['schema'])->table($input['table'])->where($input['condition'])->update($input['set_data']);
      return response()->json([
        "result" => "Success, update ".$input['table']." data",
      ]);
    }

    // RequestBooking
      function sendRequest($input, $request){
        return RequestBooking::sendRequest($input);
      }

      function approvalRequest($input, $request){
        return RequestBooking::approvalRequest($input);
      }
    // RequestBooking

    // BillingEngine
      function storeProfileTariff($input, $request){
        return BillingEngine::storeProfileTariff($input);
      }
      function storeCustomerProfileTariffAndUper($input, $request){
        return BillingEngine::storeCustomerProfileTariffAndUper($input);
      }
      function getSimulasiTarif($input, $request){
        return BillingEngine::getSimulasiTarif($input);
      }
    // BillingEngine

    // UperRequest
      function storePayment($input, $request){
        return UperRequest::storePayment($input);
      }

      function confirmPaymentUper($input, $request){
        return UperRequest::confirmPaymentUper($input);
      }

      function uperSimkeuCek($input, $request){
        return ConnectedExternalApps::uperSimkeuCek($input);
      }

      function notaProformaSimkeuCek($input, $request){
        return ConnectedExternalApps::notaProformaSimkeuCek($input);
      }
    // UperRequest

    // UserAndRoleManagemnt
      function storeRole($input, $request){
        return UserAndRoleManagemnt::storeRole($input);
      }
      function storeRolePermesion($input, $request){
        return UserAndRoleManagemnt::storeRolePermesion($input);
      }
      function storeUser($input, $request){
        return UserAndRoleManagemnt::storeUser($input);
      }
      function changePasswordUser($input, $request){
        return UserAndRoleManagemnt::changePasswordUser($input);
      }
    // UserAndRoleManagemnt

    // Schema OmCargo
    function saveheaderdetail($input) {
      return GlobalHelper::saveheaderdetail($input);
    }

    function update($input){
      return GlobalHelper::update($input);
    }

  function retrievePayment($input) {
    // DB::connection('omcargo')->table('payment')->insert($input["data"]);
    return ["Result"=>"Success"];
  }

  function sendPayment($input) {
    // DB::connection('omcargo')->table('payment')->insert($input["data"]);
    return $input["data"];
  }

  function delHeaderDetail($input) {
    return GlobalHelper::delHeaderDetail($input);
  }
}
