<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\Models\OmCargo\TxHdrUper;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrDel;
use App\Models\OmCargo\TxHdrRec;
use App\Models\OmCargo\TxHdrNota;
use App\Helper\RequestBooking;

class ConnectedExternalApps{

  public static function getListTCA($input){
    if (empty($input['idCustomer'])) {
      return ['Success' => false, 'msg' => 'idCustomer is required!'];
    }
    $endpoint_url= "10.88.48.57:5555/restv2/npkBilling/getTCAHeader";
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
    $endpoint_url= "10.88.48.57:5555/restv2/npkBilling/getTCADetail";
    $string_json = '{
        "getTCADetailInterfaceRequest": {
            "esbHeader": {},
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
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/trackingVessel";
    $string_json = '{
      "trackingVesselRequest": {
        "esbHeader": {
          "externalId": "5275682735",
          "timestamp": "YYYYMMDD HH:Mi:SS"
          },
          "esbBody": {
            "vesselName": "'.strtoupper($input['query']).'",
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

    return ['pebListResponse' => $body->searchPEBInterfaceResponse];
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
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/searchRealisasi";
    $string_json = '{
      "searchRealisasiRequest": {
        "esbHeader": { },
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
    if ($req_type == 'BM-') {
      $header = TxHdrBm::where('bm_no',$input['req_no'])->first();
      $table = 'TX_HDR_BM';
      $req_type = 'BM';
    }else if($req_type == 'REC') {
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
    }

    $config = RequestBooking::config($table);
    if (!empty($header)) {
      $condition = DB::connection('omcargo')->table('TS_SEND_TOS')->pluck('pkg_id');
      $detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'],$header[$config['head_primery']])->whereIn('dtl_pkg_id', $condition)->get();
      return static::sendRequestBookingNewExcute($req_type, $input['paid_date'], $header, $detil, $config);
    }
  }

  private static function sendRequestBookingNewExcute($req_type, $paid_date, $head, $detil, $config){
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/saveCargoNPK";
    $respn = [];
    foreach ($detil as $list) {
      $listA = (array)$list;
      
      $vparam = '';
      $vparam .= $req_type; // IF_FLAG
      $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // ID_CARGO
      $packageNameParent = DB::connection('mdm')->select(DB::raw('SELECT (CASE WHEN B.PACKAGE_NAME IS NULL THEN A.PACKAGE_NAME ELSE B.PACKAGE_NAME END) PACKAGE_NAME FROM TM_PACKAGE A LEFT JOIN TM_PACKAGE B ON B.PACKAGE_CODE = A.PACKAGE_PARENT_CODE WHERE A.PACKAGE_ID ='.$list->dtl_pkg_id));
      $packageNameParent = $packageNameParent[0];
      $packageNameParent = $packageNameParent->package_name;
      $vparam .= '^'.$packageNameParent; // PKG_NAME
      $vparam .= '^'.$list->dtl_qty; // TON
      $vparam .= '^'.$list->dtl_qty; // CUBIC
      $vparam .= '^'.$list->dtl_qty; // QTY
      $vparam .= '^INVOICE NUMBER'; // ID_INV
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
      $vparam .= '^'.$req_type; // IF_FLAG
      if ($list->dtl_character_id == 1) { $vparam .= '^N'; }else{ $vparam .= '^Y'; } // HZ
      $vparam .= '^'; // OI
      $vparam .= '^'; // HS_CODE
      $vparam .= '^'; // CARGO_ID
      if ($list->dtl_character_id == 1) { $vparam .= '^Y'; }else{ $vparam .= '^N'; } // DS
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
                  "inBlNumber": "'.$listA[$config['head_tab_detil_bl']].'",
                  "inCargoName": "'.$list->dtl_cmdty_name.'",
                  "inVvdNumber": "'.$head[$config['head_vvd_id']].'",
                  "insertdata": "'.$vparam.'"
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

      $respn[] = json_decode($res->getBody()->getContents(), true);
    }
    return ['result' => 'Success', 'response' => $respn];
  }

  private static function sendRequestBookingExcute($head, $detil, $config){
    foreach ($detil as $list) {
        $listA = (array)$list;
        // 
          $consignee = 'consignee';
          $oi = $head[$config['head_trade']];
          $podpol = '';
          $movetype = 'MOVETYPE';
          $startenddate = '';
          $blno = $listA[$config['head_tab_detil_bl']];
          $bldate = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $list->dtl_create_date)->format('m/d/Y');
        // 

        // 
          if (empty($head[$config['head_eta']])) {
            $bm_eta = null;
          }else{
            $bm_eta = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head[$config['head_eta']])->format('m/d/Y');
          }
          if (empty($head[$config['head_etd']])) {
            $bm_etd = null;
          }else{
            $bm_etd = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head[$config['head_etd']])->format('m/d/Y');
          }
          if (empty($head[$config['head_open_stack']])) {
            $bm_open_stack = null;
          }else{
            $bm_open_stack = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head[$config['head_open_stack']])->format('m/d/Y');
          }
          if (empty($head[$config['head_closing_time']])) {
            $bm_closing_time = null;
          }else{
            $bm_closing_time = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head[$config['head_closing_time']])->format('m/d/Y');
          }
        // 

        // 
          $vParam = '';
          $vParam .= $head[$config['head_no']].'^';
          $vParam .= $head[$config['head_cust_name']].'^';
          $vParam .= $head[$config['head_cust_id']].'^';
          $vParam .= $head[$config['head_cust_npwp']].'^';
          $vParam .= $head[$config['head_vessel_name']].'^';
          $vParam .= $bm_eta.'^';
          $vParam .= $bm_etd.'^';
          $vParam .= $head[$config['head_voyin']].'^';
          $vParam .= $head[$config['head_voyout']].'^';
          $vParam .= $bm_closing_time.'^'; // ?
          $vParam .= 'BONGKAR MUAT^'; // ?
          $vParam .= 'CONSIGNEE^'; // ?
          $vParam .= $oi.'^'; // ?
          $vParam .= $podpol.'^'; // ?
          $vParam .= $podpol.'^'; // ?
          $vParam .= $movetype.'^'; // ?
          $vParam .= $startenddate.'^'; // ?
          $vParam .= $startenddate.'^'; // ?
          $vParam .= '0^'; // ?
          $vParam .= $blno.'^'; // ?
          $vParam .= $startenddate.'^'; // ?
          $vParam .= '0^'; // ?
          $vParam .= $head[$config['head_vvd_id']].'^'; // ?
          $vParam .= $oi.'^'; // ?
          $vParam .= $startenddate.'^'; // ?
          $vParam .= '0'; // ?
          $vParamH = $vParam;
        // 

        // 
          $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createBookingHeader";
          $string_json = '{
            "createBookingHeaderInterfaceRequest": {
              "esbHeader": {
                "externalId": "2",
                "timestamp": "2"
              },
              "esbBody": {
                "vParam": "'.$vParamH.'",
                "vId": "'.$head[$config['head_primery']].'",
                "vReqNo": "'.$head[$config['head_no']].'",
                "vBlNo": "'.$blno.'"
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
        // 

        // 
          $merk = '-';
          $model = '-';
          $hz = 'N';
          $distrub = 'N';
          $wight = '0';

          $vParamD = '';
          $vParamD .= $list->dtl_cmdty_name.'^';
          $vParamD .= $list->dtl_cont_type.'^';
          $vParamD .= $merk.'^'; // ?
          $vParamD .= $model.'^'; // ?
          $vParamD .= $hz.'^'; // ?
          $vParamD .= $distrub.'^'; // ?
          $vParamD .= $wight.'^'; // ?
          $vParamD .= $list->dtl_qty.'^';
          $vParamD .= 'N^'; // ?
          $vParamD .= '0'; // ?

          $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createBookingDetail";
          $string_json = '{
            "createBookingDetailInterfaceRequest": {
              "esbHeader": {
                "externalId": "2",
                "timestamp": "2"
                },
                "esbBody": {
                  "vParams": "'.$vParamD.'",
                  "vId": "'.$listA[$config['head_tab_detil_id']].'",
                  "vIdHeader": "'.$head[$config['head_primery']].'"
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
        // 
    }
    return ['Success' => true];
  }

  public static function sendUperPutReceipt($uper_id, $pay){
    $uperH = TxHdrUper::find($uper_id);
    $branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$pay->pay_branch_id)->get();
    $branch = $branch[0];
    $bank = DB::connection('mdm')->table('TM_BANK')->where('bank_code',$pay->pay_bank_code)->where('branch_id',$pay->pay_branch_id)->get();
    $bank = $bank[0];

    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putReceipt";
    $string_json= '{
       "arRequestDoc":{
          "esbHeader":{
             "internalId":"",
             "externalId":"",
             "timestamp":"",
             "responseTimestamp":"",
             "responseCode":"",
             "responseMessage":""
          },
          "esbBody":[
             {
                "header":{
                   "orgId":"'.$uperH->uper_org_id.'",
                   "receiptNumber":"'.$uperH->uper_no.'",
                   "receiptMethod":"UPER",
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
                   "branchCode":"'.$branch->branch_code.'",
                   "branchAccount":"'.$branch->branch_account.'",
                   "sourceInvoiceType":"UPER",
                   "remarkToBankId":"",
                   "sourceSystem":"NPKBILLING",
                   "comments":"",
                   "cmsYn":"N",
                   "tanggalTerima":null,
                   "norekKoran":""
                }
             }
          ],
          "esbSecurity":{
             "orgId":"'.$uperH->uper_org_id.'",
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
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/truckRegistration";

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
                  "vTruckId": "'. str_replace(' ','',$input['truck_plat_no']).'",
                  "vTruckNumber": "'.$input['truck_plat_no'].'",
                  "vRFIDCode": "'.$input['truck_rfid_code'].'",
                  "vCustomerName": "'.$input['customer_name'].'",
                  "vAddress": "'.$input['customer_address'].'",
                  "vCustomerId": "'.$input['cdm_customer_id'].'",
                  "vKend": "'.$input['truck_type'].'",
                  "vTgl": "'.$input['date'].'",
                  "vTerminalCode": "201"
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
    return [json_decode($res->getBody()->getContents())];
  }

  public static function updateTid($input){
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/updateTid";

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
                  "truckId": "'.$input['truck_plat_no'].'",
                  "truckNumber": "'.$input['truck_plat_no'].'",
                  "rfidCode": "'.$input['truck_rfid_code'].'",
                  "customerName": "'.$input['customer_name'].'",
                  "address": "'.$input['customer_address'].'",
                  "customerId": "'.$input['cdm_customer_id'].'",
                  "kend": "'.$input['truck_type'].'",
                  "tgl": "'.$input['date'].'",
                  "idTerminal": "201"
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
    return [json_decode($res->getBody()->getContents())];
  }

  public static function createTCA($input, $tca_id){
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createTCA";

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
                  "vIdTruck": "",
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
    return ["Success"=>true, "result" => $res['esbBody']['statusMessage']];
  }

  public static function sendNotaProforma($nota_id){
    $find = TxHdrNota::find($nota_id);
    $detil = DB::connection('omcargo')->table('TX_DTL_NOTA')->where('nota_hdr_id', $nota_id)->get();

    $head_json = '{
       "billerRequestId":"'.$find->nota_id.'",
       "orgId":"'.$find->nota_org_id.'",
       "trxNumber":"'.$find->nota_no.'",
       "trxNumberOrig":"",
       "trxNumberPrev":"",
       "trxTaxNumber":"",
       "trxDate":"'.date('Y-m-d', strtotime($find->nota_date)).' 00:00:00",
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
       "terminal":"-",
       "vesselName":"'.$find->nota_vessel_name.'",
       "branchCode":"'.$find->nota_branch_code.'",
       "errorMessage":"",
       "apiMessage":"",
       "createdBy":"-1",
       "creationDate":"'.date('Y-m-d', strtotime($find->nota_date)).' 00:00:00",
       "lastUpdatedBy":"-1",
       "lastUpdateDate":"'.date('Y-m-d', strtotime($find->nota_date)).' 00:00:00",
       "lastUpdateLogin":"-1",
       "customerTrxIdOut":null,
       "processFlag":"",
       "attribute1":"BRG10",
       "attribute2":"'.$find->nota_cust_id.'",
       "attribute3":"'.$find->nota_cust_name.'",
       "attribute4":"'.$find->nota_cust_address.'",
       "attribute5":"'.$find->nota_cust_npwp.'",
       "attribute6":"LAUTAN LUAS PBM",
       "attribute7":"BONGKAR MUAT",
       "attribute8":"",
       "attribute9":"",
       "attribute10":"",
       "attribute11":"",
       "attribute12":"",
       "attribute13":"",
       "attribute14":"FAKTUR-0002",
       "attribute15":"",
       "interfaceHeaderAttribute1":"UPER-UPER-BTN-194160",
       "interfaceHeaderAttribute2":"'.$find->nota_vessel_name.'",
       "interfaceHeaderAttribute3":"MUAT/EXPORT",
       "interfaceHeaderAttribute4":"GD01",
       "interfaceHeaderAttribute5":"11/12",
       "interfaceHeaderAttribute6":"",
       "interfaceHeaderAttribute7":"5678",
       "interfaceHeaderAttribute8":"",
       "interfaceHeaderAttribute9":"DIM01A/DIM01B",
       "interfaceHeaderAttribute10":"2019-11-14 08:00:00",
       "interfaceHeaderAttribute11":"",
       "interfaceHeaderAttribute12":"",
       "interfaceHeaderAttribute13":"",
       "interfaceHeaderAttribute14":"",
       "interfaceHeaderAttribute15":"",
       "customerAddress":"'.$find->nota_cust_address.'",
       "customerName":"'.$find->nota_cust_name.'",
       "sourceSystem":"NPKBILLING",
       "arStatus":"N",
       "sourceInvoice":"NPKBILLING",
       "arMessage":"",
       "customerNPWP":"'.$find->nota_cust_npwp.'",
       "perKunjunganFrom":null,
       "perKunjunganTo":null,
       "jenisPerdagangan":"",
       "docNum":"",
       "statusLunas":"",
       "tglPelunasan":"",
       "amountTerbilang":"",
       "ppnDipungutSendiri":"",
       "ppnDipungutPemungut":"",
       "ppnTidakDipungut":"",
       "ppnDibebaskan":"",
       "uangJaminan":"",
       "piutang":"",
       "sourceInvoiceType":"NPKBILLING",
       "branchAccount":"91",
       "statusCetak":"",
       "statusKirimEmail":"",
       "amountDasarPenghasilan":"'.$find->nota_amount.'",
       "amountMaterai":null,
       "ppn10Persen":"'.$find->nota_ppn.'",
       "statusKoreksi":"",
       "tanggalKoreksi":null,
       "keteranganKoreksi":""
    }';

    $lines_json = '';
    foreach ($detil as $list) {
      $lines_json  .= '{
          "billerRequestId":"'.$find->nota_id.'",
          "trxNumber":"'.$find->nota_no.'",
          "lineId":null,
          "lineNumber":"'.$list->dtl_line.'",
          "description":"'.$list->dtl_service_type.'",
          "memoLineId":null,
          "glRevId":null,
          "lineContext":"",
          "taxFlag":"Y",
          "serviceType":"'.$list->dtl_service_type.'",
          "eamCode":"BRG10",
          "locationTerminal":"",
          "amount":"'.$list->dtl_amount.'",
          "taxAmount":"'.$list->dtl_ppn.'",
          "startDate":"'.date('Y-m-d', strtotime($find->dtl_create_date)).'",
          "endDate":"'.date('Y-m-d', strtotime($find->dtl_create_date)).'",
          "createdBy":"-1",
          "creationDate":"'.date('Y-m-d', strtotime($find->dtl_create_date)).'",
          "lastUpdatedBy":"-1",
          "lastUpdatedDate":"'.date('Y-m-d', strtotime($find->dtl_create_date)).'",
          "interfaceLineAttribute1":"BRG10",
          "interfaceLineAttribute2":"BONGKAT MUAT",
          "interfaceLineAttribute3":null,
          "interfaceLineAttribute4":null,
          "interfaceLineAttribute5":"",
          "interfaceLineAttribute6":"",
          "interfaceLineAttribute7":"",
          "interfaceLineAttribute8":"",
          "interfaceLineAttribute9":"",
          "interfaceLineAttribute10":"",
          "interfaceLineAttribute11":"",
          "interfaceLineAttribute12":null,
          "interfaceLineAttribute13":"800",
          "interfaceLineAttribute14":"'.$list->dtl_unit_name.'",
          "interfaceLineAttribute15":"",
          "lineDoc":""
      },';
    }
    $lines_json = substr($lines_json, 0,-1);

    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putInvoice";
    $string_json = '{
     "arRequestDoc":{
        "esbHeader":{
           "internalId":"",
           "externalId":"EDI-2910201921570203666",
           "timestamp":"2019-10-29 21:57:020.36665400",
           "responseTimestamp":"",
           "responseCode":"",
           "responseMessage":""
        },
        "esbBody":[
          { "header": '.$head_json.', "lines": ['.$lines_json.'] }
        ],
        "esbSecurity":{
           "orgId":"'.$find->nota_org_id.'",
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

    return $res = json_decode($res->getBody()->getContents(), true);
  }
}
