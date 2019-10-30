<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class GlobalHelper{

  public static function viewHeaderDetail($input) {
      $data    = $input["data"];
      $count   = count($input["data"]);
      $pk      = $input["HEADER"]["PK"][0];
      $pkVal   = $input["HEADER"]["PK"][1];
      foreach ($data as $data) {
        $val     = $input[$data];
        $connnection  = DB::connection($val["DB"])->table($val["TABLE"]);
          if ($data == "HEADER") {
             $header   = $connnection->where($pk, "like", $pkVal)->get();
             $header   = json_decode(json_encode($header), TRUE);
             $vwdata = ["HEADER" => $header];
          }

            else {
            $fk      = $val["FK"][0];
            $fkhdr   = $header[0][$val["FK"][1]];
            $detail  = $connnection->where($fk, "like", $fkhdr)->get();
            $vwdata[$data] = $detail;
          }
      }
      return response()->json($vwdata);
    }

  public static function join_filter($action) {
      $result    = [];
      $table     = $action["table"];
      $schema    = $action["schema"];
      $head      = DB::connection($schema[0])->table($table[0]);

      for ($i=0; $i < count($action['filter_1']["data"]); $i++) {
      if ($action['filter_1']["type"] == "or") {
        if ($action['filter_1']["operator"][$i] != "like") $head->orWhere($action['filter_1']["data"][$i],$action['filter_1']["operator"][$i],$action['filter_1']["value"][$i]);
        else $head->orwhere($action['filter_1']["data"][$i], 'like', '%'.$action['filter_1']["value"][$i].'%');
      }

      else if($action['filter_1']["type"] == "and") $head->Where($action['filter_1']["data"][$i],$action['filter_1']["operator"][$i],$action['filter_1']["value"][$i]);
      else $head->orwhere($action['filter_1']["data"][$i], 'like', '%'.$action['filter_1']["value"][$i].'%');
      }

      $head = $head->get();
      foreach ($head as $list) {
        $newDt = [];
        foreach ($list as $key => $value) {
          $newDt[$key] = $value;
        }
        $relasia = $action['relation'][0];
        if (count($action["relation"]) > 1) {
          $relasib = $action['relation'][1];
          $detil = DB::connection($schema[1])->table($table[1])->where($action['relation'][1],  $list->$relasia);
        } else {
          $detil = DB::connection($schema[1])->table($table[1])->where($action['relation'][0],  $list->$relasia);
        }

        for ($i=0; $i < count($action['filter_2']["data"]); $i++) {
        if ($action['filter_2']["type"] == "or") {
          if ($action['filter_2']["operator"][$i] != "like") $detil->orWhere($action['filter_2']["data"][$i],$action['filter_2']["operator"][$i],$action['filter_2']["value"][$i]);
          else $detil->orwhere($action['filter_2']["data"][$i], 'like', '%'.$action['filter_2']["value"][$i].'%');
        }

        else if($action['filter_2']["type"] == "and") $detil->Where($action['filter_2']["data"][$i],$action['filter_2']["operator"][$i],$action['filter_2']["value"][$i]);
        else $detil->orwhere($action['filter_2']["data"][$i], 'like', '%'.$action['filter_2']["value"][$i].'%');
        }

        $detil = $detil->get();
        foreach ($detil as $listS) {
          foreach ($listS as $key => $value) {
            $newDt[$key] = $value;
          }
        }
        $result[] = $newDt;
      }
      return response()->json($head);
    }

  public static function index($input) {
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if ($input['start'] != '' && $input['limit'] != '')
        $connect->skip($input['start'])->take($input['limit']);

      $result   = $connect->get();
      $count    = $connect->count();

      return response()->json(["result"=>$result, "count"=>$count]);
    }

  public static function filter($input) {
      $data      = $input['parameter']["data"];
      $value     = $input['parameter']["value"];
      $operator  = $input['parameter']["operator"];
      $type      = $input['parameter']["type"];
      $connect   = \DB::connection($input["db"])->table($input["table"]);

      for ($i=0; $i < count($data); $i++) {
      $result[] = array($data[$i],$operator[$i],$value[$i]);

      if ($type == "or") {
        if ($operator[$i] != "like") $connect->orWhere($result);
        else $connect->orwhere($data[$i], 'like', '%'.$value[$i].'%');
      }

      else if($type == "and") $connect->Where($result);
      else $connect->orwhere($data[$i], 'like', '%'.$value[$i].'%');
      }

      $result  = $connect->get();
      $count   = $connect->count();
      return response()->json(["result"=>$result, "count"=>$count]);
    }

  public static function filterByGrid($input) {
    $connect  = \DB::connection($input["db"])->table($input["table"]);
    $search   = $input["filter"];

    foreach ($search as $value) {
      if ($value["operator"] == "like")
        $connect->Where($value["property"],$value["operator"],$value["value"]."%");
      else if($value["operator"] == "eq")
        $connect->whereDate($value["property"],'=',$value["value"]);
      else if($value["operator"] == "gt")
        $connect->whereDate($value["property"],'>=',$value["value"]);
      else if($value["operator"] == "lt")
        $connect->whereDate($value["property"],'<=',$value["value"]);
    }

    if ($input['start'] != '' && $input['limit'] != '')
      $connect->skip($input['start'])->take($input['limit']);

    $result   = $connect->get();
    $count    = $connect->count();

    return response()->json(["result"=>$result, "count"=>$count]);
  }

  public static function autoComplete($input) {
    $connect  = \DB::connection($input["db"])->table($input["table"]);

    if ($input['field'] != '' && $input['query'] != '') {
      $connect->Where($input["field"],'like',$input["query"]."%");
    }

    if ($input['start'] != '' && $input['limit'] != '') {
      $connect->skip($input['start'])->take($input['limit']);
    }

    if(!empty($input["groupby"])) {
      $connect->groupBy($input["groupby"]);
    }

    $result   = $connect->get();
    $count    = $connect->count();

    return response()->json(["result"=>$result, "count"=>$count]);
  }

  public static function join($input) {
    $connection = DB::connection($input["db"])->table($input["table"]);
    foreach ($input["join"] as $list) {
      $connection->join($list["table"], $list["field1"], '=', $list["field2"]);
    }

    if(!empty($input["where"][0])) {
      $connection->where($input["where"]);
    }
    if(!empty($input["select"][0])) {
      $connection->select($input["select"]);
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connection->whereIn($in[0], $in[1]);
    }

    $data   = $connection->get();
    // $decode = json_decode(json_encode($data), TRUE);
    // $conCross = DB::connection($input["crossJoin"]["db"])->table($input["crossJoin"]["table"]);
    // foreach ($decode as $value) {
    //     $cek[] = [$input["crossJoin"]["field2"],"=", $value[$input["crossJoin"]["field1"]]];
    // }
    // $conCross->where(["CUSTOMER_ID", "=", "12402110"]);
    // $data = $conCross->get();
    return response()->json($data);

  }

  public static function whereQuery($input) {
    $connect   = \DB::connection($input["db"])->table($input["table"]);
    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    if (!empty($input["query"]) && !empty($input["field"])) {
      $connect->where($input["field"],"like", "%".$input["query"]."%");
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn($in[0], $in[1]);
    }

    $data      = $connect->get();
    $count     = $connect->count();
    return response()->json(["result"=>$data, "count"=>$count]);
  }

  public static function whereIn($input) {
    $connect   = \DB::connection($input["db"])->table($input["table"]);

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn($in[0], $in[1]);
    }

    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    $data      = $connect->get();
    return response()->json($data);
  }
}
