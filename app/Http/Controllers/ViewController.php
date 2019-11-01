<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;

class ViewController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
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
        return response()->json($response);
      }
    }

    public function view($input, $request) {
      $table  = $input["table"];
      $data   = \DB::connection('order')->table($table)->get();
      return response()->json($data);
    }

    // BillingEngine
      function viewProfileTariff($input, $request)
      {
        return BillingEngine::viewProfileTariff($input);
      }

      function viewCustomerProfileTariff($input, $request)
      {
        return BillingEngine::viewCustomerProfileTariff($input);
      }
    // BillingEngine

    // UserAndRoleManagemnt
      function permissionGet($input, $request)
      {
        return UserAndRoleManagemnt::permissionGet($input);
      }
    // UserAndRoleManagemnt

}
