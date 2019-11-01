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

    function save($input, $request) {
      $parameter   = $input['parameter'];
      $connect    = \DB::connection($input["db"])->table($input["table"]);
      foreach ($parameter as $value) $connect->insert($parameter);
      return response(["result"=>$parameter, "count"=>count($parameter)]);
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
    function saveheaderdetail($input, $request){
      $data    = $input["data"];
      $count   = count($input["data"]);
      $cek     = $input["HEADER"]["PK"];

      if(!empty($input["HEADER"]["SEQUENCE"])) {
      $sq      = $input["HEADER"]["SEQUENCE"];
    } else {
      $sq      = "Y";
    }

      foreach ($data as $data) {
        $val     = $input[$data];
        $connnection  = DB::connection($val["DB"])->table($val["TABLE"]);
        if ($data == "HEADER") {
          $hdr   = json_decode(json_encode($val["VALUE"]), TRUE);
          if ($hdr[0][$cek] == '' || $sq == "N") {
            foreach ($val["VALUE"] as $value) {
              $insert       = $connnection->insert([$value]);
            }
          } else {
            foreach ($val["VALUE"] as $value) {
              $insert       = $connnection->where($cek,$hdr[0][$cek])->update($value);
            }
          }
          $header   = $connnection->orderby($val["PK"], "desc")->first();
          $header   = json_decode(json_encode($header), TRUE);
        }
        else if($data == "FILE") {
          if ($hdr[0][$cek] != '') {
            $connnection->where($val["FK"][0], $header[$val["FK"][1]]);
            $connnection->delete();
          }
          foreach ($val["VALUE"] as $list) {
            if (isset($list["id"])) {
              unset($list["id"]);
            }
            $directory  = $val["DB"].'/'.$val["TABLE"].'/'.str_random(5).'/';
            $response   = FileUpload::upload_file($list, $directory);
            $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_name'=>$list["PATH"],'doc_path'=>$response['link']];
            if ($response['response'] == true) {
                $connnection->insert([$addVal]);
              }
            }
        } else {
          if ($hdr[0][$cek] != '') {
            $connnection->where($val["FK"][0], $header[$val["FK"][1]]);
            $connnection->delete();
          }
          foreach ($val["VALUE"] as $value) {
            if (isset($value["id"])) {
              unset($value["id"]);
            }
            if (isset($value["DTL_OUT"])) {
              $value["DTL_OUT"] = str_replace("T"," ",$value["DTL_OUT"]);
              $value["DTL_OUT"] = str_replace(".000Z","",$value["DTL_OUT"]);
            }
            if (isset($value["DTL_IN"])) {
              $value["DTL_IN"] = str_replace("T"," ",$value["DTL_IN"]);
            }
            $addVal = [$val["FK"][0]=>$header[$val["FK"][1]]]+$value;
                if(empty($value["id"])) {
                  $connnection->insert([$addVal]);
                }
            }
          }
        }
      return ["result"=>"Save or Update Success"];
    }

    function update($input, $request){
      $connection = DB::connection($input["db"])->table($input["table"]);
      $connection->where($input["where"]);
      $connection->update($input["update"]);
      $data = $connection->get();
      return $data;
    }

    // function test($input, $request){
    //   if (isset($input["VALUE"]["DTL_OUT"])) {
    //     $input["VALUE"]["DTL_OUT"] = str_replace("T"," ",$input["VALUE"]["DTL_OUT"]);
    //     $input["VALUE"]["DTL_OUT"] = str_replace(".000Z","",$input["VALUE"]["DTL_OUT"]);
    //   }
    //   return response($input["VALUE"]["DTL_OUT"]);
    // }
}
