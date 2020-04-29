<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helper\GlobalHelper;
use App\Helper\ConnectedExternalApps;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\ListIndexExt;
use Firebase\JWT\ExpiredException;
use App\Models\OmUster\TmUser;
use App\Models\OmCargo\TsUnit;
use Firebase\JWT\JWT;
use Illuminate\Support\Facades\Hash;

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
      if ($action == "cektoken" || $action == "clearlogin") {
        return $this->$action($input);
      } else {
        $response = $this->$action($input, $request);
        if (isset($input['encode']) and $input['encode'] == 'true') {
          return response()->json(['response' => json_encode($response)]);
        } else{
          return response()->json($response);
        }
      }

    }

    function getListTCA($input, $request){
      return ConnectedExternalApps::getListTCA($input);
    }

    function listProfileTariffDetil($input, $request){
      return BillingEngine::listProfileTariffDetil($input);
    }

    function listTmNota($input, $request){
      $data = DB::connection('mdm')->table('TM_NOTA')
        ->leftJoin('TS_NOTA', function($join) use ($input)
        {
          $join->on('TS_NOTA.nota_id', '=', 'TM_NOTA.nota_id');
          $join->on('TS_NOTA.branch_id', '=', $input['condition']['branch_id']);
          $join->on('TS_NOTA.branch_code', '=', DB::raw("'".$input['condition']['branch_code']."'"));
        })
        ->leftJoin('TM_REFF', 'TM_REFF.reff_id', '=', 'TM_NOTA.service_code')->select(
        'TM_NOTA.*',
        'case when TS_NOTA.flag_status is null then \'N\' else TS_NOTA.flag_status end flag_status',
        'TM_REFF.reff_name as area'
      );
      if(!empty($input["orderby"][0])) {
        $in        = $input["orderby"];
        $data->orderby($in[0], $in[1]);
      }
      if(!empty($input["condition"]["service_code"])) {
        $data->where("TM_NOTA.service_code",$input["condition"]["service_code"]);
      }
      $data->where(['TM_REFF.REFF_TR_ID' => '8']);
      $count    = $data->count();
      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $data->skip($input['start'])->take($input['limit']);
        }
      }

      if (!empty($input["filter"])) {
      $search   = $input["filter"];
      if (!is_array($search)) {
        $search = json_decode($search, TRUE);
      }
      foreach ($search as $value) {
        if ($value["operator"] == "like")
          $data->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
        else if($value["operator"] == "eq")
          $data->whereDate($value["property"],'=',$value["value"]);
        else if($value["operator"] == "gt")
          $data->whereDate($value["property"],'>=',$value["value"]);
        else if($value["operator"] == "lt")
          $data->whereDate($value["property"],'<=',$value["value"]);
        }
      }

      $data = $data->get();


      return ["result"=>$data, "count"=>$count];
    }

    function listRoleBranch($input, $request) {
      return UserAndRoleManagemnt::listRoleBranch($input);
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

    function xml($input) {
        return ListIndexExt::xml($input);
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

    function other($input) {
      return ListIndexExt::other($input);
    }

    function cektoken($input) {
      return ListIndexExt::cektoken($input);
    }

    function cleartoken($input) {
      return ListIndexExt::cleartoken($input);
    }

    function clearlogin($input) {
      return ListIndexExt::clearlogin($input);
    }

    function clearsession($input) {
      return ListIndexExt::clearsession($input);
    }

    function unit($input) {
      return ListIndexExt::unit($input);
    }

    function listproforma($input) {
      return ListIndexExt::listproforma($input);
    }

    function listProfileCustomer($input) {
      return ListIndexExt::listProfileCustomer($input);
    }

    function listRecBatalCargo($input) {
      return ListIndexExt::listRecBatalCargo($input);
    }

    function listDelBatalCargo($input) {
      return ListIndexExt::listDelBatalCargo($input);
    }

    function check($input) {
      $sequence = DB::connection('omuster')->table("SYS.DUAL")->select("SEQ_TX_HDR_NOTA.NEXTVAL")->get();
      $sequence = $sequence[0]->nextval;

      return $sequence;
    }
}
