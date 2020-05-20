<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr_ilcs;
use App\Models\Billing\TsTariff_ilcs;
use Carbon\Carbon;
use App\Models\omuster_ilcs\TmUser_ilcs;
use Illuminate\Support\Facades\Hash;
use Firebase\JWT\JWT;
use App\Helper\Jbi\FileUpload;

class ListIndexExt{

  public static function xml($input) {
    $xml  = new \SimpleXMLElement('<root/>');
    $data = json_decode(json_encode($input));
    // $array = array_flip($data);
    // array_walk_recursive($array, array ($xml, 'addChild'));
    return $xml->asXML();
  }

  public static function other($input) {
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

  public static function cektoken($input) {
    $token       = $input["token"];
    try {
    $credentials = JWT::decode($token, env('JWT_SECRET'), ['HS256']);
    } catch(ExpiredException $e) {
      return response()->json([
              'error' => 'Provided token is expired. Please Login'
          ], 400);
    }
    date_default_timezone_set('GMT');
    $data    = DB::connection('omuster_ilcs')->table('TM_USER')->where("USER_ID",$credentials->sub)->get();
    $cektoken= DB::connection('omuster_ilcs')->table('TM_USER')->where("API_TOKEN",$token)->get();
    $time    = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
    $tes     = json_decode(json_encode($data), TRUE);
    $now     = intval(strtotime($time));
    $active  = intval(strtotime($tes[0]["user_active"]));
    $selisih = ($now - $active)/60;

    if ($selisih >= 240) {
      $update  = DB::connection('omuster_ilcs')->table('TM_USER')->where("USER_ID",$credentials->sub)->update(["USER_LOGIN"=>"0","API_TOKEN"=>""]);
      return response()->json([
              'error' => 'Provided token is expired. Please Login'
          ], 400);
    }  else if(empty($cektoken)) {
      return response()->json([
              'error' => 'Error Token. Please Login'
          ], 400);
    } else {
      // Now let's put the user in the request class so that you can grab it from there
      $update  = DB::connection('omuster_ilcs')->table('TM_USER')->where("USER_ID",$credentials->sub)->update(["USER_ACTIVE" => $time]);
      $user    = DB::connection('omuster_ilcs')->table('TM_USER')->where("USER_ID",$credentials->sub)->get();
      return response()->json(["message" => "Login Success"]);
    }
  }

  public static function cleartoken($input) {
    $update = DB::connection('omuster_ilcs')->table('TM_USER')->where('api_token', $input["token"])->update(["USER_LOGIN"=>"0","API_TOKEN"=>""]);
    return ["message" => "Logout"];
  }

  public static function clearlogin($input) {
    $username = $input["USER_NAME"];
    $password = $input["USER_PASSWD"];
    $user = TmUser::where('USER_NAME',$username)->first();
    if (!$user) {
        return response()->json([
            'message' => 'Invalid Username / Password'
        ], 400);
    }

    if (Hash::check($password, $user["user_passwd"])) {
      $update = DB::connection('omuster_ilcs')->table('TM_USER')->where('USER_NAME', $username)->update(["USER_LOGIN"=>"0","API_TOKEN"=>"", "USER_ACTIVE"=>""]);
      return response()->json(["message"=> "Clear Token Success, Login Again"]);
    } else {
      return response()->json([
          'message' => 'Invalid Username / Password'
      ], 400);
    }
  }

    public static function clearsession($input) {
      $database    = DB::connection('omuster_ilcs')->table('TM_USER')->where("USER_LOGIN","1")->get();
      foreach ($database as $data) {
        $time    = date('H:i:s',strtotime('+7 hour',strtotime(date("h:i:s"))));
        $active  = intval(strtotime($data->user_active));
        $now     = intval(strtotime($time));
        $selisih = ($now - $active)/60;
        if ($selisih >= 240) {
          $user[] = [$data->user_name, $selisih];
           DB::connection('omuster_ilcs')->table('TM_USER')->where('USER_ID', $data->user_id)->update(["USER_LOGIN" => "", "API_TOKEN" => ""]);
        }
      }
      if (!empty($user)) {
        return $user;
      }
    }

    public static function unit($input) {
      $tsUnit   = DB::connection('omcargo_ilcs')->table("TS_UNIT")->where("UNIT_SUBJECT", $input["unit_subject"])->get();
      foreach ($tsUnit as $tsUnit) {
        $unit_id[]  = $tsUnit->unit_id;
      }

      $tmUnit   = DB::connection('mdm_ilcs')->table("TM_UNIT")->orderby($input["orderby"][0], $input["orderby"][1])->get();
      foreach ($tmUnit as $tmUnit) {
        if (in_array($tmUnit->unit_id,$unit_id)) {
          $data[] = $tmUnit;
        }
      }

      return $data;
    }

    public static function listproforma($input) {
      $data     = [];
      if (isset($input["db"])) {
      $proforma = DB::connection($input["db"])
                  ->table("TX_HDR_NOTA")
                  ->join("TM_REFF B", "B.REFF_ID", "=", "TX_HDR_NOTA.NOTA_STATUS")
                  ->orderBy("TX_HDR_NOTA.NOTA_ID", "DESC");
      } else {
        $proforma = DB::connection('omcargo_ilcs')
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

          $file   = DB::connection('omcargo_ilcs')
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

    public static function listProfileCustomer($input) {
      $tsUper = DB::connection('eng_ilcs')->table('TS_UPER')->get();
      $newDt = [];

      foreach ($tsUper as $value) {
        $tsCustomerProfile = DB::connection('eng_ilcs')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $value->uper_cust_id)->first();
        if (!empty($tsCustomerProfile)) {
          $joinUperCustomer = DB::connection('eng_ilcs')->table('TS_UPER A')
                              ->join("TS_CUSTOMER_PROFILE b","b.CUST_PROFILE_ID", "=","a.UPER_CUST_ID")
                              ->join("BILLING_mdm_ilcs.TM_CUSTOMER c","b.CUST_PROFILE_ID", "=","c.CUSTOMER_ID")
                              ->where('b.CUST_PROFILE_ID', $value->uper_cust_id)
                              ->first();
          $newDt[] = $joinUperCustomer;
        } else {
          $joinUperCustomer = DB::connection('eng_ilcs')->table('TS_UPER A')
                              ->join("BILLING_mdm_ilcs.TM_CUSTOMER B","B.CUSTOMER_ID", "=","A.UPER_CUST_ID")
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

    public static function listRecBatalCargo($input) {
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
        $header  = DB::connection('omuster_ilcs')->table('TX_HDR_REC_CARGO')->where("REC_CARGO_ID", $detail[0]->rec_cargo_hdr_id)->first();
        $file    = DB::connection('omuster_ilcs')->table('TX_DOCUMENT')->where('REQ_NO', $header->rec_cargo_no)->get();
        return ["HEADER"=>$header, "DETAIL"=>$detail, "FILE"=>$file];
    }

    public static function listDelBatalCargo($input) {
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
      $header  = DB::connection('omuster_ilcs')->table('TX_HDR_DEL_CARGO')->where("DEL_CARGO_ID", $detail[0]->del_cargo_hdr_id)->first();
      $file    = DB::connection('omuster_ilcs')->table('TX_DOCUMENT')->where('REQ_NO', $header->del_cargo_no)->get();
      return ["HEADER"=>$header, "DETAIL"=>$detail, "FILE"=>$file];
    }
}
?>
