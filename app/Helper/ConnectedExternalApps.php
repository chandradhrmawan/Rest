<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\Models\OmCargo\TxHdrUper;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrDel;
use App\Models\OmCargo\TxHdrRec;
use App\Helper\RequestBooking;

class ConnectedExternalApps{

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
    $ckp = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id', $req->bm_id)->where('dtl_pkg_id', 4)->get();

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
      $req_type == 'BM';
    }else if($req_type == 'REC') {
      $header = TxHdrRec::where('rec_no',$input['req_no'])->first();
      $table = 'TX_HDR_REC';
      if ($header->rec_extend_status == 'Y') {
        $req_type = 'EXT';
      }
    }else if($req_type == 'DEL') {
      $header = TxHdrDel::where('del_no',$input['req_no'])->first();
      $table = 'TX_HDR_DEL';
      if ($header->del_extend_status == 'Y') {
        $req_type = 'EXT';
      }
    }

    $config = RequestBooking::config($table);
    if (!empty($header)) {
      $detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'],$header[$config['head_primery']])->where('dtl_pkg_id', 4)->get();
      return static::sendRequestBookingNew($req_type, $input['paid_date'] $header, $detil, $config);
    }
  }


  private static function sendRequestBookingNewExcute($req_type, $paid_date, $head, $detil, $config){
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createBookingHeader";
    foreach ($detil as $list) {
      $listA = (array)$list;
      
      $vparam = '';
      $vparam .= $listA[$config['head_tab_detil_bl']];
      $vparam .= '^'.$list->dtl_cmdty_name;
      $vparam .= '^'.$head[$config['head_vvd_id']];
      $vparam .= '^'.$req_type; // IF_FLAG
      $vparam .= '^'.$listA[$config['head_tab_detil_id']]; // ID_CARGO
      $vparam .= '^'.$list->dtl_pkg_name; // PKG_NAME
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
      $vparam .= '^'.$listA[$config['head_tab_detil_tl']]; // TL_FLAG
      $vparam .= '^'.$req_type; // IF_FLAG
      if ($list->dtl_character_id == 1) { $vparam .= '^N'; }else{ $vparam .= '^Y'; } // HZ
      $vparam .= '^'; // OI
      $vparam .= '^'; // HS_CODE
      $vparam .= '^'; // CARGO_ID
      if ($list->dtl_character_id == 1) { $vparam .= '^Y'; }else{ $vparam .= '^N'; } // DS
      $vparam .= '^'; // EI
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
      $vparam .= '^'; // id_Port

      $string_json = '{
          "createBookingDetailInterfaceRequest": {
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
    }
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
                   "attribute14":"'.$uperH->uper_nota_id.'",
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

  public static function sendNotaProforma($input){
    // buat funct send proforma nota ke invoice
  }
}
