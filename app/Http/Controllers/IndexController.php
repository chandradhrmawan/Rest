<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helper\GlobalHelper;
use App\Helper\ConnectedExternalApps;
use App\Models\OmCargo\TsUnit;

class IndexController extends Controller
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
      $response = $this->$action($input, $request);

      if (isset($input['encode']) and $input['encode'] == 'true') {
        return response()->json(['response' => json_encode($response)]);
      }else{
        return response()->json($response);
      }
    }

    function getListTCA($input, $request){
      return ConnectedExternalApps::getListTCA($input);
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

    function vessel_index($input, $request) {
      return ConnectedExternalApps::vessel_index($input);
    }

    function peb_index($input, $request) {
     return ConnectedExternalApps::peb_index($input);
    }

    function getRealisasionTOS($input, $request){
      return ConnectedExternalApps::realTos($input);
    }

    function join_filter($input) {
      return GlobalHelper::join_filter($input);
    }

    function index($input) {
      return GlobalHelper::index($input);
    }

    function filter($input) {
      return GlobalHelper::filter($input);
    }

    function filterByGrid($input) {
      // $this->validasi($input["action"], $request);
      return GlobalHelper::filterByGrid($input);
    }

    function autoComplete($input) {
      return GlobalHelper::autoComplete($input);
    }

    function viewHeaderDetail($input) {
        return GlobalHelper::viewHeaderDetail($input);
    }

    function join($input) {
        return GlobalHelper::join($input);
    }

    function xml($input, $request) {
        $xml  = new \SimpleXMLElement('<root/>');
        $data = json_decode(json_encode($input));
        // $array = array_flip($data);
        // array_walk_recursive($array, array ($xml, 'addChild'));
        return $xml->asXML();
    }

    function whereQuery($input) {
      return GlobalHelper::whereQuery($input);
    }

    function whereIn($input) {
      return GlobalHelper::whereQuery($input);
    }

    function joinMdmOrder($input) {
      return GlobalHelper::joinMdmOrder($input);
    }

    function testjoin($input) {
      $a = "SELECT tr.*, tr2.REFF_NAME AS SERVICE, tr3.REFF_NAME AS STATUS FROM TR_ROLE tr, TM_REFF tr2, TM_REFF tr3 WHERE tr.ROLE_SERVICE = tr2.REFF_ID AND tr2.REFF_TR_ID = 1 AND tr.ROLE_STATUS = tr3.REFF_ID AND tr3.REFF_TR_ID = 3";
      $b = DB::connection("omuster")->select(DB::raw($a));
      return $b;
      // $detail   = [];
      // $b = [];
      // $data_a = DB::connection("omuster")->table('TR_ROLE as tr')
      // ->join("TM_REFF as tr2", "tr2.REFF_ID", "=", "tr.ROLE_STATUS")
      // ->join("TM_REFF as tr3", "tr3.REFF_ID", "=", "tr.ROLE_SERVICE")
      // ->get();
      //
      // foreach ($data_a as $list) {
      //   $newDt = [];
      //   foreach ($list as $key => $value) {
      //     $newDt[$key] = $value;
      //   }
      //   $detail[] = $newDt;
      // }
      //
      // return $detail;
    }

    function other($input) {
      $raw   = $input["raw"];
      if (!empty($input['value'])) {
        $param = $input["value"];
        $table = DB::connection($input["db"])->select($raw, $param);
      } else {
        $table = DB::connection($input["db"])->select($raw);
      }
      return ["result" => $table];
    }
}
