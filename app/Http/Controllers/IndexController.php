<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;

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
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input, $request);
    }

    function join_filter($action, $request) {
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
        $relasib = $action['relation'][1];
        if (count($action["relation"]) > 1) {
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

    function filter($input, $request) {
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

    function filterByGrid($input, $request) {
      $this->validasi($input["action"], $request);
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

    function autoComplete($input, $request) {
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if ($input['field'] != '' && $input['query'] != '') {
        $connect->Where($input["field"],'like',$input["query"]."%");
      }

      if ($input['start'] != '' && $input['limit'] != '') {
        $connect->skip($input['start'])->take($input['limit']);
      }

      $result   = $connect->get();
      $count    = $connect->count();

      return response()->json(["result"=>$result, "count"=>$count]);
    }

    function vessel_index($input, $request) {
      $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/trackingVessel";
      $string_json = '{
        "trackingVesselRequest": {
          "esbHeader": {
            "externalId": "5275682735",
            "timestamp": "YYYYMMDD HH:Mi:SS"
            },
            "esbBody": {
              "vesselName": "'.$input['query'].'",
              "ibisTerminalCode": "'.$input['ibis_terminal_code'].'"
            }
          }
        }';

        $username="npk_billing";
        $password ="npk_billing";
        $client = new Client();
        $options= array(
          'auth' => [
            $username,
            $password
          ],
          'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json'],
          'body' => $string_json,
          "debug" => false
        );
        try {
          $res = $client->post($endpoint_url, $options);
        } catch (ClientException $e) {
          echo $e->getRequest() . "\n";
          if ($e->hasResponse()) {
            echo $e->getResponse() . "\n";
          }
        }

        $results = json_decode($res->getBody()->getContents());
        $data = $results->esbBody->results;

        $array_map = array_map(function($query) {
          return [
            'vessel' => $query->vessel,
            'voyageIn' => $query->voyageIn,
            'voyageOut' => $query->voyageOut,
            'ata' => ($query->ata == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->ata)->format('Y-m-d H:i'),
            'atd' => ($query->atd == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->atd)->format('Y-m-d H:i'),
            'atb' => ($query->atb == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->atb)->format('Y-m-d H:i'),
            'eta' => \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->eta)->format('Y-m-d H:i'),
            'etd' => \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->etd)->format('Y-m-d H:i'),
            'etb' => ($query->etb == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->etb)->format('Y-m-d H:i'),
            'openStack' => ($query->openStack == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->openStack)->format('Y-m-d H:i'),
            'closingTime' => ($query->closingTime == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->closingTime)->format('Y-m-d H:i'),
            'closingTimeDoc' => ($query->closingTimeDoc == null) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->closingTimeDoc)->format('Y-m-d H:i'),
            'voyage' => $query->voyage,
            'idKade' => $query->idKade,
            'terminalCode' => $query->terminalCode,
            'ibisTerminalCode' => $query->ibisTerminalCode,
            'active' => $query->active,
            'idVsbVoyage' => $query->idVsbVoyage,
            'vesselCode'=> $query->vesselCode
          ];
        }, (array) $data);

        return response()->json(["result"=>$array_map, "count"=>count($array_map)]);
    }

    function peb_index($input, $request) {
      $date = \Carbon\Carbon::createFromFormat("Ymd", str_replace('-','',$input['date_peb']))->format('dmY');
      $endpoint_url="http://10.88.48.57:5555/restv2/tpsOnline/searchPEB";
      $string_json = '{
        "searchPEBRequest": {
          "esbHeader": {
            "externalId": "5275682735",
            "timestamp": "YYYYMMDD HH:Mi:SS"
            },
            "esbBody": {
              "username": "PLDB",
              "password": "PLDB12345",
              "noPEB": "'.$input['no_peb'].'",
              "tglPEB": "'.$date.'",
              "npwp": "'.$input['npwp'].'"
            }
          }
        }';

        $username="npk_billing";
        $password ="npk_billing";
        $client = new Client();
        $options= array(
          'auth' => [
            $username,
            $password
          ],
          'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json'],
          'body' => $string_json,
          "debug" => false
        );
        try {
          $res = $client->post($endpoint_url, $options);
        } catch (ClientException $e) {
          echo $e->getRequest() . "\n";
          if ($e->hasResponse()) {
            echo $e->getResponse() . "\n";
          }
        }

        $body = json_decode($res->getBody()->getContents());

        return response()->json(['pebListResponse' => $body->searchPEBInterfaceResponse]);
    }

    function viewHeaderDetail($input) {
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

    function join($input) {
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

      $data = $connection->get();
      return response()->json($data);
    }
}
