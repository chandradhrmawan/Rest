<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helper\GlobalHelper;
use App\Helper\ConnectedExternalApps;
use App\Helper\UserAndRoleManagemnt;
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
        $data->where("service_code",$input["condition"]["service_code"]);
      }
      $data->where(['TM_REFF.REFF_TR_ID' => '8']);
      $count    = $data->count();
      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $data->skip($input['start'])->take($input['limit']);
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

    function other($input) {
      $raw   = $input["raw"];
      if (!empty($input['value'])) {
        $param = $input["value"];
        $table = DB::connection($input["db"])->select($raw, $param);
        $count = count($table);
      } else {
        if (!empty($input['start'])) {
            $data = $raw."ROWNUM <= ".$input['start']."+".$input['limit'].") WHERE R >= ".$input['start'];
            $raw  = $data;
        }
        $table = DB::connection($input["db"])->select($raw);
        $count = count($table);
      }
      return ["result" => $table, "count"=>$count];
    }

    function cektoken($input) {
      $token       = $input["token"];
      try {
      $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
      } catch(ExpiredException $e) {
        return response()->json([
                'error' => 'Provided token is expired. Please Login'
            ], 400);
      }
      date_default_timezone_set('GMT');
      $data    = DB::connection('omuster')->table('TM_USER')->where("USER_ID",$credentials->sub)->get();
      $cektoken= DB::connection('omuster')->table('TM_USER')->where("API_TOKEN",$token)->get();
      $time    = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
      $tes     = json_decode(json_encode($data), TRUE);
      $now     = intval(strtotime($time));
      $active  = intval(strtotime($tes[0]["user_active"]));
      $selisih = ($now - $active)/60;

      if ($selisih >= 240) {
        $update  = DB::connection('omuster')->table('TM_USER')->where("USER_ID",$credentials->sub)->update(["USER_LOGIN"=>"0","API_TOKEN"=>""]);
        return response()->json([
                'error' => 'Provided token is expired. Please Login'
            ], 400);
      }  else if(empty($cektoken)) {
        return response()->json([
                'error' => 'Error Token. Please Login'
            ], 400);
      } else {
        // Now let's put the user in the request class so that you can grab it from there
        $update  = DB::connection('omuster')->table('TM_USER')->where("USER_ID",$credentials->sub)->update(["USER_ACTIVE" => $time]);
        $user    = DB::connection('omuster')->table('TM_USER')->where("USER_ID",$credentials->sub)->get();
        return response()->json(["message" => "Login Success"]);
      }

    }

    function cleartoken($input) {
      $update = DB::connection('omuster')->table('TM_USER')->where('api_token', $input["token"])->update(["USER_LOGIN"=>"0","API_TOKEN"=>""]);
      return ["message" => "Logout"];
    }

    function clearlogin($input) {
      $username = $input["USER_NAME"];
      $password = $input["USER_PASSWD"];
      $user = TmUser::where('USER_NAME',$username)->first();
      if (!$user) {
          return response()->json([
              'message' => 'Invalid Username / Password'
          ], 400);
      }

      if (Hash::check($password, $user["user_passwd"])) {
        $update = DB::connection('omuster')->table('TM_USER')->where('USER_NAME', $username)->update(["USER_LOGIN"=>"0","API_TOKEN"=>"", "USER_ACTIVE"=>""]);
        return response()->json(["message"=> "Clear Token Success, Login Again"]);
      } else {
        return response()->json([
            'message' => 'Invalid Username / Password'
        ], 400);
      }
    }

    function clearsession($input) {
      $database    = DB::connection('omuster')->table('TM_USER')->where("USER_LOGIN","1")->get();
      foreach ($database as $data) {
        $time    = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
        $active  = intval(strtotime($data->user_active));
        $now     = intval(strtotime($time));
        $selisih = ($now - $active)/60;
        if ($selisih >= 240) {
          $user[] = [$data->user_name, $selisih];
           DB::connection('omuster')->table('TM_USER')->where('USER_ID', $data->user_id)->update(["USER_LOGIN" => "", "API_TOKEN" => ""]);
        }
      }
      if (!empty($user)) {
        return $user;
      }
    }

    function unit($input) {
      $tsUnit   = DB::connection('omcargo')->table("TS_UNIT")->where("UNIT_SUBJECT", $input["unit_subject"])->get();
      foreach ($tsUnit as $tsUnit) {
        $unit_id[]  = $tsUnit->unit_id;
      }

      $tmUnit   = DB::connection('mdm')->table("TM_UNIT")->orderby($input["orderby"][0], $input["orderby"][1])->get();
      foreach ($tmUnit as $tmUnit) {
        if (in_array($tmUnit->unit_id,$unit_id)) {
          $data[] = $tmUnit;
        }
      }

      return $data;
    }

    function listproforma($input) {
      $data     = [];
      if (isset($input["db"])) {
      $proforma = DB::connection($input["db"])
                  ->table("TX_HDR_NOTA")
                  ->join("TM_REFF B", "B.REFF_ID", "=", "TX_HDR_NOTA.NOTA_STATUS")
                  ->orderBy("TX_HDR_NOTA.NOTA_ID", "DESC");
      } else {
        $proforma = DB::connection('omcargo')
        ->table("TX_HDR_NOTA")
        ->join("TM_REFF B", "B.REFF_ID", "=", "TX_HDR_NOTA.NOTA_STATUS")
        ->orderBy("TX_HDR_NOTA.NOTA_ID", "DESC");
      }

      if(!empty($input["where"][0])) {
        $proforma->where($input["where"]);
      }

      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $proforma->skip($input['start'])->take($input['limit']);
        }
      }

      $header   = $proforma->get();
      $count    = count($header);

      if (empty($header)) {
        $result  = [];
        $count   = 0;
      }

      foreach ($header as $list) {
        $newDt = [];
        foreach ($list as $key => $value) {
          $newDt[$key] = $value;
        }

          $file   = DB::connection('omcargo')
                    ->table('TX_DOCUMENT')
                    ->where('REQ_NO', $newDt["nota_no"]);

          $detil = $file->get();
          foreach ($detil as $listS) {
            foreach ($listS as $key => $value) {
              $newDt[$key] = $value;
            }
          }
          $result[] = $newDt;
        }

      return ["result"=>$result, "count"=>$count];
    }

    function listProfileCustomer($input) {
    $tsUper = DB::connection('eng')->table('TS_UPER')->get();
    $newDt = [];

    foreach ($tsUper as $value) {
      $tsCustomerProfile = DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $value->uper_cust_id)->first();
      if (!empty($tsCustomerProfile)) {
        $joinUperCustomer = DB::connection('eng')->table('TS_UPER A')
                            ->join("TS_CUSTOMER_PROFILE b","b.CUST_PROFILE_ID", "=","a.UPER_CUST_ID")
                            ->join("BILLING_MDM.TM_CUSTOMER c","b.CUST_PROFILE_ID", "=","c.CUSTOMER_ID")
                            ->where('b.CUST_PROFILE_ID', $value->uper_cust_id)
                            ->first();
        $newDt[] = $joinUperCustomer;
      } else {
        $joinUperCustomer = DB::connection('eng')->table('TS_UPER A')
                            ->join("BILLING_MDM.TM_CUSTOMER B","B.CUSTOMER_ID", "=","A.UPER_CUST_ID")
                            ->where('A.UPER_CUST_ID', $value->uper_cust_id)
                            ->first();

        if (!empty($joinUperCustomer)) {
          $newDt[] = $joinUperCustomer;
        }
      }
    }

    $count = count($newDt);
    return ["result" =>$newDt, "count"=>$count];
  }

    function listRecBatalCargo($input) {
      $select   =
        "A.REC_CARGO_DTL_ID,
        A.REC_CARGO_HDR_ID,
        A.REC_CARGO_DTL_SI_NO,
        A.REC_CARGO_DTL_QTY,
        A.REC_CARGO_DTL_VIA,
        A.REC_CARGO_DTL_PKG_ID,
        A.REC_CARGO_DTL_PKG_NAME,
        A.REC_CARGO_DTL_UNIT_ID,
        A.REC_CARGO_DTL_UNIT_NAME,
        A.REC_CARGO_DTL_CMDTY_ID,
        A.REC_CARGO_DTL_CMDTY_NAME,
        A.REC_CARGO_DTL_CHARACTER_ID,
        A.REC_CARGO_DTL_CHARACTER_NAME,
        A.REC_CARGO_DTL_REC_DATE,
        A.REC_CARGO_DTL_CREATE_DATE,
        A.REC_CARGO_DTL_VIA_NAME,
        A.REC_CARGO_DTL_OWNER,
        A.REC_CARGO_DTL_OWNER_NAME,
        A.REC_CARGO_DTL_PKG_PARENT_ID,
        A.REC_CARGO_DTL_ISCANCELLED,
        (CASE WHEN SUM(B.CANCL_QTY) IS NULL THEN 0 ELSE SUM(B.CANCL_QTY) END) AS jumlah_batal";

      $leftJoinDTLCancelled    =
        "A.REC_CARGO_DTL_SI_NO = B.CANCL_SI AND
         B.CANCL_CMDTY_ID      = A.REC_CARGO_DTL_CMDTY_ID AND
         B.CANCL_PKG_ID        = A.REC_CARGO_DTL_PKG_ID AND
         B.CANCL_PKG_PARENT_ID = A.REC_CARGO_DTL_PKG_PARENT_ID";

      $leftJoinDTLCancelledType = "B.CANCL_HDR_ID = C.CANCELLED_ID AND C.CANCELLED_TYPE = '10'";

      $groupByRaw               =
          "A.REC_CARGO_DTL_ID,
           A.REC_CARGO_HDR_ID,
           A.REC_CARGO_DTL_SI_NO,
           A.REC_CARGO_DTL_QTY,
           A.REC_CARGO_DTL_VIA,
           A.REC_CARGO_DTL_PKG_ID,
           A.REC_CARGO_DTL_PKG_NAME,
           A.REC_CARGO_DTL_UNIT_ID,
           A.REC_CARGO_DTL_UNIT_NAME,
           A.REC_CARGO_DTL_CMDTY_ID,
           A.REC_CARGO_DTL_CMDTY_NAME,
           A.REC_CARGO_DTL_CHARACTER_ID,
           A.REC_CARGO_DTL_CHARACTER_NAME,
           A.REC_CARGO_DTL_REC_DATE,
           A.REC_CARGO_DTL_CREATE_DATE,
           A.REC_CARGO_DTL_VIA_NAME,
           A.REC_CARGO_DTL_OWNER,
           A.REC_CARGO_DTL_OWNER_NAME,
           A.REC_CARGO_DTL_PKG_PARENT_ID,
           A.REC_CARGO_DTL_ISCANCELLED";

      $connect = DB::connection($input["db"])
                ->table($input["table"])
                ->where($input["where"])
                ->leftJoin("TX_DTL_CANCELLED B", DB::raw($leftJoinDTLCancelled))
                ->leftJoin("TX_HDR_CANCELLED C", DB::raw($leftJoinDTLCancelledType))
                ->groupBy($groupByRaw)
                ->select(DB::raw($select));

      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $connect->skip($input['start'])->take($input['limit']);
        }
      }

      $detail  = $connect->get();
      $header  = DB::connection('omuster')->table('TX_HDR_REC_CARGO')->where("REC_CARGO_ID", $detail[0]->rec_cargo_hdr_id)->first();
      $file    = DB::connection('omuster')->table('TX_DOCUMENT')->where('REQ_NO', $header->rec_cargo_no)->get();
      return ["HEADER"=>$header, "DETAIL"=>$detail, "FILE"=>$file];
    }

    function listDelBatalCargo($input) {
      $select   =
        "A.DEL_CARGO_DTL_ID,
         A.DEL_CARGO_HDR_ID,
         A.DEL_CARGO_DTL_SI_NO,
         A.DEL_CARGO_DTL_QTY,
         A.DEL_CARGO_DTL_VIA,
         A.DEL_CARGO_DTL_PKG_ID,
         A.DEL_CARGO_DTL_PKG_NAME,
         A.DEL_CARGO_DTL_UNIT_ID,
         A.DEL_CARGO_DTL_UNIT_NAME,
         A.DEL_CARGO_DTL_CMDTY_ID,
         A.DEL_CARGO_DTL_CMDTY_NAME,
         A.DEL_CARGO_DTL_CHARACTER_ID,
         A.DEL_CARGO_DTL_CHARACTER_NAME,
         A.DEL_CARGO_DTL_DEL_DATE,
         A.DEL_CARGO_DTL_CREATE_DATE,
         A.DEL_CARGO_DTL_STACK_DATE,
         A.DEL_CARGO_DTL_EXT_DATE,
         A.DEL_CARGO_DTL_VIA_NAME,
         A.DEL_CARGO_DTL_OWNER,
         A.DEL_CARGO_DTL_OWNER_NAME,
         A.DEL_CARGO_DTL_PKG_PARENT_ID,
         A.DEL_CARGO_DTL_ISCANCELLED,
         (CASE WHEN SUM(B.CANCL_QTY) IS NULL THEN 0 ELSE SUM(B.CANCL_QTY) END) AS jumlah_batal";

      $leftJoinDTLCancelled    =
        "A.DEL_CARGO_DTL_SI_NO = B.CANCL_SI AND
         B.CANCL_CMDTY_ID      = A.DEL_CARGO_DTL_CMDTY_ID AND
         B.CANCL_PKG_ID        = A.DEL_CARGO_DTL_PKG_ID AND
         B.CANCL_PKG_PARENT_ID = A.DEL_CARGO_DTL_PKG_PARENT_ID";

      $leftJoinDTLCancelledType = "B.CANCL_HDR_ID = C.CANCELLED_ID AND C.CANCELLED_TYPE = '11'";

      $groupByRaw               =
          "A.DEL_CARGO_DTL_ID,
           A.DEL_CARGO_HDR_ID,
           A.DEL_CARGO_DTL_SI_NO,
           A.DEL_CARGO_DTL_QTY,
           A.DEL_CARGO_DTL_VIA,
           A.DEL_CARGO_DTL_PKG_ID,
           A.DEL_CARGO_DTL_PKG_NAME,
           A.DEL_CARGO_DTL_UNIT_ID,
           A.DEL_CARGO_DTL_UNIT_NAME,
           A.DEL_CARGO_DTL_CMDTY_ID,
           A.DEL_CARGO_DTL_CMDTY_NAME,
           A.DEL_CARGO_DTL_CHARACTER_ID,
           A.DEL_CARGO_DTL_CHARACTER_NAME,
           A.DEL_CARGO_DTL_DEL_DATE,
           A.DEL_CARGO_DTL_CREATE_DATE,
           A.DEL_CARGO_DTL_STACK_DATE,
           A.DEL_CARGO_DTL_EXT_DATE,
           A.DEL_CARGO_DTL_VIA_NAME,
           A.DEL_CARGO_DTL_OWNER,
           A.DEL_CARGO_DTL_OWNER_NAME,
           A.DEL_CARGO_DTL_PKG_PARENT_ID,
           A.DEL_CARGO_DTL_ISCANCELLED";

      $connect = DB::connection($input["db"])
                ->table($input["table"])
                ->where($input["where"])
                ->leftJoin("TX_DTL_CANCELLED B", DB::raw($leftJoinDTLCancelled))
                ->leftJoin("TX_HDR_CANCELLED C", DB::raw($leftJoinDTLCancelledType))
                ->groupBy($groupByRaw)
                ->select(DB::raw($select));

      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $connect->skip($input['start'])->take($input['limit']);
        }
      }

      $detail  = $connect->get();
      $header  = DB::connection('omuster')->table('TX_HDR_DEL_CARGO')->where("DEL_CARGO_ID", $detail[0]->del_cargo_hdr_id)->first();
      $file    = DB::connection('omuster')->table('TX_DOCUMENT')->where('REQ_NO', $header->del_cargo_no)->get();
      return ["HEADER"=>$header, "DETAIL"=>$detail, "FILE"=>$file];
    }
}
