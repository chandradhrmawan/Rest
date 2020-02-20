<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class GlobalHelper {

  public static function viewHeaderDetail($input) {
      $data    = $input["data"];
      $count   = count($input["data"]);
      $pk      = $input["HEADER"]["PK"][0];
      $pkVal   = $input["HEADER"]["PK"][1];
      foreach ($data as $data) {
        $val     = $input[$data];
        $connect  = DB::connection($val["DB"])->table($val["TABLE"]);
          if ($data == "HEADER") {
             $header   = $connect->where(strtoupper($pk), "like", strtoupper($pkVal))->get();
             $header   = json_decode(json_encode($header), TRUE);
             $vwdata = ["HEADER" => $header];
          }

          else if($data == "FILE") {
            if (isset($input[$data]["BASE64"])) {
              if ($input[$data]["BASE64"] == "N" || $input[$data]["BASE64"] == "n" ) {
                $fil     = [];
                $fk      = $val["FK"][0];
                $fkhdr   = $header[0][$val["FK"][1]];
                $detail  = json_decode(json_encode($connect->where(strtoupper($fk), "like", strtoupper($fkhdr))->get()), TRUE);
                foreach ($detail as $list) {
                  $newDt = [];
                  foreach ($list as $key => $value) {
                    $newDt[$key] = $value;
                  }
                  $fil[] = $newDt;
                  $vwdata[$data] = $fil;
                  }
                  if (empty($detail)) {
                    $vwdata[$data] = [];
                    }
              }
            } else {
            $fil     = [];
            $fk      = $val["FK"][0];
            $fkhdr   = $header[0][$val["FK"][1]];
            $detail  = json_decode(json_encode($connect->where(strtoupper($fk), "like", strtoupper($fkhdr))->get()), TRUE);
            foreach ($detail as $list) {
              $newDt = [];
              foreach ($list as $key => $value) {
                $newDt[$key] = $value;
              }
              $dataUrl = "http://10.88.48.33/api/public/".$detail[0]["doc_path"];
              $url     = str_replace(" ", "%20", $dataUrl);
              $file = file_get_contents($url);
              $newDt["base64"]  =  base64_encode($file);
              $fil[] = $newDt;
              $vwdata[$data] = $fil;
              }
              if (empty($detail)) {
                $vwdata[$data] = [];
                }
              }
            }

          else {
            $fk      = $val["FK"][0];
            $fkhdr   = $header[0][$val["FK"][1]];
            if(!empty($val["WHERE"][0])) {
              $detail  = $connect->where(strtoupper($fk), "like", strtoupper($fkhdr))->where($val["WHERE"])->get();
            } else {
              if (isset($val["JOIN"])) {
                foreach ($val["JOIN"] as $list) {
                  $connect->join(strtoupper($list["table"]), strtoupper($list["field1"]), '=', strtoupper($list["field2"]));
                }
              }
              if (isset($val["JOINRAW"])) {
                foreach ($val["JOINRAW"] as $list) {
                  $connect->join(strtoupper($list["table"]), DB::raw($list['field']));
                }
              }
              if (isset($val["LEFTJOIN"])) {
                foreach ($val["LEFTJOIN"] as $list) {
                  $connect->leftJoin(strtoupper($list["table"]), strtoupper($list["field1"]), '=', strtoupper($list["field2"]));
                }
              }
              $detail  = $connect->where(strtoupper($fk), "like", strtoupper($fkhdr))->get();
            }
            if (empty($detail)) {
              $field      = DB::connection($val["DB"])->table('USER_TAB_COLUMNS')->select('column_name')->where('table_name', $val["TABLE"])->get();
              $empty      = [];
              foreach ($field as $value) {
                $empty[$value->column_name] = "";
              }
              $vwdata[$data] = $empty;
            } else {
              $vwdata[$data] = $detail;
            }
          }
      }

      if (!empty($input["changeKey"])) {
        $result  = $vwdata;
        $data    = json_encode($result);
        $change  = str_replace($input["changeKey"][0], $input["changeKey"][1], $data);
        $vwdata  = json_decode($change);
      }

      if (isset($input["spesial"])) {
        if ($input["spesial"] == "TM_LUMPSUM") {
          $id       = $input["HEADER"]["PK"][1];
          $detail   = [];
          $cust     = [];
          $fil      = [];
          $data_a   = DB::connection("omcargo")->table('TS_LUMPSUM_AREA')->where("LUMPSUM_ID", "=", $id)->get();
          foreach ($data_a as $list) {
            $newDt = [];
            foreach ($list as $key => $value) {
              $newDt[$key] = $value;
            }

            $data_c = DB::connection("omcargo")->table('TM_REFF')->where([["REFF_TR_ID", "=", "11"],["REFF_ID", "=", $list->lumpsum_stacking_type]])->get();
            foreach ($data_c as $listc) {
              $newDt = [];
              foreach ($listc as $key => $value) {
                $newDt[$key] = $value;
              }
            }

          if ($list->lumpsum_stacking_type == "2") {
            $data_b = DB::connection("mdm")->table('TM_STORAGE')->where("storage_code", $list->lumpsum_area_code)->get();
          } else {
            $data_b = DB::connection("mdm")->table('TM_YARD')->where("yard_code", $list->lumpsum_area_code)->get();
          }
          foreach ($data_b as $listS) {
            foreach ($listS as $key => $value) {
              $newDt[$key] = $value;
            }
          }
          $detail[] = $newDt;
         }

         $data_d   = DB::connection("omcargo")->table('TS_LUMPSUM_CUST')->where("LUMPSUM_ID", "=", $id)->get();
         foreach ($data_d as $listD) {
           $custo = [];
           foreach ($listD as $key => $value) {
             $custo[$key] = $value;
           }
             $cust[] = $custo;
         }

         $vwdata["DETAIL"] = $detail;
         $vwdata["CUSTOMER"] = $cust;
         $no       = $vwdata["HEADER"][0]["lumpsum_no"];
         $data_e   = DB::connection("omcargo")->table('TX_DOCUMENT')->where("REQ_NO", "=", $id)->get();
         foreach ($data_e as $list) {
           $newDt = [];
           foreach ($list as $key => $value) {
             $newDt[$key] = $value;
           }
           $dataUrl = "http://10.88.48.33/api/public/".$list->doc_path;
           $url     = str_replace(" ", "%20", $dataUrl);
           $file = file_get_contents($url);
           $newDt["base64"]  =  base64_encode($file);
           $fil[] = $newDt;
           $vwdata["FILE"] = $fil;
           }
           if (empty($data_e)) {
             $vwdata["FILE"] = [];
           }
        }
      }
      return $vwdata;
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

      $count  = count($result);
      return ["count"=>$count,"result"=>$result];
    }

  public static function index($input) {
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if (!empty($input["filter"])) {
      $search   = $input["filter"];
      foreach ($search as $value) {
        if ($value["operator"] == "like")
          $connect->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
        else if($value["operator"] == "eq")
          $connect->whereDate($value["property"],'=',$value["value"]);
        else if($value["operator"] == "gt")
          $connect->whereDate($value["property"],'>=',$value["value"]);
        else if($value["operator"] == "lt")
          $connect->whereDate($value["property"],'<=',$value["value"]);
        }
      }

      if (!empty($input["selected"])) {
        $result  = $connect->select($input["selected"]);
      }

      if (!empty($input["selectraw"])) {
        $result = $connect->select(DB::raw($input["selectraw"]));
      }

      if(!empty($input["orderby"][0])) {
      $in        = $input["orderby"];
      $connect->orderby($in[0], $in[1]);
      }

      if (isset($input["whereraw"])) {
          $connect->whereRaw($input["whereraw"]);
      }

      if(!empty($input["where"][0])) {
        $connect->where($input["where"]);
      }

      if(!empty($input["whereIn"][0])) {
      $in        = $input["whereIn"];
      $connect->whereIn(strtoupper($in[0]), $in[1]);
      }

      $count    = $connect->count();
      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $connect->skip($input['start'])->take($input['limit']);
        }
      }
      $result   = $connect->get();

      return ["result"=>$result, "count"=>$count];
    }

  public static function filter($input) {
      $data      = $input['parameter']["data"];
      $value     = $input['parameter']["value"];
      $operator  = $input['parameter']["operator"];
      $type      = $input['parameter']["type"];
      $connect   = \DB::connection($input["db"])->table($input["table"]);

      if (!empty($input["filter"])) {
      $search   = $input["filter"];
      foreach ($search as $value) {
        if ($value["operator"] == "like")
          $connect->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
        else if($value["operator"] == "eq")
          $connect->whereDate($value["property"],'=',$value["value"]);
        else if($value["operator"] == "gt")
          $connect->whereDate($value["property"],'>=',$value["value"]);
        else if($value["operator"] == "lt")
          $connect->whereDate($value["property"],'<=',$value["value"]);
        }
      }

      for ($i=0; $i < count($data); $i++) {
      $result[] = array(strtoupper($data[$i]),$operator[$i],strtoupper($value[$i]));

      if ($type == "or") {
        if ($operator[$i] != "like") $connect->orWhere($result);
        else $connect->orwhere(strtoupper($data[$i]), 'like', '%'.strtoupper($value[$i]).'%');
      }

      else if($type == "and") $connect->Where($result);
      else $connect->orwhere(strtoupper($data[$i]), 'like', '%'.strtoupper($value[$i]).'%');
      }

      if(!empty($input["orderby"][0])) {
      $in        = $input["orderby"];
      $connect->orderby($in[0], $in[1]);
      }

      if (!empty($input["range"])) {
        $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
      }

      if (!empty($input["selected"])) {
        $result  = $connect->select($input["selected"]);
      }

      if (!empty($input["range"])) {
        $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
      }

      $count   = $connect->count();
      if (!empty($input['start']) || $input["start"] == '0') {
        if (!empty($input['limit'])) {
          $connect->skip($input['start'])->take($input['limit']);
        }
      }

      if (!empty($input["changeKey"])) {
        $result  = $connect->get();
        $data    = json_encode($result);
        $change  = str_replace($input["changeKey"][0], $input["changeKey"][1], $data);
        $result  = json_decode($change);
      } else {
        $result  = $connect->get();
      }
      return ["result"=>$result, "count"=>$count];
    }

  public static function filterByGrid($input) {
    $connect  = \DB::connection($input["db"])->table($input["table"]);
    if (!empty($input["filter"])) {
    $search   = $input["filter"];
    foreach ($search as $value) {
      if ($value["operator"] == "like")
        $connect->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
      else if($value["operator"] == "eq")
        $connect->whereDate($value["property"],'=',$value["value"]);
      else if($value["operator"] == "gt")
        $connect->whereDate($value["property"],'>=',$value["value"]);
      else if($value["operator"] == "lt")
        $connect->whereDate($value["property"],'<=',$value["value"]);
      }
    }

    if (!empty($input["range"])) {
      $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
    }


    if (!empty($input["selected"])) {
      $result  = $connect->select($input["selected"]);
    }

    if(!empty($input["orderby"][0])) {
    $in        = $input["orderby"];
    $connect->orderby(strtoupper($in[0]), $in[1]);
    }

    $count    = $connect->count();
    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $connect->skip($input['start'])->take($input['limit']);
      }
    }
    $result   = $connect->get();

    return ["result"=>$result, "count"=>$count];
  }

  public static function autoComplete($input) {
    $connect  = DB::connection($input["db"])->table($input["table"]);

    if(!empty($input["groupby"])) {
      $connect->groupBy(strtoupper($input["groupby"]));
    }

    if (!empty($input["range"])) {
      $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
    }

    if (!empty($input["selected"])) {
      $result  = $connect->select($input["selected"]);
    }

    if(!empty($input["orderby"][0])) {
    $in        = $input["orderby"];
    $connect->orderby(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["whereNotIn"][0])) {
    $in        = $input["whereNotIn"];
    $connect->whereNotIn(strtoupper($in[0]), $in[1]);
    }

    if (!empty($input["query"]) && !empty($input["field"])) {
      if (is_array($input["field"])) {
        foreach ($input["field"] as $field) {
          $upper = DB::connection($input["db"])->table($input["table"])->where(strtoupper($field),"like", "%".strtoupper($input["query"])."%")->get();
          $capit = DB::connection($input["db"])->table($input["table"])->where(strtoupper($field),"like", "%".ucwords(strtolower($input["query"]))."%")->get();
          $lower = DB::connection($input["db"])->table($input["table"])->where(strtoupper($field),"like", "%".strtolower($input["query"])."%")->get();

          if (!empty($upper)) {
            $connect->where(strtoupper($field),"like", "%".strtoupper($input["query"])."%");
          } else if(!empty($capit)) {
            $connect->where(strtoupper($field),"like", "%".ucwords(strtolower($input["query"]))."%");
          } else if(!empty($lower)) {
            $connect->where(strtoupper($field),"like", "%".strtolower($input["query"])."%");
          }
        }
      } else {
        $upper = DB::connection($input["db"])->table($input["table"])->where(strtoupper($input["field"]),"like", "%".strtoupper($input["query"])."%")->get();
        $capit = DB::connection($input["db"])->table($input["table"])->where(strtoupper($input["field"]),"like", "%".ucwords(strtolower($input["query"]))."%")->get();
        $lower = DB::connection($input["db"])->table($input["table"])->where(strtoupper($input["field"]),"like", "%".strtolower($input["query"])."%")->get();

        if (!empty($upper)) {
          $connect->where(strtoupper($input["field"]),"like", "%".strtoupper($input["query"])."%");
        } else if(!empty($capit)) {
          $connect->where(strtoupper($input["field"]),"like", "%".ucwords(strtolower($input["query"]))."%");
        } else if(!empty($lower)) {
          $connect->where(strtoupper($input["field"]),"like", "%".strtolower($input["query"])."%");
        }
      }
    }

    $count    = $connect->count();
    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $connect->skip($input['start'])->take($input['limit']);
      }
    }
    $result   = $connect->get();

    return ["result"=>$result, "count"=>$count];
  }

  public static function join($input) {
    $connect = DB::connection($input["db"])->table($input["table"]);
    if (isset($input["type"])) {
      if ($input["type"] == "left") {
        foreach ($input["join"] as $list) {
          $connect->leftJoin(strtoupper($list["table"]), strtoupper($list["field1"]), '=', strtoupper($list["field2"]));
        }
      }
    } else {
      foreach ($input["join"] as $list) {
        $connect->join(strtoupper($list["table"]), strtoupper($list["field1"]), '=', strtoupper($list["field2"]));
      }
    }

    if (isset($input["leftJoin"])) {
      foreach ($input["leftJoin"] as $list) {
        if (isset($list["fieldRaw"])) {
          $connect->leftJoin(strtoupper($list["table"]), DB::raw($list["fieldRaw"]));
        } else {
          $connect->leftJoin(strtoupper($list["table"]), strtoupper($list["field1"]), '=', strtoupper($list["field2"]));
        }
      }
    }

    if (!empty($input["selected"])) {
      $result  = $connect->select($input["selected"]);
    }

    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["whereIn2"][0])) {
    $in        = $input["whereIn2"];
    $connect->whereIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["whereNotIn"][0])) {
    $in        = $input["whereNotIn"];
    $connect->whereNotIn(strtoupper($in[0]), $in[1]);
    }

    if (!empty($input["range"])) {
      $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
    }

    if (!empty($input["filter"])) {
    $search   = json_decode($input["filter"], TRUE);
    foreach ($search as $value) {
      if ($value["operator"] == "like")
        $connect->Where(strtoupper($value["property"]),$value["operator"],"%".$value["value"]."%");
      else if($value["operator"] == "eq")
        $connect->whereDate($value["property"],'=',$value["value"]);
      else if($value["operator"] == "gt")
        $connect->whereDate($value["property"],'>=',$value["value"]);
      else if($value["operator"] == "lt")
        $connect->whereDate($value["property"],'<=',$value["value"]);
      }
    }

    if (!empty($input["query"]) && !empty($input["field"])) {
      if (is_array($input["field"])) {
        foreach ($input["field"] as $field) {
          $connect->orwhere(strtoupper($field),"like", "%".strtoupper($input["query"])."%");
          $connect->orwhere(strtoupper($field),"like", "%".ucwords(strtolower($input["query"]))."%");
          $connect->orwhere(strtoupper($field),"like", "%".strtolower($input["query"])."%");
        }
      } else {
        $connect->orwhere(strtoupper($input["field"]),"like", "%".strtoupper($input["query"])."%");
        $connect->orwhere(strtoupper($input["field"]),"like", "%".ucwords($input["query"])."%");
        $connect->orwhere(strtoupper($input["field"]),"like", "%".strtolower($input["query"])."%");
      }
    }

    if(!empty($input["groupby"])) {
      $connect->groupBy($input["groupby"]);
    }

    if (isset($input["sort"])) {
      $sort = json_decode($input["sort"]);
      foreach ($sort as $sort) {
      $property = $sort->property;
      $direction = $sort->direction;
      $connect->orderby(strtoupper($property), $direction);
      }
    } else {
      if(!empty($input["orderby"][0])) {
        $in        = $input["orderby"];
        $connect->orderby(strtoupper($in[0]), $in[1]);
      }
    }

    $addSlashes = str_replace('?', "'?'", $connect->toSql());
    $count      = vsprintf(str_replace('?', '%s', $addSlashes), $connect->getBindings());

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $connect->skip($input['start'])->take($input['limit']);
      }
    }

    if (!empty($input["changeKey"])) {
      $result  = $connect->get();
      $data    = json_encode($result);
      $change  = str_replace($input["changeKey"][0], $input["changeKey"][1], $data);
      $data    = json_decode($change);
    } else {
      $data   = $connect->get();
      }

      // query builder
      $addSlashes = str_replace('?', "'?'", $connect->toSql());
      $count      = vsprintf(str_replace('?', '%s', $addSlashes), $connect->getBindings());

      return ["result"=>$data, "count"=>count($data)];
  }

  public static function whereQuery($input) {
    $connect   = \DB::connection($input["db"])->table($input["table"]);
    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    if (!empty($input["query"]) && !empty($input["field"])) {
      $connect->where(strtoupper($input["field"]),"like", "%".strtoupper($input["query"])."%");
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["orderby"][0])) {
    $in        = $input["orderby"];
    $connect->orderby(strtoupper($in[0]), $in[1]);
    }

    if (!empty($input["range"])) {
      $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
    }

    if (!empty($input["filter"])) {
    $search   = $input["filter"];
    foreach ($search as $value) {
      if ($value["operator"] == "like")
        $connect->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
      else if($value["operator"] == "eq")
        $connect->whereDate($value["property"],'=',$value["value"]);
      else if($value["operator"] == "gt")
        $connect->whereDate($value["property"],'>=',$value["value"]);
      else if($value["operator"] == "lt")
        $connect->whereDate($value["property"],'<=',$value["value"]);
      }
    }


    if (!empty($input["selected"])) {
      $result  = $connect->select($input["selected"]);
    }

    $count     = $connect->count();
    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $connect->skip($input['start'])->take($input['limit']);
      }
    }

    if (!empty($input["changeKey"])) {
      $result  = $connect->get();
      $data    = json_encode($result);
      $change  = str_replace($input["changeKey"][0], $input["changeKey"][1], $data);
      $data  = json_decode($change);
    } else {
      $data  = $connect->get();
    }
    return ["result"=>$data, "count"=>$count];
  }

  public static function whereIn($input) {
    $connect   = \DB::connection($input["db"])->table($input["table"]);

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connect->whereIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["where"][0])) {
      $connect->where($input["where"]);
    }

    if(!empty($input["whereNotIn"][0])) {
    $in        = $input["whereNotIn"];
    $connect->whereNotIn(strtoupper($in[0]), $in[1]);
    }

    if (!empty($input["range"])) {
      $result  = $connect->whereBetween($input["range"][0],[$input["range"][1],$input["range"][2]]);
    }

    if (!empty($input["filter"])) {
    $search   = $input["filter"];
    foreach ($search as $value) {
      if ($value["operator"] == "like")
        $connect->Where(strtoupper($value["property"]),$value["operator"],"%".strtoupper($value["value"])."%");
      else if($value["operator"] == "eq")
        $connect->whereDate($value["property"],'=',$value["value"]);
      else if($value["operator"] == "gt")
        $connect->whereDate($value["property"],'>=',$value["value"]);
      else if($value["operator"] == "lt")
        $connect->whereDate($value["property"],'<=',$value["value"]);
      }
    }

    if (!empty($input["selected"])) {
      $result  = $connect->select(strtoupper($input["selected"]));
    }

    if(!empty($input["orderby"][0])) {
    $in        = $input["orderby"];
    $connect->orderby(strtoupper($in[0]), $in[1]);
    }

    $count    = $connect->count();
    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $connect->skip($input['start'])->take($input['limit']);
      }
    }
    $data      = $connect->get();
    return ["result"=>$data, "count"=>$count];
  }

  public static function saveheaderdetail($input) {
    $data    = $input["data"];
    $count   = count($input["data"]);
    $cek     = $input["HEADER"]["PK"];
    $dbhdr   = $input["HEADER"]["DB"];
    $tblhdr  = $input["HEADER"]["TABLE"];

    if(!empty($input["HEADER"]["SEQUENCE"])) {
    $sq      = $input["HEADER"]["SEQUENCE"];
    } else {
      $sq      = "Y";
    }

    foreach ($data as $data) {
      $val     = $input[$data];
      $connect  = DB::connection($val["DB"])->table($val["TABLE"]);
      if ($data == "HEADER") {
        $hdr   = json_decode(json_encode($val["VALUE"]), TRUE);
        if ($hdr[0][$cek] == '' || $sq == "N") {
          if ($dbhdr == "omuster") {
            $sequence = DB::connection($dbhdr)->table("DUAL")->select($tblhdr."_SEQ.NEXTVAL")->get();
            $seq      = ($sequence[0]->nextval);
          } else {
            $sequence = DB::connection($dbhdr)->table("DUAL")->select("SEQ_".$tblhdr.".NEXTVAL")->get();
            $seq      = ($sequence[0]->nextval);
          }

          if (isset($input["cekdb"])) {
            $field = $input["cekdb"];
            $value = $input["HEADER"]["VALUE"][0][$field];
            $item  = DB::connection($input["HEADER"]["DB"])->table($input["HEADER"]["TABLE"])->where($field, $value)->get();
            if (!empty($item)) {
              return ["Success"=>"F", "Error"=>"Data With $field = $value Already Exist"];
            }
          }

          foreach ($val["VALUE"] as $list) {
            $newDt = [];
            foreach ($list as $key => $value) {
              if ($key == $cek) {
                $newDt[$key] = $seq;
              } else {
                $newDt[$key] = $value;
              }
            }
          }

          $datahdr[] = $newDt;
          foreach ($datahdr as $value) {
            $insert       = $connect->insert([$value]);
          }

        $header   = DB::connection($dbhdr)->table($tblhdr)->where($cek, $seq)->first();
        $header   = json_decode(json_encode($header), TRUE);

        } else {
          foreach ($val["VALUE"] as $value) {
            $insert       = $connect->where($cek,$hdr[0][$cek])->update($value);
            $header   = DB::connection($dbhdr)->table($tblhdr)->where($cek, $input["HEADER"]["VALUE"][0][$cek])->first();
            $header   = json_decode(json_encode($header), TRUE);
          }
        }

      }
      else if($data == "FILE") {
        if ($hdr[0][$cek] != '') {
          $connect->where($val["FK"][0], $header[$val["FK"][1]]);
          $connect->delete();
        }
        foreach ($val["VALUE"] as $list) {
          if (isset($list["id"])) {
            unset($list["id"]);
          }

          $directory  = $val["DB"].'/'.strtoupper($val["TABLE"]).'/'.date('d-m-Y').'/';
          $response   = FileUpload::upload_file($list, $directory,$input["HEADER"]["TABLE"], $header[strtolower($input["HEADER"]["PK"])]);
          if (!empty($list["DOC_TYPE"])) {
            if (!empty($list["DOC_NAME"])) {
              $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_type'=>$list["DOC_TYPE"],'doc_name'=>$list["DOC_NAME"],'doc_path'=>$response['link']];
            } else {
              $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_type'=>$list["DOC_TYPE"],'doc_name'=>$list["PATH"],'doc_path'=>$response['link']];
            }
          } else {
            if (!empty($list["DOC_NAME"])) {
              $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_name'=>$list["DOC_NAME"],'doc_path'=>$response['link']];
            } else {
              $addVal     = [$val["FK"][0]=>$header[$val["FK"][1]]]+['doc_no'=>$list["DOC_NO"],'doc_name'=>$list["PATH"],'doc_path'=>$response['link']];
            }
          }
          if ($response['response'] == true) {
              $connect->insert([$addVal]);
            }
          }
      } else {
        if ($hdr[0][$cek] != '') {
          $connect->where($val["FK"][0], $header[$val["FK"][1]]);
          $connect->delete();
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
                $connect->insert([$addVal]);
              }
          }
        }
      }
    return ["result"=>"Save or Update Success", "header"=>$header];
  }

  public static function update($input){
    $connection = DB::connection($input["db"])->table($input["table"]);
    if (isset($input["where"])) {
      $connection->where($input["where"]);
    }
    if(isset($input["whereNotIn"][0])) {
    $in        = $input["whereNotIn"];
    $connection->whereNotIn(strtoupper($in[0]), $in[1]);
    }

    if(!empty($input["whereIn"][0])) {
    $in        = $input["whereIn"];
    $connection->whereIn(strtoupper($in[0]), $in[1]);
    }

    $connection->update($input["update"]);
    $data = $connection->get();
    return ["msg"=>"Success", "result"=>$data];
  }

  public static function save($input) {
    $parameter   = $input['parameter'];
    if (!empty($input["PK"])) {
      $cek         = DB::connection($input["db"])->table($input["table"])->where($input["PK"][0], $input["PK"][1])->get();
      if (!empty($cek)) {
        $cek       = DB::connection($input["db"])->table($input["table"])->where($input["PK"][0], $input["PK"][1])->delete();
        $connect   = \DB::connection($input["db"])->table($input["table"]);
        foreach ($parameter as $value) $connect->insert($parameter);
        return ["result"=>$parameter, "count"=>count($parameter)];
      } else {
        $connect   = \DB::connection($input["db"])->table($input["table"]);
        foreach ($parameter as $value) $connect->insert($parameter);
        return ["result"=>$parameter, "count"=>count($parameter)];
      }
    } else {
      $connect   = \DB::connection($input["db"])->table($input["table"]);
      foreach ($parameter as $value) $connect->insert($parameter);
      return ["result"=>$parameter];
    }
  }

  public static function delHeaderDetail($input) {
    $data    = $input["data"];
    $count   = count($input["data"]);
    $pk      = $input["HEADER"]["PK"][0];
    $pkVal   = $input["HEADER"]["PK"][1];
    foreach ($data as $data) {
      $val     = $input[$data];
      $connect  = DB::connection($val["DB"])->table($val["TABLE"]);
        if ($data == "HEADER") {
           $header   = $connect->where(strtoupper($pk), "like", strtoupper($pkVal))->get();
           $header   = json_decode(json_encode($header), TRUE);
        }

        else if($data == "FILE") {
          $fil     = [];
          $fk      = $val["FK"][0];
          $fkhdr   = $header[0][$val["FK"][1]];
          $detail  = $connect->where(strtoupper($fk), "like", "%".strtoupper($fkhdr)."%")->first();
            if (file_exists($detail->doc_path)) {
              $file    = unlink($detail->doc_path);
              $result["file"] = "File delete success";
            } else {
              $result["file"] = "Error Delete File / File Not Found";
            }
          }

        else {
          $fk      = $val["FK"][0];
          $fkhdr   = $header[0][$val["FK"][1]];
          $detail  = $connect->where(strtoupper($fk), "like",  "%".strtoupper($fkhdr)."%")->delete();
        }
    }
    $result["header"] = $pk." = ".$header[0][strtolower($pk)]." Delete Success";
    $delHead = DB::connection($input["HEADER"]["DB"])->table($input["HEADER"]["TABLE"])->where(strtoupper($pk), "like", strtoupper($pkVal))->delete();
    return $result;
  }

  public static function tanggalMasukKeluar($service, $req_no, $no) {
    if ($service == "DEL") {
        $header = DB::connection('omcargo')->table('TX_HDR_'.$service)->where($service.'_NO', '=', $req_no)->get();
        $dtl    = DB::connection('omcargo')->table('TX_DTL_'.$service)->where('HDR_'.$service.'_ID', '=', $header[0]->del_id)->get();
        $date2  =date_create($dtl[$no]->dtl_out);
        $date1  =date_create($dtl[$no]->dtl_in);
        $count = date_diff($date1,$date2);
        // echo
        echo date("d-m-y", strtotime($dtl[$no]->dtl_in))."<br>".date("d-m-y", strtotime($dtl[$no]->dtl_out))."<br>".$count->format('%a Hari');
    }
    else if ($service == "REC") {
        $dtlIn  = DB::connection('omcargo')->table('TX_HDR_'.$service)->where($service.'_NO', '=', $req_no)->get();
        $dtlOut = DB::connection('omcargo')->table('TX_DTL_'.$service)->where('HDR_'.$service.'_ID', '=', $dtlIn[0]->rec_id)->get();
        $date1  =date_create($dtlOut[$no]->dtl_in);
        $date2  =date_create($dtlIn[0]->rec_etd);
        $count = date_diff($date1,$date2);
        echo date("d-m-y", strtotime($dtlOut[$no]->dtl_in))."<br>".date("d-m-y", strtotime($dtlIn[0]->rec_etd))."<br>".$count->format('%a Hari');
    } else if($service == "BPRP") {
      $header = DB::connection('omcargo')->table('TX_HDR_'.$service)->where($service.'_REQ_NO', '=', $req_no)->get();
      $dtl    = DB::connection('omcargo')->table('TX_DTL_'.$service)->where('HDR_'.$service.'_ID', '=', $header[0]->bprp_id)->get();
      $date2  = date_add(date_create($dtl[$no]->dtl_dateout), date_interval_create_from_date_string('1 days'));
      $date1  = date_create($dtl[$no]->dtl_datein);
      $count  = date_diff($date1,$date2);
      echo date("d-m-y", strtotime($dtl[$no]->dtl_datein))."<br>".date("d-m-y", strtotime($dtl[$no]->dtl_dateout))."<br>".$count->format('%a Hari');
    }
  }

  public static function getUper($req_no) {
    $data = DB::connection('omcargo')->table('TX_PAYMENT')->where('PAY_REQ_NO', $req_no)->get();
    if (!empty($data)) {
      return $data[0]->pay_amount;
    } else {
      return 0;
    }
  }

  public static function totalPenumpukan($req_no) {
    $data     = DB::connection('omcargo')->table("V_TX_DTL_NOTA")->where([['NOTA_HDR_ID','=', $req_no],["dtl_group_tariff_id","=","10"]])->get();
    if (!empty($data)) {
      echo number_format($data[0]->dtl_dpp);
    } else {
      return 0;
    }
  }
}
