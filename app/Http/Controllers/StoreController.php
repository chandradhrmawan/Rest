<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

use App\Helper\BillingEngine;

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
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input, $request);
    }

    function validasi($action, $request) {
      $latest   = DB::connection("mdm")->table('JS_VALIDATION')->where('action', 'like', $action."%")->select(["field", "mandatori"])->get();
      $decode   = json_decode(json_encode($latest), true);
      $s        = array();
      foreach ($decode as $data) {
      $s[$data["field"]] = $data["mandatori"];
      }
      $this->validate($request, $s);
      return response($latest);
    }

    function index($input, $request) {
      $this->validasi($input["action"], $request);
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if ($input['start'] != '' && $input['limit'] != '')
        $connect->skip($input['start'])->take($input['limit']);

      $result   = $connect->get();
      $count    = $connect->count();

      return response()->json(["result"=>$result, "count"=>$count]);
    }

    function save($input, $request) {
      $parameter   = $input['parameter'];
      $connect    = \DB::connection($input["db"])->table($input["table"]);
      foreach ($parameter as $value) $connect->insert($parameter);
      return response(["result"=>$parameter, "count"=>count($parameter)]);
    }

    // schema billing_engine
      function storeProfileTariff($input) {
        return BillingEngine::storeProfileTariff($input);
      }
      function storeCustomerProfileTariffAndUper($input){
        return BillingEngine::storeCustomerProfileTariffAndUper($input);
      }
    // schema billing_engine

}
