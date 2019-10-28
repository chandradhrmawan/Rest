<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\FileUpload;
use App\Helper\BillingEngine;
use App\Helper\RoleManagemnt;
use App\Helper\RequestBooking;
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
      $input  = $this->request->all();
      $request = $request;
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
      function sendRequest($input){
        return RequestBooking::sendRequest($input);
      }

      function approvalRequest($input){
        return RequestBooking::approvalRequest($input);
      }
    // RequestBooking

    // BillingEngine
      function storeProfileTariff($input) {
        return BillingEngine::storeProfileTariff($input);
      }
      function storeCustomerProfileTariffAndUper($input){
        return BillingEngine::storeCustomerProfileTariffAndUper($input);
      }
    // BillingEngine

    // RoleManagemnt
      function storeRole($input){
        return RoleManagemnt::storeRole($input);
      }
      function storeRolePermesion($input){
        return RoleManagemnt::storeRolePermesion($input);
      }
    // RoleManagemnt

    // Schema OmCargo
    function saveheaderdetail($input) {
      $data    = $input["data"];
      $count   = count($input["data"]);
      $cek     = $input["HEADER"]["PK"];
      foreach ($data as $data) {
        $val     = $input[$data];
        $connnection  = DB::connection($val["DB"])->table($val["TABLE"]);
        if ($data == "HEADER") {
          $hdr   = json_decode(json_encode($val["VALUE"]), TRUE);
          if ($hdr[0][$cek] == '') {
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
      return response()->json(["result"=>"Save or Update Success", "header" => $header]);
    }

    function update($input) {
      $connection = DB::connection($input["db"])->table($input["table"]);
      $connection->where($input["where"]);
      $connection->update($input["update"]);
      $data = $connection->get();
      return response()->json($data);
    }

    // function test($input) {
    //   // $dtl_in = str_replace("T"," ",$list["DTL_IN"]);
    //   if (isset($input["value"]["DTL_IN"])) {
    //     $input["value"]["DTL_IN"] = str_replace("T"," ",$input["value"]["DTL_IN"]);
    //   }
    //   return response($input["value"]["DTL_IN"]);
    // }
}
