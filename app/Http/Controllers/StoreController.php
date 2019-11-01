<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\FileUpload;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\RequestBooking;
use App\Helper\UperRequest;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrRec;
use App\Helper\GlobalHelper;

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
        return response()->json(['response' => $response]);
      }else{
        return response()->json($response);
      }
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
      function storeUperPayment($input, $request){
        return UperRequest::storeUperPayment($input);
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

    // function test($input, $request){
    //   if (isset($input["VALUE"]["DTL_OUT"])) {
    //     $input["VALUE"]["DTL_OUT"] = str_replace("T"," ",$input["VALUE"]["DTL_OUT"]);
    //     $input["VALUE"]["DTL_OUT"] = str_replace(".000Z","",$input["VALUE"]["DTL_OUT"]);
    //   }
    //   return response($input["VALUE"]["DTL_OUT"]);
    // }
}
