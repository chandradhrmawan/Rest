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
use App\Helper\PlgGenerateTariff;
use App\Helper\PlgRequestBooking;
use App\Helper\PlgConnectedExternalApps;
use App\Helper\PlgFunctTOS;
use App\Helper\PlgEInvo;

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
        $input_user = $input['user'];
        $request = json_decode($input['request'], true);
        $input = json_decode($input['request'], true);
        $input['encode'] = 'true';
        $input['user'] = $input_user;
      }
      // return $input;
      $action = $input["action"];
      $request = $request;
      $response = $this->$action($input, $request);
      $this->saveLogs($action,$input,$response,$input["user"]);
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

    function saveLogs($action,$input, $response, $user){
      if (empty($input['service_branch_id'])) {
        $branch_id = $user->user_branch_id;
        $branch_code = $user->user_branch_code;
      }else{
        $branch_id = $input['service_branch_id'];
        $branch_code = $input['service_branch_code'];
      }
      if ($branch_id == 12) {
        $connection = 'omcargo';
      }else if ($branch_id == 4) {
        $connection = 'omuster';
      }
      DB::connection($connection)->table('TH_LOGS_API_STORE')->insert([
        "create_date" => \DB::raw("TO_DATE('".Carbon::now()->format('Y-m-d H:i:s')."', 'YYYY-MM-DD HH24:mi:ss')"),
        "action" => $action,
        "branch_id" => $branch_id,
        "branch_code" => $branch_code,
        "json_request" => json_encode($input),
        "json_response" => json_encode($response),
        "create_name" => $user->user_full_name
      ]);
    }

    public function storeTsNota($input, $request){
      if ($input['flag_status'] == 'Y') {
        $cekActive = DB::connection('mdm')->table('TS_NOTA')->where([
          "flag_status" => $input['flag_status'],
          "branch_id" => $input['branch_id'],
          "branch_code" => $input['branch_code'],
          "nota_id_parent" => $input['nota_id_parent']
        ])->count();
        if ($cekActive > 0 ) {
          return [ "Success" => false, "response" => "Fail, tidak boleh ada 2 data yang active" ];
        }
      }

      $setData = [
        "branch_id" => $input['branch_id'],
        "branch_code" => $input['branch_code'],
        "nota_id" => $input['nota_id'],
        "nota_id_parent" => $input['nota_id_parent']
      ];

      $cek = DB::connection('mdm')->table('TS_NOTA')->where($setData)->count();
      if (empty($input['flag_status'])) {
        $input['flag_status'] = 'N';
      }else if (!empty($input['flag_status'])) {
        DB::connection('mdm')->table('TS_NOTA')->where([
        "branch_id" => $input['branch_id'],
        "nota_id" => $input['nota_id'],
        "nota_id_parent" => $input['nota_id_parent']
        ])->update(["flag_status" => $input['flag_status']]);
      }
      $strData = [
        "branch_id" => $input['branch_id'],
        "branch_code" => $input['branch_code'],
        "nota_id_parent" => $input['nota_id_parent'],
        "nota_id" => $input['nota_id'],
        "flag_status" => $input['flag_status']
      ];
      if ($cek > 0) {
        DB::connection('mdm')->table('TS_NOTA')->where($setData)->update($strData);
      }else{
        DB::connection('mdm')->table('TS_NOTA')->insert($strData);
      }
      return [ "response" => "Success, store data" ];
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
      $config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', 20)->first();
      $config = json_decode($config->api_set, true);
      return PlgFunctTOS::sendRequestBookingPLG(['id' => 62, 'table' => 'TX_HDR_TL', 'config' => $config]);
      return PlgConnectedExternalApps::flagRealisationRequest();
      $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_id',114)->first();
      $nota = (array)$nota;
      return PlgConnectedExternalApps::sendInvProforma([
        'nota' => $nota,
        'id' => 170
      ]);
    }

    // PLG
      function simulationTariffPLG($input, $request){
        return PlgGenerateTariff::simulationTariffPLG($input);
      }

      function sendRequestPLG($input, $request){
        return PlgRequestBooking::sendRequestPLG($input);
      }

      function approvalRequestPLG($input, $request){
        return PlgRequestBooking::approvalRequestPLG($input);
      }

      function confirmRealisasion($input, $request){
        return PlgRequestBooking::confirmRealisasion($input);
      }

      function approvalProformaPLG($input, $request){
        return PlgRequestBooking::approvalProformaPLG($input);
      }

      function storePaymentPLG($input, $request){
        return PlgRequestBooking::storePaymentPLG($input);
      }

      function getRealPLG($input, $request){
        return PlgFunctTOS::getRealPLG($input);
      }

    // PLG

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
      $cekoldtmtruckcompany = DB::connection('mdm')->table('TM_TRUCK')->where('truck_cust_id',$input['truck_cust_id'])->first();
      if (empty($cekoldtmtruckcompany) or !is_numeric($cekoldtmtruckcompany->truck_cust_id)) {
        $new = new TmTruckCompany;
        $new->comp_name = $input['truck_cust_name'];
        $new->comp_address = $input['truck_cust_address'];
        $new->comp_branch_id = $input['truck_branch_id'];
        $new->comp_branch_code = $input['truck_branch_code'];
        $new->save();
        $input['truck_cust_id'] = $new->comp_id;
      }

      $terminal = DB::connection('mdm')->table('TM_TERMINAL')->where([
        'branch_id' => $input['truck_branch_id'],
        'branch_code' => $input['truck_branch_code']
      ])->first();

      $set_data = [
        "terminal_id" => $terminal->terminal_id,
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
        "truck_branch_code" => $input['truck_branch_code'],
        "truck_date" => $input['truck_date'],
        "truck_cust_address" => $input['truck_cust_address'],
        "truck_type" => $input['truck_type'],
        "truck_terminal_code" => $terminal->terminal_id,
        "truck_plat_exp" => $input['truck_plat_exp'],
        "truck_stnk_no" => $input['truck_stnk_no'],
        "truck_stnk_exp" => $input['truck_stnk_exp'],
        "truck_rfid" => $input['truck_rfid'],
        "truck_type_name" => $input['truck_type_name']
      ];
      if ($input['type'] == "CREATE") {
        DB::connection('mdm')->table('TM_TRUCK')->insert($set_data_self);
        $res = ConnectedExternalApps::truckRegistration($set_data);
      }else{
        $tid = DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',$input['truck_id'])->first();
        $set_data['truck_id'] = $tid->truck_id;
        DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',$input['truck_id'])->update($set_data_self);
        $res = ConnectedExternalApps::updateTid($set_data);
      }
        $tid = DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',$input['truck_id'])->first();
        $set_data['truck_id'] = $tid->truck_id;
        
      $res['getTruckPrimaryIdTos'] = ConnectedExternalApps::getTruckPrimaryIdTos($set_data);
      return $res;
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
      function storeProfileTariffDetil($input, $request){
        return BillingEngine::storeProfileTariffDetil($input);
      }
      function deleteProfileTariffDetil($input, $request){
        return BillingEngine::deleteProfileTariffDetil($input);
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
      function deleteRoleBranch($input, $request){
        return UserAndRoleManagemnt::deleteRoleBranch($input);
      }
      function storeRoleBranch($input, $request){
        return UserAndRoleManagemnt::storeRoleBranch($input);
      }
    // UserAndRoleManagemnt

    // Schema OmCargo
    function saveheaderdetail($input) {
      return GlobalHelper::saveheaderdetail($input);
    }

    function update($input){
      return GlobalHelper::update($input);
    }

  function delHeaderDetail($input) {
    return GlobalHelper::delHeaderDetail($input);
  }

  function hitScheduler($input) {
    return PlgConnectedExternalApps::flagRealisationRequest($input);
  }

  function hitPlacement($input) {
    return PlgConnectedExternalApps::getUpdatePlacement($input);
  }


  function ujiCoba($input) {
    $notaId = "22";
    $id     = "72";
    $config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $notaId)->first();
    $config = json_decode($config->api_set, true);
    $table  = $config['head_table'];


    return PlgFunctTOS::sendRequestBookingPLG(['id' => $id, 'table' =>$table, 'config' => $config]);
  }

}
