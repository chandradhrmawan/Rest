<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use App\Helper\ConnectedTOS;
use App\Helper\GlobalHelper;

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

    function getRealisasionTOS($input, $request){
      return ConnectedTOS::realTos($input);
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
}
