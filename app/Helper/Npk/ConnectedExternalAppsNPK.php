<?php

namespace App\Helper\Npk;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use GuzzleHttp\Client;

use App\Helper\Npk\RequestBookingNPK;
use App\Helper\Npk\UperRequest;

use App\Models\OmCargo\TxHdrUper;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrDel;
use App\Models\OmCargo\TxHdrRec;
use App\Models\OmCargo\TxHdrNota;

class ConnectedExternalAppsNPK{

  // BTN
    public static function getListTCA($input){
      if (empty($input['idCustomer'])) {
        return ['Success' => false, 'msg' => 'idCustomer is required!'];
      }
      $endpoint_url=config('endpoint.getListTCA');
      $string_json = '{
           "getTCAHeaderInterfaceRequest": {
            "esbHeader": {
              "internalId": "",
              "externalId": "",
              "timestamp": "",
              "responseTimestamp": "",
              "responseCode": "",
              "responseMessage": ""
              },
              "esbBody": {
                 "idPort": "201",
                 "noBL": "'.$input['noBL'].'",
                 "vessel": "'.$input['vessel'].'",
                 "idCustomer": "'.$input['idCustomer'].'"
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

      $res = json_decode($res->getBody()->getContents(), true);
      $res = $res['esbBody']['results'];

      return ["result"=>$res, "count"=>count($res)];
    }

    public static function getViewDetilTCA($input){
      if (empty($input['noRequest'])) {
        return ['Success' => false, 'msg' => 'noRequest is required!'];
      }
      $endpoint_url=config('endpoint.getViewDetilTCA');
      $string_json = '{
          "getTCADetailInterfaceRequest": {
            "esbHeader": {
              "internalId": "",
              "externalId": "",
              "timestamp": "",
              "responseTimestamp": "",
              "responseCode": "",
              "responseMessage": ""
              },
              "esbBody": {
                  "noRequest": "'.$input['noRequest'].'"
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

      $res = json_decode($res->getBody()->getContents(), true);
      $res = $res['esbBody']['results'];

      return ["result"=>$res, "count"=>count($res)];
    }

    public static function vessel_index($input) {
      $endpoint_url=config('endpoint.vessel_index');
      $string_json = '{
        "trackingVesselRequest": {
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody": {
              "vesselName": "'.strtoupper($input['query']).'",
              "kadeName": "'.strtoupper($input['query']).'",
              "terminalCode": "'.$input['ibis_terminal_code'].'"
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
        // return $data = $results->esbBody->results;

        $array_map = array_map(function($query) {
          return [
            'vessel' => $query->vessel,
            'voyageIn' => $query->voyageIn,
            'voyageOut' => $query->voyageOut,
            'ata' => (empty($query->ata)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->ata)->format('Y-m-d H:i'),
            'atd' => (empty($query->atd)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->atd)->format('Y-m-d H:i'),
            'atb' => (empty($query->atb)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->atb)->format('Y-m-d H:i'),
            'eta' => (empty($query->eta)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->eta)->format('Y-m-d H:i'),
            'etd' => (empty($query->etd)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->etd)->format('Y-m-d H:i'),
            'etb' => (empty($query->etb)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->etb)->format('Y-m-d H:i'),
            'openStack' => (empty($query->openStack)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->openStack)->format('Y-m-d H:i'),
            'closingTime' => (empty($query->closingTime)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->closingTime)->format('Y-m-d H:i'),
            'closingTimeDoc' => (empty($query->closingTimeDoc)) ? null : \Carbon\Carbon::createFromFormat("d-m-Y H:i", $query->closingTimeDoc)->format('Y-m-d H:i'),
            'voyage' => $query->voyage,
            'idUkkSimop' => (empty($query->idUkkSimop)) ? null : $query->idUkkSimop,
            'idKade' => (empty($query->idKade)) ? null : $query->idKade,
            'kadeName' => (empty($query->kadeName)) ? null : $query->kadeName,
            'terminalCode' => (empty($query->terminalCode)) ? null : $query->idKade,
            'ibisTerminalCode' => (empty($query->ibisTerminalCode)) ? null : $query->idKade,
            'active' => (empty($query->active)) ? null : $query->idKade,
            'idVsbVoyage' => $query->idVsbVoyage,
            'vesselCode'=> $query->vesselCode
          ];
        }, (array) $data);

        return ["result"=>$array_map, "count"=>count($array_map)];
    }

    public static function peb_index($input) {
      // $date = \Carbon\Carbon::createFromFormat("Ymd", str_replace('-','',$input['date_peb']))->format('dmY');
      $date = date('dmY', strtotime($input['date_peb']));
      $endpoint_url=config('endpoint.peb_index');
      $string_json = '{
        "searchPEBRequest": {
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
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

      $body = json_decode($res->getBody()->getContents(), true);
      if (isset($body['searchPEBInterfaceResponse']['esbBody']['response'])) {
        if(strpos($body['searchPEBInterfaceResponse']['esbBody']['response'], 'Data tidak ditemukan')){
          return ['Success' => false, 'result' => 'Data tidak ditemukan'];
        }
      }
      return ['pebListResponse' => $body['searchPEBInterfaceResponse']];
    }

  	public static function realTos($input){
  		$count = DB::connection('omcargo')->table('TX_HDR_REALISASI')->where('REAL_REQ_NO', $input['req_no'])->count();
      $req = TxHdrBm::where('BM_NO', $input['req_no'])->first();
      if (empty($req)) {
        return ['result' => "Fail, request not found!", "Success" => false];
      }
      $condition = DB::connection('omcargo')->table('TS_SEND_TOS')->pluck('pkg_id');
      $ckp = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id', $req->bm_id)->whereIn('dtl_pkg_id', $condition)->get();

      if (count($ckp) > 0) {
        foreach ($ckp as $list) {
          static::realTosGet($req, $list);
        }
      }

      if (isset($input['reGet']) and $input['reGet'] == true) {
        $head = DB::connection('omcargo')->select(DB::raw("select * from TX_HDR_BM A left join TX_HDR_REALISASI B on B.REAL_REQ_NO = A.BM_NO where A.BM_ID = ".$req->bm_id));
        $head = $head[0];
        $query_detil = "
          select
            A.*, B.TOTAL_TON AS DTL_REAL_QTY_FROM_TOS, C.*
            from TX_DTL_BM A
            left join TX_REAL_TOS B on B.BL_NO = A.DTL_BM_BL
            left join TX_DTL_REALISASI C on C.DTL_BM_ID = A.DTL_BM_ID where A.HDR_BM_ID =".$req->bm_id;
        return [
          'req_header' => $head,
          'req_detil' => DB::connection('omcargo')->select(DB::raw($query_detil)),
          'req_eqpt' => DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $input['req_no'])->get(),
          'result' => "Success, get data real from tos!"
        ];
      }

      return [
        'req_header' => $req,
        'req_detil' => DB::connection('omcargo')->select(DB::raw("select * from TX_DTL_BM A left join TX_REAL_TOS B on B.BL_NO = A.DTL_BM_BL where A.HDR_BM_ID = ".$req->bm_id)),
        'req_eqpt' => DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $input['req_no'])->get(),
        'result' => "Success, get data real from tos!"
      ];
  	}

    private static function realTosGet($req, $list){
      DB::connection('omcargo')->table('TX_REAL_TOS')->where('idvsb', $req->bm_vvd_id)->where('bl_no', $list->dtl_bm_bl)->delete();
      $endpoint_url=config('endpoint.realTosGet');
      $string_json = '{
        "searchRealisasiRequest": {
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody": {
              "vvd": "'.$req->bm_vvd_id.'",
              "noblss": "'.$list->dtl_bm_bl.'"
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
        return $e->getResponse();
      }

      $response = json_decode(json_encode($res->getBody()->getContents()));
      $response = json_decode($response, true);
      $response = $response['esbBody']['results'][0];

      if (!empty($response['idVsbVoyage'])) {
        // return $newreal = $response;
        $newreal = $response;
        DB::connection('omcargo')->table('TX_REAL_TOS')->insert([
           'idvsb'=> $newreal['idVsbVoyage'],
           'bl_no'=> $newreal['blNumber'],
           // 'package'=> $newreal['packageName'], // gak tau knpa gak mau masuk
           'is_hz'=> $newreal['hz'],
           'is_disturb'=> $newreal['disturb'],
           'ei'=> $newreal['ei'],
           'tl'=> $newreal['tl'],
           'total_ton'=> $newreal['ttlTon'],
           'total_cubic'=> $newreal['ttlCubic'],
           'oi'=> $newreal['oi'],
           'rpact'=> $newreal['rpact'],
           'omcargoid'=> $newreal['omCargoid']
        ]);
      }
    }

    public static function sendRequestBooking($input){
      $req_type = substr($input['req_no'], 0,3);
      if($req_type == 'REC') {
        $header = TxHdrRec::where('rec_no',$input['req_no'])->first();
        $table = 'TX_HDR_REC';
        // if ($header->rec_extend_status == 'Y') {
        //   $req_type = 'EXT';
        // }
      }else if($req_type == 'DEL') {
        $header = TxHdrDel::where('del_no',$input['req_no'])->first();
        $table = 'TX_HDR_DEL';
        // if ($header->del_extend_status == 'Y') {
        //   $req_type = 'EXT';
        // }
      }else{
        $req_type = substr($input['req_no'], 0,2);
        if ($req_type == 'BM') {
          $header = TxHdrBm::where('bm_no',$input['req_no'])->first();
          $table = 'TX_HDR_BM';
          $req_type = 'BM';
        }
      }

      $config = RequestBookingNPK::config($table);
      if (!empty($header)) {
        $condition = DB::connection('omcargo')->table('TS_SEND_TOS')->pluck('pkg_id');
        $detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'],$header[$config['head_primery']])->whereIn('dtl_pkg_id', $condition)->get();
        return static::sendRequestBookingNewExcute($req_type, $input['paid_date'], $header, $detil, $config);
      }
    }

    private static function sendRequestBookingNewExcute($req_type, $paid_date, $head, $detil, $config){
      $endpoint_url=config('endpoint.sendRequestBookingNewExcute');
      $respn = [];
      $string_json_arr = [];
      $hitEsb = [];
      foreach ($detil as $list) {
        $listA = (array)$list;
        $hsl = [];

        $vparam = '';
        $hscode = '0000';
        if ($req_type == 'REC' or $req_type == 'BM') {
          $hscode = DB::connection('mdm')->table('TM_COMMODITY')->where('COMMODITY_ID', $list->dtl_cmdty_id)->first();
          $hscode = $hscode->hscode;
        }
        // first
          if ($req_type == 'BM' and $list->dtl_bm_type == 'Muat' and $listA[$config['head_tab_detil_tl']] == 'Y') {
            $vparam .= 'REC'; // IF_FLAG
          } else{
            $vparam .= $req_type; // IF_FLAG
          }
          $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // ID_CARGO
          $packageNameParent = DB::connection('mdm')->select(DB::raw('SELECT (CASE WHEN B.PACKAGE_NAME IS NULL THEN A.PACKAGE_NAME ELSE B.PACKAGE_NAME END) PACKAGE_NAME FROM TM_PACKAGE A LEFT JOIN TM_PACKAGE B ON B.PACKAGE_CODE = A.PACKAGE_PARENT_CODE WHERE A.PACKAGE_ID ='.$list->dtl_pkg_id));
          $packageNameParent = $packageNameParent[0];
          $packageNameParent = $packageNameParent->package_name;
          $vparam .= '^'.$packageNameParent; // PKG_NAME
          $paramTon = 0;
          $paramCub = 0;
          if ($list->dtl_unit_id == 1 or $list->dtl_unit_id == 2) {
            $paramTon = $list->dtl_qty;
          } else if ($list->dtl_unit_id == 3) {
            $paramCub = $list->dtl_qty;
          }
          $vparam .= '^'.$paramTon; // TON
          $vparam .= '^'.$paramCub; // CUBIC
          $vparam .= '^0'; // QTY
          $vparam .= '^'; // ID_INV
          $vparam .= '^'.$head[$config['head_no']]; // ID_REQ
          if ($req_type == 'BM') {
            $vparam .= '^'.date('Ymd', strtotime($head[$config['head_open_stack']])).'235959'; // STACKOUT_DATE
          }else{
            $vparam .= '^'.date('Ymd', strtotime($head[$config['head_closing_time']])).'235959'; // STACKOUT_DATE
          }
          if ($config['head_tab_detil_tl'] == null) {
            $vparam .= '^N'; // TL_FLAG
          }else{
            $vparam .= '^'.$listA[$config['head_tab_detil_tl']]; // TL_FLAG

          }
          if ($req_type == 'BM' and $list->dtl_bm_type == 'Muat' and $listA[$config['head_tab_detil_tl']] == 'Y') {
            $vparam .= '^REC'; // IF_FLAG
          } else{
            $vparam .= '^'.$req_type; // IF_FLAG
          }
          if ($list->dtl_character_id == 1) { $vparam .= '^N'; }else{ $vparam .= '^Y'; } // HZ
          $vparam .= '^'; // OI
          $vparam .= '^'.$hscode; // HS_CODE
          $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // CARGO_ID
          if ($list->dtl_character_id == 1) { $vparam .= '^Y'; }else{ $vparam .= '^N'; } // DS

          // $vparam .= '^E'; // EI
          if ($req_type == 'BM') {
            if ($list->dtl_bm_type == 'Muat') {
              $vparam .= '^E'; // EI
            } else if ($list->dtl_bm_type == 'Bongkar'){
              $vparam .= '^I'; // EI
            }
          } else if ($req_type == 'REC') {
            $vparam .= '^E'; // EI
          } else if ($req_type == 'DEL') {
            $vparam .= '^I'; // EI
          }
          $vparam .= '^'.$head[$config['head_cust_name']]; // CUSTOMER_NAME
          $vparam .= '^'.$head[$config['head_cust_addr']]; // CUSTOMER_ADDRESS
          $vparam .= '^'.date('Ymd', strtotime($paid_date)).'235959'; // DATE_PAID
          $vparam .= '^'; // DO_NUMBER
          $vparam .= '^'; // POD
          $vparam .= '^'; // POL
          $vparam .= '^'; // POR
          $vparam .= '^'; // SIZE_
          $vparam .= '^'; // TYPE_
          $vparam .= '^'; // STATUS_
          $vparam .= '^201'; // id_Port

          $string_json = '{
              "savecargoNpkInterfaceRequest": {
                  "esbHeader": {
                      "externalId": "2",
                      "timestamp": "2"
                  },
                  "esbBody": {
                      "blNumber": "'.$listA[$config['head_tab_detil_bl']].'",
                      "cargoName": "'.$list->dtl_cmdty_name.'",
                      "vvdNumber": "'.$head[$config['head_vvd_id']].'",
                      "insertData": "'.$vparam.'"
                  }
              }
          }';
          $string_json_arr[] = json_decode($string_json);

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
            return $e->getResponse();
          }
          $hsl[] = json_decode($res->getBody()->getContents(), true);

          $hitEsb[] = [
            'request' => json_decode($string_json),
            'response' => json_decode($res->getBody()->getContents(), true),
          ];
        //first


        if ($req_type == 'BM' and $list->dtl_bm_type == 'Bongkar' and $listA[$config['head_tab_detil_tl']] == 'Y') {
          $vparam = '';
          if ($req_type == 'BM' and $list->dtl_bm_type == 'Muat' and $listA[$config['head_tab_detil_tl']] == 'Y') {
            $vparam .= 'REC'; // IF_FLAG
          } else if ($req_type == 'BM' and $list->dtl_bm_type == 'Bongkar' and $listA[$config['head_tab_detil_tl']] == 'Y'){
            $vparam .= 'DEL'; // IF_FLAG
          } else{
            $vparam .= $req_type; // IF_FLAG
          }
          $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // ID_CARGO
          $packageNameParent = DB::connection('mdm')->select(DB::raw('SELECT (CASE WHEN B.PACKAGE_NAME IS NULL THEN A.PACKAGE_NAME ELSE B.PACKAGE_NAME END) PACKAGE_NAME FROM TM_PACKAGE A LEFT JOIN TM_PACKAGE B ON B.PACKAGE_CODE = A.PACKAGE_PARENT_CODE WHERE A.PACKAGE_ID ='.$list->dtl_pkg_id));
          $packageNameParent = $packageNameParent[0];
          $packageNameParent = $packageNameParent->package_name;
          $vparam .= '^'.$packageNameParent; // PKG_NAME
          $paramTon = 0;
          $paramCub = 0;
          if ($list->dtl_unit_id == 1 or $list->dtl_unit_id == 2) {
            $paramTon = $list->dtl_qty;
          } else if ($list->dtl_unit_id == 3) {
            $paramCub = $list->dtl_qty;
          }
          $vparam .= '^'.$paramTon; // TON
          $vparam .= '^'.$paramCub; // CUBIC
          $vparam .= '^0'; // QTY
          $vparam .= '^'; // ID_INV
          $vparam .= '^'.$head[$config['head_no']]; // ID_REQ
          if ($req_type == 'BM') {
            $vparam .= '^'.date('Ymd', strtotime($head[$config['head_open_stack']])).'235959'; // STACKOUT_DATE
          } else{
            $vparam .= '^'.date('Ymd', strtotime($head[$config['head_closing_time']])).'235959'; // STACKOUT_DATE
          }
          if ($config['head_tab_detil_tl'] == null) {
            $vparam .= '^N'; // TL_FLAG
          }else{
            $vparam .= '^'.$listA[$config['head_tab_detil_tl']]; // TL_FLAG

          }
          if ($req_type == 'BM' and $list->dtl_bm_type == 'Muat' and $listA[$config['head_tab_detil_tl']] == 'Y') {
            $vparam .= '^REC'; // IF_FLAG
          } else if ($req_type == 'BM' and $list->dtl_bm_type == 'Bongkar' and $listA[$config['head_tab_detil_tl']] == 'Y'){
            $vparam .= '^DEL'; // IF_FLAG
          } else{
            $vparam .= '^'.$req_type; // IF_FLAG
          }
          if ($list->dtl_character_id == 1) { $vparam .= '^N'; }else{ $vparam .= '^Y'; } // HZ
          $vparam .= '^'; // OI
          $vparam .= '^'.$hscode; // HS_CODE
          $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // CARGO_ID
          if ($list->dtl_character_id == 1) { $vparam .= '^Y'; }else{ $vparam .= '^N'; } // DS

          // $vparam .= '^E'; // EI
          if ($req_type == 'BM') {
            if ($list->dtl_bm_type == 'Muat') {
              $vparam .= '^E'; // EI
            } else if ($list->dtl_bm_type == 'Bongkar'){
              $vparam .= '^I'; // EI
            }
          } else if ($req_type == 'REC') {
            $vparam .= '^E'; // EI
          } else if ($req_type == 'DEL') {
            $vparam .= '^I'; // EI
          }
          $vparam .= '^'.$head[$config['head_cust_name']]; // CUSTOMER_NAME
          $vparam .= '^'.$head[$config['head_cust_addr']]; // CUSTOMER_ADDRESS
          $vparam .= '^'.date('Ymd', strtotime($paid_date)).'235959'; // DATE_PAID
          $vparam .= '^'; // DO_NUMBER
          $vparam .= '^'; // POD
          $vparam .= '^'; // POL
          $vparam .= '^'; // POR
          $vparam .= '^'; // SIZE_
          $vparam .= '^'; // TYPE_
          $vparam .= '^'; // STATUS_
          $vparam .= '^201'; // id_Port

          $string_json = '{
              "savecargoNpkInterfaceRequest": {
                  "esbHeader": {
                      "externalId": "2",
                      "timestamp": "2"
                  },
                  "esbBody": {
                      "blNumber": "'.$listA[$config['head_tab_detil_bl']].'",
                      "cargoName": "'.$list->dtl_cmdty_name.'",
                      "vvdNumber": "'.$head[$config['head_vvd_id']].'",
                      "insertData": "'.$vparam.'"
                  }
              }
          }';
          $string_json_arr[] = json_decode($string_json);

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
            return $e->getResponse();
          }

          $hsl[] = json_decode($res->getBody()->getContents(), true);
          $hitEsb[] = [
            'request' => json_decode($string_json),
            'response' => json_decode($res->getBody()->getContents(), true),
          ];
        }

        $respn[] = $hsl;
      }
      return [
        'result' => 'Success', 
        'hitEsb' => $hitEsb
        // 'response' => $respn, 
        // 'json' => $string_json_arr
      ];
      // return ['result' => 'Success', 'json' => $string_json_arr];
    }

