<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\OmCargo\TxHdrBm;
use Illuminate\Support\Facades\DB;
use App\Models\OmCargo\TxHdrUper;

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
    if ($count > 0) {
      return ['result' => "Fail, realisation has been created!", "Success" => false];
    }
    $req = TxHdrBm::where('BM_NO', $input['req_no'])->first();
    if (empty($req)) {
      return ['result' => "Fail, request not found!", "Success" => false];
    }
    $ckp = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id', $req->bm_id)->where('dtl_pkg_id', 4)->get();

    if (count($ckp) > 0) {
      foreach ($ckp as $list) {
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
          $newreal = $response['esbBody']['results'][0];
          DB::connection('omcargo')->table('TX_REAL_TOS')->insert([
             'idvsb'=> $newreal['idVsbVoyage'],
             'bl_no'=> $newreal['blNumber'],
             'package'=> $newreal['packageName'],
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
    }

    return [
      'req_header' => $req,
      'req_detil' => DB::connection('omcargo')->select(DB::raw("select * from TX_DTL_BM A left join TX_REAL_TOS B on B.BL_NO = A.DTL_BM_BL where A.HDR_BM_ID = ".$req->bm_id)),
      'result' => "Success, get data real from tos!"
    ];
	}

  public static function sendRequestBooking($input){
    $header = TxHdrBm::where('bm_no',$input)->first();
    if (!empty($header)) {
      $detil = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id',$header->bm_id)->get();
      return static::sendRealBM($header, $detil);
    }
  }

  private static function sendRealBM($head, $detil){
    foreach ($detil as $list) {
      if ($list->dtl_pkg_id == 4) {
        // 
          $consignee = '';
          $oi = '';
          $podpol = '';
          $movetype = '';
          $startenddate = '';
          $blno = $list->dtl_bm_bl;
          $bldate = $list->dtl_create_date;
        // 

        // 
          if (empty($head->bm_eta)) {
            $bm_eta = null;
          }else{
            $bm_eta = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head->bm_eta)->format('m/d/Y');
          }
          if (empty($head->bm_etd)) {
            $bm_etd = null;
          }else{
            $bm_etd = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head->bm_etd)->format('m/d/Y');
          }
          if (empty($head->bm_open_stack)) {
            $bm_open_stack = null;
          }else{
            $bm_open_stack = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head->bm_open_stack)->format('m/d/Y');
          }
          if (empty($head->bm_closing_time)) {
            $bm_closing_time = null;
          }else{
            $bm_closing_time = \Carbon\Carbon::createFromFormat("Y-m-d H:i:s", $head->bm_closing_time)->format('m/d/Y');
          }
        // 

        // 
          $vParam = '';
          $vParam .= $head->bm_no.'^';
          $vParam .= $head->bm_cust_name.'^';
          $vParam .= $head->bm_cust_id.'^';
          $vParam .= $head->bm_cust_npwp.'^';
          $vParam .= $head->bm_vessel_name.'^';
          $vParam .= $bm_eta.'^';
          $vParam .= $bm_etd.'^';
          $vParam .= $head->bm_voyin.'^';
          $vParam .= $head->bm_voyout.'^';
          $vParam .= $bm_closing_time.'^';
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
          $vParam .= $head->bm_vvd_id.'^'; // ?
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
                "vId": "'.$head->bm_id.'",
                "vReqNo": "'.$head->bm_no.'",
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
                  "vId": "'.$list->dtl_bm_id.'",
                  "vIdHeader": "'.$head->bm_id.'"
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
    }
    return ['Success' => true];
  }

  public static function sendUperPutReceipt($uper_id, $pay){
    $uperH = TxHdrUper::find($uper_id);
    $branchCode = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$pay->pay_branch_id)->get();
    $branch = $branchCode[0];
    // $branchCode = $branchCode->branch_code;

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
                   "receiptMethod":"BANK",
                   "receiptAccount":"'.$pay->pay_dest_account_name.' '.$pay->pay_dest_bank_code.' '.$pay->pay_dest_account_no.'",
                   "bankId":"",
                   "customerNumber":"'.$pay->pay_cust_id.'",
                   "receiptDate":"'.$pay->pay_date.'",
                   "currencyCode":"'.$pay->pay_currency.'",
                   "status":"P",
                   "amount":"'.$pay->pay_amount.'",
                   "processFlag":"",
                   "errorMessage":"",
                   "apiMessage":"",
                   "attributeCategory":"",
                   "referenceNum":"",
                   "receiptType":"",
                   "receiptSubType":"",
                   "createdBy":"'.$pay->pay_create_by.'",
                   "creationDate":"'.$pay->pay_create_date.'",
                   "terminal":"",
                   "attribute1":"",
                   "attribute2":"",
                   "attribute3":"",
                   "attribute4":"",
                   "attribute5":"",
                   "attribute6":"",
                   "attribute7":"",
                   "attribute8":"",
                   "attribute9":"",
                   "attribute10":"",
                   "attribute11":"",
                   "attribute12":"",
                   "attribute13":"",
                   "attribute14":"",
                   "attribute15":"",
                   "statusReceipt":"N",
                   "sourceInvoice":"NPKBILLING",
                   "statusReceiptMsg":"",
                   "invoiceNum":"",
                   "amountOrig":null,
                   "lastUpdateDate":"'.$pay->pay_create_date.'",
                   "lastUpdateBy":"'.$pay->pay_create_by.'",
                   "branchCode":"'.$branch->branch_code.'",
                   "branchAccount":"'.$branch->branch_account.'",
                   "sourceInvoiceType":"NPKBILLING",
                   "remarkToBankId":"'.$pay->pay_dest_account_no.'",
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

  public static function createTCA($input){
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
                  "vIdTruck": "'.$list['vIdTruck'].'",
                  "vIdVvd": "'.$list['vIdVvd'].'",
                  "vIdTerminal": "'.$list['vIdTerminal'].'"
                },';
    }
    $detail = substr($detail, 0, -1);

    $string_json = '{
     "createTCARequest": {
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
         "document": [],
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
    return ["Success"=>true, "result" => json_decode($res->getBody()->getContents(), true)];
  }

  public static function sendNotaProforma($input){
    // buat funct send proforma nota ke invoice
  }
}