    public static function sendUperPutReceipt($uper_id, $pay){
      $uperH = TxHdrUper::find($uper_id);
      $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$uperH->uper_branch_id)->where('branch_code',$uperH->uper_branch_code)->get();
      $branch = $branch[0];
      $bank = DB::connection('mdm')->table('TM_BANK')->where('bank_code',$pay->pay_bank_code)->where('branch_id',$pay->pay_branch_id)->where('branch_code',$uperH->uper_branch_code)->get();
      $bank = $bank[0];

      $endpoint_url=config('endpoint.sendUperPutReceipt');

      if (strtoupper($branch->branch_code) == 'PTN') {
        $branchCode = 'BTN';
      }else{
        $branchCode = $branch->branch_code;
      }

      $string_json= '{
         "arRequestDoc":{
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody":[
               {
                  "header":{
                     "orgId":"'.$branch->branch_org_id.'",
                     "receiptNumber":"'.$uperH->uper_no.'",
                     "receiptMethod":"UPER",
                     "receiptAccount":"'.$pay->pay_account_name.' '.$pay->pay_bank_code.' '.$pay->pay_account_no.'",
                     "bankId":"'.$bank->bank_id.'",
                     "customerNumber":"'.$pay->pay_cust_id.'",
                     "receiptDate":"'.date('Y-m-d H:i:s', strtotime($pay->pay_date)).'",
                     "currencyCode":"'.$pay->pay_currency.'",
                     "status":"P",
                     "amount":"'.$pay->pay_amount.'",
                     "processFlag":"",
                     "errorMessage":"",
                     "apiMessage":"",
                     "attributeCategory":"UPER",
                     "referenceNum":"",
                     "receiptType":"",
                     "receiptSubType":"",
                     "createdBy":"-1",
                     "creationDate":"'.$pay->pay_create_date.'",
                     "terminal":"",
                     "attribute1":"'.$uperH->uper_no.'",
                     "attribute2":"'.$uperH->uper_cust_id.'",
                     "attribute3":"'.$uperH->uper_cust_name.'",
                     "attribute4":"'.$uperH->uper_cust_address.'",
                     "attribute5":"'.$uperH->uper_cust_npwp.'",
                     "attribute6":"",
                     "attribute7":"'.$uperH->uper_currency_code.'",
                     "attribute8":"'.$uperH->uper_vessel_name.'",
                     "attribute9":"",
                     "attribute10":"",
                     "attribute11":"",
                     "attribute12":"",
                     "attribute13":"ID-001",
                     "attribute14":"'.$uperH->uper_sub_context.'",
                     "attribute15":"",
                     "statusReceipt":"N",
                     "sourceInvoice":"BRG_UPER",
                     "statusReceiptMsg":"",
                     "invoiceNum":"",
                     "amountOrig":null,
                     "lastUpdateDate":"'.$pay->pay_create_date.'",
                     "lastUpdateBy":"-1",
                     "branchCode":"'.$branchCode.'",
                     "branchAccount":"'.$branch->branch_account.'",
                     "sourceInvoiceType":"NPKBILLING",
                     "remarkToBankId":"BANK_ACCOUNT_ID",
                     "sourceSystem":"NPKBILLING",
                     "comments":"'.$pay->pay_note.'",
                     "cmsYn":"N",
                     "tanggalTerima":null,
                     "norekKoran":""
                  }
               }
            ],
            "esbSecurity":{
               "orgId":"'.$branch->branch_org_id.'",
               "batchSourceId":"",
               "lastUpdateLogin":"",
               "userId":"",
               "respId":"",
               "ledgerId":"",
               "respApplId":"",
               "batchSourceName":""
            }
         }
      }';
      // return $string_json;
      $username="billing";
      $password ="b1Llin9";
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
        $retrn = [
          "request" => $options,
          "response" => json_decode($res->getBody()->getContents(), true)
        ];
        return $retrn;
      } catch (ClientException $e) {
        $retrn = [
          "request" => $options,
          "response" => $e->getResponse()
        ];
        return $retrn;
      }
    }

    public static function sendNotaPutReceipt($nota_id, $pay){
      $notaH = TxHdrNota::find($nota_id);
      $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$notaH->nota_branch_id)->where('branch_code',$notaH->nota_branch_code)->get();
      $branch = $branch[0];
      $bank = DB::connection('mdm')->table('TM_BANK')->where('bank_code',$pay->pay_bank_code)->where('branch_id',$pay->pay_branch_id)->where('branch_code',$notaH->nota_branch_code)->get();
      $bank = $bank[0];

      $endpoint_url=config('endpoint.sendNotaPutReceipt');

      if (strtoupper($branch->branch_code) == 'PTN') {
        $branchCode = 'BTN';
      }else{
        $branchCode = $branch->branch_code;
      }

      $string_json= '{
         "arRequestDoc":{
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody":[
               {
                  "header":{
                     "orgId":"'.$branch->branch_org_id.'",
                     "receiptNumber":"'.$notaH->nota_no.'",
                     "receiptMethod":"BANK",
                     "receiptAccount":"'.$pay->pay_account_name.' '.$pay->pay_bank_code.' '.$pay->pay_account_no.'",
                     "bankId":"'.$bank->bank_id.'",
                     "customerNumber":"'.$pay->pay_cust_id.'",
                     "receiptDate":"'.$pay->pay_date.'",
                     "currencyCode":"'.$pay->pay_currency.'",
                     "status":"P",
                     "amount":"'.$pay->pay_amount.'",
                     "processFlag":"",
                     "errorMessage":"",
                     "apiMessage":"",
                     "attributeCategory":"BANK",
                     "referenceNum":"",
                     "receiptType":"",
                     "receiptSubType":"",
                     "createdBy":"-1",
                     "creationDate":"'.$pay->pay_create_date.'",
                     "terminal":"",
                     "attribute1":"'.$notaH->nota_no.'",
                     "attribute2":"'.$notaH->nota_cust_id.'",
                     "attribute3":"'.$notaH->nota_cust_name.'",
                     "attribute4":"'.$notaH->nota_cust_address.'",
                     "attribute5":"'.$notaH->nota_cust_npwp.'",
                     "attribute6":"",
                     "attribute7":"'.$notaH->nota_currency_code.'",
                     "attribute8":"'.$notaH->nota_vessel_name.'",
                     "attribute9":"",
                     "attribute10":"",
                     "attribute11":"",
                     "attribute12":"",
                     "attribute13":"",
                     "attribute14":"'.$notaH->nota_sub_context.'",
                     "attribute15":"",
                     "statusReceipt":"N",
                     "sourceInvoice":"BRG",
                     "statusReceiptMsg":"",
                     "invoiceNum":"'.$notaH->nota_no.'",
                     "amountOrig":null,
                     "lastUpdateDate":"'.$pay->pay_create_date.'",
                     "lastUpdateBy":"-1",
                     "branchCode":"'.$branchCode.'",
                     "branchAccount":"'.$branch->branch_account.'",
                     "sourceInvoiceType":"NPKBILLING",
                     "remarkToBankId":"BANK_ACCOUNT_ID",
                     "sourceSystem":"NPKBILLING",
                     "comments":"'.$pay->pay_note.'",
                     "cmsYn":"N",
                     "tanggalTerima":null,
                     "norekKoran":""
                  }
               }
            ],
            "esbSecurity":{
               "orgId":"'.$branch->branch_org_id.'",
               "batchSourceId":"",
               "lastUpdateLogin":"",
               "userId":"",
               "respId":"",
               "ledgerId":"",
               "respApplId":"",
               "batchSourceName":""
            }
         }
      }';
      $username="billing";
      $password ="b1Llin9";
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
        return json_decode($res->getBody()->getContents(), true);
      } catch (ClientException $e) {
        return $e->getResponse();
      }
    }

    public static function truckRegistration($input){
      $endpoint_url=config('endpoint.truckRegistration');

      $string_json = '{
            "truckRegistrationInterfaceRequest": {
              "esbHeader": {
                "internalId": "",
                "externalId": "",
                "timestamp": "",
                "responseTimestamp": "",
                "responseCode": "",
                "responseMessage": ""
                },
                "esbBody": {
                    "vTruckId": "'.str_replace(' ','',$input['truck_plat_no']).'",
                    "vTruckNumber": "'.$input['truck_plat_no'].'",
                    "vRFIDCode": "'.$input['truck_rfid_code'].'",
                    "vCustomerName": "'.$input['customer_name'].'",
                    "vAddress": "'.$input['customer_address'].'",
                    "vCustomerId": "'.$input['cdm_customer_id'].'",
                    "vKend": "'.$input['truck_type'].'",
                    "vTgl": "'.$input['date'].'",
                    "vTerminalCode": "'.$input['terminal_id'].'"
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
      $res = [
        "request" => json_decode($string_json, true),
        "response" => json_decode($res->getBody()->getContents(), true)
      ];
      return $res;
    }

    public static function updateTid($input){
      $endpoint_url=config('endpoint.updateTid');

      $string_json = '{
            "updateTidInterfaceRequest": {
              "esbHeader": {
                "internalId": "",
                "externalId": "",
                "timestamp": "",
                "responseTimestamp": "",
                "responseCode": "",
                "responseMessage": ""
                },
                "esbBody": {
                    "truckId": "'.$input['truck_id'].'",
                    "truckNumber": "'.$input['truck_plat_no'].'",
                    "rfidCode": "'.$input['truck_rfid_code'].'",
                    "customerName": "'.$input['customer_name'].'",
                    "address": "'.$input['customer_address'].'",
                    "customerId": "'.$input['cdm_customer_id'].'",
                    "kend": "'.$input['truck_type'].'",
                    "tgl": "'.$input['date'].'",
                    "idTerminal": "'.$input['terminal_id'].'"
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
      $res = [
        "request" => json_decode($string_json, true),
        "response" => json_decode($res->getBody()->getContents(), true)
      ];
      return $res;
    }

    public static function getTruckPrimaryIdTos($input){
      $endpoint_url=config('endpoint.getTruckPrimaryIdTos');

      $string_json = '{
          "getTruckRequest": {
              "esbHeader": {
                          "internalId" : "",
                              "externalId":"",
                              "timestamp":"",
                              "responseTimestamp":"",
                              "responseCode":"",
                              "responseMessage":""
              },
              "esbBody":   {
                              "idTerminal":"201",
                              "tid":"'.$input['truck_id'].'",
                              "truckNumber":"'.str_replace(' ','',$input['truck_plat_no']).'"
                      },
              "esbSecurity": {
                            "orgId":"",
                              "batchSourceId":"",
                              "lastUpdateLogin":"",
                              "userId":"",
                              "respId":"",
                              "ledgerId":"",
                              "respApplId":"",
                              "batchSourceName":"",
                              "category":""
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
      $results = json_decode($res->getBody()->getContents(), true);

      if(isset($results['getTruckResponse']['esbBody']['results'][0]) and isset($results['getTruckResponse']['esbBody']['results'][0]['idTruck'])){
        DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',str_replace(' ','',$input['truck_plat_no']))->update([
          "truck_id_seq" => $results['getTruckResponse']['esbBody']['results'][0]['idTruck']
        ]);
      }
      $res = [
        "request" => json_decode($string_json, true),
        "response" => $results
      ];
      return $res;
    }

    public static function closeTCA($input){
      $endpoint_url=config('endpoint.closeTCA');

      $terminal = DB::connection('mdm')->table('TM_TERMINAL')->where('terminal_code', $input['tca_terminal_code'])->get();
      $terminal = $terminal[0];
      $truck = DB::connection('mdm')->table('TM_TRUCK')->where('truck_id', $input['tca_truck_id'])->get();
      if (count($truck) == 0) {
        return ["Success"=>false, 'result_msg' => 'Fail, not found '.$input['tca_truck_id'].' on mdm.tm_truck'];
      }
      $truck = $truck[0];

      $string_json = '{
              "closeTCAInterfaceRequest": {
               "esbHeader": {
                "internalId": "",
                "externalId": "",
                "timestamp": "",
                "responseTimestamp": "",
                "responseCode": "",
                "responseMessage": ""
                },
                "esbBody": {
                  "vTid": "'.$truck->truck_id_seq.'",
                  "vNoRequest": "'.$input['tca_req_no'].'",
                  "vBlNumber": "'.$input['tca_bl'].'",
                  "vIdTerminal": "'.$terminal->terminal_id.'"
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

      $response = json_decode($res->getBody()->getContents(),TRUE);
      $success  = $response["closeTCAInterfaceResponse"]["esbHeader"]["responseCode"];
      if (isset($response["closeTCAInterfaceResponse"]["esbBody"])) {
        $msg      = $response["closeTCAInterfaceResponse"]["esbBody"]["vMsg"];
      }

      if ($success == "F") {
        return ["success" => $success, "message" => "Data Tidak Ditemukan"];
      } else {
        if ($msg == "NOT OK") {
          return ["success"=> $success, "message" => "Data Gagal di Hapus/ TCA Sedang Berjalan"];
        } else {
          $update = DB::connection('omcargo')->table('TX_HDR_TCA')->update(["TCA_IS_ACTIVE" => '0']);
          return ["success"=> $success, "message" => "TCA Berhasil Di Hapus"];
        }
      }
    }

    public static function createTCA($input, $tca_id){
      $endpoint_url=config('endpoint.createTCA');

      $detail = '';
      foreach ($input['detail'] as $list) {
        $detail .= '{
                    "vNoRequest": "'.$list['vNoRequest'].'",
                    "vTruckId": "'.$list['vTruckId'].'",
                    "vTruckNumber": "'.$list['vTruckNumber'].'",
                    "vBlNumber": "'.$list['vBlNumber'].'",
                    "vTcaCompany": "'.$list['vTcaCompany'].'",
                    "vEi": "'.$list['vEi'].'",
                    "vRfidCode": "'.$list['vRfidCode'].'",
                    "vIdServiceType": "'.$list['vIdServiceType'].'",
                    "vServiceType": "'.$list['vServiceType'].'",
                    "vIdTruck": "'.$list['vIdTruck'].'",
                    "vIdVvd": "'.$list['vIdVvd'].'",
                    "vIdTerminal": "'.$list['vIdTerminal'].'"
                  },';
      }
      $detail = substr($detail, 0, -1);

      $string_json = '{
       "createTCAInterfaceRequest": {
        "esbHeader": {
          "internalId": "",
          "externalId": "",
          "timestamp": "",
          "responseTimestamp": "",
          "responseCode": "",
          "responseMessage": ""
          },
         "esbBody": {
          "vVessel": "'.$input['vVessel'].'",
           "vVin": "'.$input['vVin'].'",
           "vVout": "'.$input['vVout'].'",
           "vNoRequest": "'.$input['vNoRequest'].'",
           "vCustomerName": "'.$input['vCustomerName'].'",
           "vCustomerId": "'.$input['vCustomerId'].'",
           "vPkgName": "'.$input['vPkgName'].'",
           "vQty": "'.$input['vQty'].'",
           "vTon": "'.$input['vTon'].'",
           "vBlNumber": "'.$input['vBlNumber'].'",
           "vBlDate": "'.$input['vBlDate'].'",
           "vEi": "'.$input['vEi'].'",
           "vHsCode": "'.$input['vHsCode'].'",
           "vIdServicetype": "'.$input['vIdServicetype'].'",
           "vServiceType": "'.$input['vServiceType'].'",
           "vIdVvd": "'.$input['vIdVvd'].'",
           "vIdTerminal": "'.$input['vIdTerminal'].'",
           "document": [{ "documentName": "test_api.pdf" }],
           "detail":['.$detail.']
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
        $error = $e->getRequest() . "\n";
        if ($e->hasResponse()) {
          $error .= $e->getResponse() . "\n";
        }
        return ["Success"=>false, "result" => $error];
      }
      $res = json_decode($res->getBody()->getContents(), true);
      if ($res['esbBody']['statusCode'] == 'F') {
        return ["Success"=>false, "result" => $res['esbBody']['statusMessage']];
      }
      DB::connection('omcargo')->table('TX_HDR_TCA')->where('tca_id', $tca_id)->update([
        "tca_status" => 2
      ]);
      return ["Success"=>true, "request" => $options, "result" => $res['esbBody']['statusMessage']];
    }

    public static function sendNotaProforma($nota_id){
      $endpoint_url=config('endpoint.sendNotaProforma');
      $find = TxHdrNota::find($nota_id);
      $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$find->nota_branch_id)->where('branch_code',$find->nota_branch_code)->get();
      $branch = $branch[0];

      $findU_uper_no = null;
      $findU_uper_terminal_code = '00';
      $findU_uper_amount = null;

      $findU = TxHdrUper::where('uper_req_no', $find->nota_req_no)->where('uper_cust_id', $find->nota_cust_id)->first();
      if (!empty($findU)) {
        $findU_uper_no = $findU->uper_no;
        // $findU_uper_terminal_code = $findU->uper_terminal_code;
        $findP = DB::connection('omcargo')->table('TX_PAYMENT')->where('pay_no',$findU_uper_no)->where('pay_branch_code',$findU->uper_branch_code)->get();
        $findU_uper_amount = $findP[0]->pay_amount;
      }

      if (strtoupper($find->nota_branch_code) == 'PTN') {
        $branchCode = 'BTN';
      }else{
        $branchCode = $find->nota_branch_code;
      }

      $branchAccount = $branch->branch_account_erp;
      $notaDateNoHour= date('Y-m-d', strtotime($find->nota_date));
      $notaDate      = $find->nota_date;

      $head_json = '{
         "billerRequestId":"'.$find->nota_req_no.'",
         "orgId":"'.$branch->branch_org_id.'",
         "trxNumber":"'.$find->nota_no.'",
         "trxNumberOrig":"",
         "trxNumberPrev":"",
         "trxTaxNumber":"",
         "trxDate":"'.$notaDate.'",
         "trxClass":"INV",
         "trxTypeId":"-1",
         "paymentReferenceNumber":"",
         "referenceNumber":"",
         "currencyCode":"'.$find->nota_currency_code.'",
         "currencyType":"",
         "currencyRate":"0",
         "currencyDate":null,
         "amount":"'.$find->nota_amount.'",
         "customerNumber":"'.$find->nota_cust_id.'",
         "customerClass":"",
         "billToCustomerId":"-1",
         "billToSiteUseId":"-1",
         "termId":null,
         "status":"P",
         "headerContext":"'.$find->nota_context.'",
         "headerSubContext":"'.$find->nota_sub_context.'",
         "startDate":null,
         "endDate":null,
         "terminal":"'.$findU_uper_terminal_code.'",
         "vesselName":"'.$find->nota_vessel_name.'",
         "branchCode":"'.$branchCode.'",
         "errorMessage":"",
         "apiMessage":"",
         "createdBy":"-1",
         "creationDate":"'.$notaDate.'",
         "lastUpdatedBy":"-1",
         "lastUpdateDate":"'.$notaDate.'",
         "lastUpdateLogin":"-1",
         "customerTrxIdOut":null,
         "processFlag":"",
         "attribute1":"'.$find->nota_sub_context.'",
         "attribute2":"'.$find->nota_cust_id.'",
         "attribute3":"'.$find->nota_cust_name.'",
         "attribute4":"'.$find->nota_cust_address.'",
         "attribute5":"'.$find->nota_cust_npwp.'",
         "attribute6":"",
         "attribute7":"",
         "attribute8":"",
         "attribute9":"",
         "attribute10":"",
         "attribute11":"",
         "attribute12":"",
         "attribute13":"",
         "attribute14":"'.$find->nota_no.'",
         "attribute15":"",
         "interfaceHeaderAttribute1":"'.$findU_uper_no.'",
         "interfaceHeaderAttribute2":"'.$find->nota_vessel_name.'",
         "interfaceHeaderAttribute3":"",
         "interfaceHeaderAttribute4":"",
         "interfaceHeaderAttribute5":"",
         "interfaceHeaderAttribute6":"",
         "interfaceHeaderAttribute7":"",
         "interfaceHeaderAttribute8":"",
         "interfaceHeaderAttribute9":"",
         "interfaceHeaderAttribute10":"",
         "interfaceHeaderAttribute11":"",
         "interfaceHeaderAttribute12":"",
         "interfaceHeaderAttribute13":"",
         "interfaceHeaderAttribute14":"",
         "interfaceHeaderAttribute15":"",
         "customerAddress":"'.$find->nota_cust_address.'",
         "customerName":"'.$find->nota_cust_name.'",
         "sourceSystem":"NPKBILLING",
         "arStatus":"N",
         "sourceInvoice":"'.$find->nota_context.'",
         "arMessage":"",
         "customerNPWP":"'.$find->nota_cust_npwp.'",
         "perKunjunganFrom":null,
         "perKunjunganTo":null,
         "jenisPerdagangan":"",
         "docNum":"",
         "statusLunas":"",
         "tglPelunasan":"'.$notaDateNoHour.'",
         "amountTerbilang":"",
         "ppnDipungutSendiri":"'.$find->nota_ppn.'",
         "ppnDipungutPemungut":"",
         "ppnTidakDipungut":"",
         "ppnDibebaskan":"",
         "uangJaminan":"'.$findU_uper_amount.'",
         "piutang":"'.($find->nota_amount-$findU_uper_amount).'",
         "sourceInvoiceType":"'.$find->nota_context.'",
         "branchAccount":"'.$branchAccount.'",
         "statusCetak":"",
         "statusKirimEmail":"",
         "amountDasarPenghasilan":"'.$find->nota_dpp.'",
         "amountMaterai":null,
         "ppn10Persen":"'.$find->nota_ppn.'",
         "statusKoreksi":"",
         "tanggalKoreksi":null,
         "keteranganKoreksi":""
      }';

      $lines_json = '';
      if (in_array($find->nota_group_id, [14,15])) { // jika bprp
        $dtl_line_count = 0;
        $detil = DB::connection('omcargo')->table('V_TX_DTL_NOTA_WITH_MEMOLINE')->where('nota_hdr_id', $nota_id)->get();
        foreach ($detil as $list) {
          $dtl_line_count++;
          $masa11 = "";
          $masa12 = "";
          $masa2 = "";
          $hitM1 = "";
          $hitM2 = "";
          $dateIn = $notaDate;
          $dateOut = $notaDate;
          if ($list->dtl_group_tariff_id == 10) {
            $masa11 = $list->masa1;
            $masa2 = $list->masa2;

            $hitM1 = $masa11*$list->dtl_qty*$list->trf1up;
            $hitM2 = $masa2*$list->dtl_qty*$list->trf2up;
          }
          $bprpHeadId = DB::connection('omcargo')->table('TX_HDR_BPRP')->where('BPRP_NO', $find->nota_real_no)->get();
          $bprpHeadId = $bprpHeadId[0];
          $bprpHeadId = $bprpHeadId->bprp_id;
          $bprpDtl = DB::connection('omcargo')->table('TX_DTL_BPRP')->where('HDR_BPRP_ID', $bprpHeadId)->where('DTL_BL', $list->dtl_bl)->get();
          if (count($bprpDtl) > 0) {
            $bprpDtl = $bprpDtl[0];
            $dateIn = date('Y-m-d', strtotime($bprpDtl->dtl_datein));
            $dateOut = date('Y-m-d', strtotime($bprpDtl->dtl_dateout));
          }

          if ($list->dtl_group_tariff_name == 'PENUMPUKAN') {
            $descrpt = $list->dtl_commodity.' '.$list->dtl_qty.' '.$list->dtl_unit_name;
          }else{
            $descrpt = $list->dtl_group_tariff_name;
          }

          $lines_json  .= '{
            "billerRequestId":"'.$find->nota_req_no.'",
            "trxNumber":"'.$find->nota_no.'",
            "lineId":null,
            "lineNumber":"'.$dtl_line_count.'",
            "description":"'.$descrpt.'",
            "memoLineId":null,
            "glRevId":null,
            "lineContext":"",
            "taxFlag":"Y",
            "serviceType":"'.$list->dtl_line_desc.'",
            "eamCode":"",
            "locationTerminal":"",
            "amount":"'.$list->dtl_dpp.'",
            "taxAmount":"'.$list->dtl_ppn.'",
            "startDate":"'.$dateIn.'",
            "endDate":"'.$dateOut.'",
            "createdBy":"-1",
            "creationDate":"'.$notaDateNoHour.'",
            "lastUpdatedBy":"-1",
            "lastUpdatedDate":"'.$notaDateNoHour.'",
            "interfaceLineAttribute1":"",
            "interfaceLineAttribute2":"'.$dateIn.'",
            "interfaceLineAttribute3":"'.$dateOut.'",
            "interfaceLineAttribute4":"'.$masa11.'",
            "interfaceLineAttribute5":"'.$masa2.'",
            "interfaceLineAttribute6":"'.$masa12.'",
            "interfaceLineAttribute7":"'.$hitM1.'",
            "interfaceLineAttribute8":"'.$hitM2.'",
            "interfaceLineAttribute9":"",
            "interfaceLineAttribute10":"'.$list->trf1up.'",
            "interfaceLineAttribute11":"'.$list->trf2up.'",
            "interfaceLineAttribute12":"'.$list->dtl_package.'",
            "interfaceLineAttribute13":"'.$list->dtl_qty.'",
            "interfaceLineAttribute14":"'.$list->dtl_unit_name.'",
            "interfaceLineAttribute15":"",
            "lineDoc":""
          },';
        }
      }else if ($find->nota_group_id == 13) {
        $detil = DB::connection('omcargo')->table('TX_DTL_NOTA')->where('nota_hdr_id', $nota_id)->get();
        foreach ($detil as $list) {
          $lines_json  .= '{
            "billerRequestId":"'.$find->nota_req_no.'",
            "trxNumber":"'.$find->nota_no.'",
            "lineId":null,
            "lineNumber":"'.$list->dtl_line.'",
            "description":"'.$list->dtl_service_type.'",
            "memoLineId":null,
            "glRevId":null,
            "lineContext":"",
            "taxFlag":"Y",
            "serviceType":"'.$list->dtl_line_desc.'",
            "eamCode":"",
            "locationTerminal":"",
            "amount":"'.$list->dtl_dpp.'",
            "taxAmount":"'.$list->dtl_ppn.'",
            "startDate":"'.$notaDateNoHour.'",
            "endDate":"'.$notaDateNoHour.'",
            "createdBy":"-1",
            "creationDate":"'.$notaDateNoHour.'",
            "lastUpdatedBy":"-1",
            "lastUpdatedDate":"'.$notaDateNoHour.'",
            "interfaceLineAttribute1":"",
            "interfaceLineAttribute2":"'.$list->dtl_service_type.'",
            "interfaceLineAttribute3":"-",
            "interfaceLineAttribute4":"-",
            "interfaceLineAttribute5":"-",
            "interfaceLineAttribute6":"-",
            "interfaceLineAttribute7":"-",
            "interfaceLineAttribute8":"-",
            "interfaceLineAttribute9":"",
            "interfaceLineAttribute10":"-",
            "interfaceLineAttribute11":"-",
            "interfaceLineAttribute12":"-",
            "interfaceLineAttribute13":"'.$list->dtl_qty.'  ",
            "interfaceLineAttribute14":"'.$list->dtl_unit_name.'",
            "interfaceLineAttribute15":"",
            "lineDoc":""
          },';
        }
      }
      $lines_json = substr($lines_json, 0,-1);

      $string_json = '{
       "arRequestDoc":{
        "esbHeader": {
          "internalId": "",
          "externalId": "",
          "timestamp": "",
          "responseTimestamp": "",
          "responseCode": "",
          "responseMessage": ""
          },
          "esbBody":[
            { "header": '.$head_json.', "lines": ['.$lines_json.'] }
          ],
          "esbSecurity":{
             "orgId":"'.$branch->branch_org_id.'",
             "batchSourceId":"",
             "lastUpdateLogin":"",
             "userId":"",
             "respId":"",
             "ledgerId":"",
             "respApplId":"",
             "batchSourceName":""
          }
        }
      }';

      $username="billing";
      $password ="b1Llin9";
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

      $res = json_decode($res->getBody()->getContents(), true);
      return ['response'=>$res, 'request'=>$options];
    }

    public static function notaProformaPutApply($nota_id, $pay){
      $notaH = TxHdrNota::find($nota_id);
      $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$notaH->nota_branch_id)->where('branch_code',$notaH->nota_branch_code)->get();
      $branch = $branch[0];
      $bank = DB::connection('mdm')->table('TM_BANK')->where('bank_code',$pay->pay_bank_code)->where('branch_id',$pay->pay_branch_id)->get();
      $bank = $bank[0];

      $endpoint_url=config('endpoint.notaProformaPutApply');
      $string_json = '{
         "arRequestDoc":{
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody":[
               {
                  "header":{
                     "paymentCode":"'.$notaH->nota_no.'",
                     "trxNumber":"'.$notaH->nota_no.'",
                     "orgId":"'.$branch->branch_org_id.'",
                     "amountApplied":"'.$pay->pay_amount.'",
                     "cashReceiptId":null,
                     "customerTrxId":"'.$pay->pay_cust_id.'",
                     "paymentScheduleId":null,
                     "bankId":"'.$bank->bank_id.'",
                     "receiptSource":"ESB",
                     "legacySystem":"NPKBILLING",
                     "statusTransfer":"N",
                     "errorMessage":null,
                     "requestIdApply":null,
                     "createdBy":"-1",
                     "creationDate":"'.date('Y-m-d', strtotime($pay->pay_create_date)).'",
                     "lastUpdateBy":"-1",
                     "lastUpdateDate":"'.date('Y-m-d', strtotime($pay->pay_create_date)).'",
                     "amountPaid":"'.$pay->pay_amount.'",
                     "epay":"N"
                  }
               }
            ],
            "esbSecurity":{
               "orgId":"'.$branch->branch_org_id.'",
               "batchSourceId":"",
               "lastUpdateLogin":"",
               "userId":"",
               "respId":"",
               "ledgerId":"",
               "respApplId":"",
               "batchSourceName":""
            }
         }
      }';
      $username="billing";
      $password ="b1Llin9";
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
        return json_decode($res->getBody()->getContents(), true);
      } catch (ClientException $e) {
        return $e->getResponse();
      }
    }

    public static function uperSimkeuCek($input){
      $cekUperPaid = TxHdrUper::where('uper_no',$input['uper_no'])->where('uper_paid', 'Y')->count();
      if ($cekUperPaid > 0) {
        return ['result' => "Info, uper already paid!", "Success" => true];
      }
      $endpoint_url=config('endpoint.uperSimkeuCek');
      $string_json = '{
         "inquiryStatusReceiptRequest":{
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            "esbBody":[
               {
                  "receiptNumber":"'.$input['uper_no'].'"
               }
            ]
         }
      }';

      $username="billing";
      $password ="b1Llin9";
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
        return $e->getResponse();
      }
      $results = json_decode($res->getBody()->getContents(), true);
      if ($results['inquiryStatusReceiptResponse']['esbHeader']['responseCode'] != 1) {
        return ['Success' => true, 'result' => 'Data dalam antrian, silahkan coba beberapa saat lagi... '.$results['inquiryStatusReceiptResponse']['esbHeader']['responseMessage']];
      }else if ($results['inquiryStatusReceiptResponse']['esbHeader']['responseCode'] == 1) {
        if ($results['inquiryStatusReceiptResponse']['esbBody']['details'][0]['statusReceipt'] == 'S') {
          TxHdrUper::where('uper_no',$input['uper_no'])->update(['uper_paid' => 'Y']);
          $upr = TxHdrUper::where('uper_no',$input['uper_no'])->get();
          $dt = [
            'req_no' => $upr[0]->uper_req_no,
            'uper_paid_date' => $upr[0]->uper_paid_date
          ];
          $sendRequestBooking = UperRequest::sendRequestBooking($dt);
          return ['result' => $results['inquiryStatusReceiptResponse']['esbBody']['details'][0]['statusReceiptMsg'], 'uper_no' => $input['uper_no'], 'sendRequestBooking' => $sendRequestBooking];
        }else if($results['inquiryStatusReceiptResponse']['esbBody']['details'][0]['statusReceipt'] == 'F'){
          // TxHdrUper::where('uper_no',$input['uper_no'])->update(['uper_paid' => 'F']);
          return ['Success' => false, 'result' => $results['inquiryStatusReceiptResponse']['esbBody']['details'][0]['statusReceiptMsg'], 'uper_no' => $input['uper_no']];
        }else{
          return ['Success' => true, 'result' => 'Data dalam antrian, silahkan coba beberapa saat lagi... '. $results['inquiryStatusReceiptResponse']['esbBody']['details'][0]['statusReceiptMsg'], 'uper_no' => $input['uper_no']];
        }
      }
    }

    public static function notaProformaSimkeuCek($input){
      $endpoint_url=config('endpoint.notaProformaSimkeuCek');
      $string_json = '{
         "inquiryStatusLusnasRequest":{
          "esbHeader": {
            "internalId": "",
            "externalId": "",
            "timestamp": "",
            "responseTimestamp": "",
            "responseCode": "",
            "responseMessage": ""
            },
            },
            "esbBody":[
               {
                  "trxNumber":"'.$input['nota_no'].'"
               }
            ]
         }
      }';

      $username="billing";
      $password ="b1Llin9";
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
        return $e->getResponse();
      }
      $results = json_decode($res->getBody()->getContents(), true);
      if ($results['inquiryStatusLunasResponse']['esbHeader']['responseCode'] != 1) {
        return ['Success' => false, 'result' => $results['inquiryStatusLunasResponse']['esbHeader']['responseMessage'], 'esbRes' => $results];
      }else if ($results['inquiryStatusLunasResponse']['esbHeader']['responseCode'] == 1) {
        if ($results['inquiryStatusLunasResponse']['esbBody']['details'][0]['statusLunas'] == 'S') {
          TxHdrNota::where('nota_no',$input['nota_no'])->update(['nota_paid' => 'Y']);
          return ['result' => 'Nota is paid', 'nota_no' => $input['nota_no'], 'esbRes' => $results];
        }else if ($results['inquiryStatusLunasResponse']['esbBody']['details'][0]['statusLunas'] == 'F') {
          // TxHdrNota::where('nota_no',$input['nota_no'])->update(['nota_paid' => 'F']);
          return ['Success' => false, 'result' => 'Nota is failed', 'nota_no' => $input['nota_no'], 'esbRes' => $results];
        }else{
          return ['Success' => false, 'result' => 'Nota sending to simkeu!', 'nota_no' => $input['nota_no'], 'esbRes' => $results];
        }
      }
    }

    public static function getLinkCodeQR($input){
      $endpoint_url=config('endpoint.getLinkCodeQR');
      $string_json = '{
                     "getDataCetakRequest":{
                      "esbHeader": {
                        "internalId": "",
                        "externalId": "",
                        "timestamp": "",
                        "responseTimestamp": "",
                        "responseCode": "",
                        "responseMessage": ""
                        },
                        "esbBody":{
                           "kode":"billingedii",
                           "tipe":"'.$input['type'].'",
                           "nota":"'.$input['no'].'"
                        }
                     }
                  }';

      $username="billing";
      $password ="b1Llin9";
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
        return $e->getResponse();
      }
      $results = json_decode($res->getBody()->getContents(), true);

      return $results['getDataCetakResponse']['esbBody']['url'];
    }

    public static function sendNotifToIBISQA(){
      $endpoint_url=config('endpoint.sendNotifToIBISQA');
      $data = DB::connection('omcargo')->table('TX_NOTIF')->where('notif_flag_status', 0)->get();
      foreach ($data as $list) {
        if ($list->branch_id == 12) {
          $pAppId = 1;
        }else if ($list->branch_id == 4) {
          $pAppId = 2;
        }
        $string_json = '{
          "saveNotifRequest": {
            "esbHeader": {
              "internalId": "",
              "externalId": "",
              "timestamp": "",
              "responseTimestamp": "",
              "responseCode": "",
              "responseMessage": ""
              },
                  "esbBody": {

                          "pNotifType": "'.$list->notif_type.'",
                          "pNotifDate": "'.date('d/m/Y', strtotime($list->notif_date)).'",
                          "pNotifDesc": "'.$list->notif_desc.'",
                          "pNotifBillingId": "'.$list->notif_id.'",
                          "pCustomerId": "'.$list->customer_id.'",
                          "pBranchId": "'.$list->branch_id.'",
                          "pBranchCode": "'.$list->branch_code.'",
                          "pAppId": "'.$pAppId.'"
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
          return $e->getResponse();
        }
        $results = json_decode($res->getBody()->getContents(), true);
        if (isset($results['saveNotifResponse']['esbBody']['pMsg']) and $results['saveNotifResponse']['esbBody']['pMsg'] == 'OK') {
          DB::connection('omcargo')->table('TX_NOTIF')->where('notif_id', $list->notif_id)->update(['notif_flag_status'=>1]);
        }
        DB::connection('omcargo')->table('TH_LOGS_API_STORE')->insert([
          "create_date" => \DB::raw("TO_DATE('".Carbon::now()->format('Y-m-d H:i:s')."', 'YYYY-MM-DD HH24:mi:ss')"),
          "action" => 'sendNotifToIBISQA',
          "json_request" => json_encode($options),
          "json_response" => json_encode($results),
          "create_name" => 'schedule'
        ]);
      }
    }
  // BTN
}
