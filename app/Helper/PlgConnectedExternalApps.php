<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Illuminate\Support\Facades\DB;
use App\Models\OmUster\TxHdrNota;

class PlgConnectedExternalApps{
	// PLG
	    public static function sendRequestBookingPLG($arr){
	        $endpoint_url=config('endpoint.tosPostPLG');
	        $toFunct = 'buildJson'.$arr['config']['head_table'];
	        $getJson = static::$toFunct($arr);

	        $username="npks";
	        $password ="npks123";
	        $client = new Client();
	        $options= array(
	          'auth' => [
	            $username,
	            $password
	          ],
	          'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json'],
	          'body' => $getJson,
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
	        return ['request' => $options, 'response' => $res];
		}

	    private static function buildJsonTX_HDR_REC($arr){
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
	          $arrdetil .= '{
	            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "REQ_DTL_VIA": "'.$dtl[$arr['config']['DTL_VIA_NAME']].'",
	            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
	            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
	            "REQ_DTL_OWNER_CODE": "'.$dtl[$arr['config']['DTL_OWNER']].'",
	            "REQ_DTL_OWNER_NAME": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'"
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
	        $head = (array)$head;
	        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$arr['config']['head_no']])->first();
	        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
	          'reff_tr_id' => 5,
	          'reff_id' => $head[$arr['config']['head_from']]
	        ])->first();
	        return $json_body = '{
	          "action" : "getReceiving",
	          "header": {
	            "REQ_NO": "'.$head[$arr['config']['head_no']].'",
	            "REQ_RECEIVING_DATE": "'.date('m/d/Y', strtotime($head[$arr['config']['head_date']])).'",
	            "NO_NOTA": "'.$nota->nota_no.'",
	            "TGL_NOTA": "'.date('m/d/Y', strtotime($nota->nota_date)).'",
	            "NM_CONSIGNEE": "'.$head[$arr['config']['head_cust_name']].'",
	            "ALAMAT": "'.$head[$arr['config']['head_cust_addr']].'",
	            "REQ_MARK": "",
	            "NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
	            "RECEIVING_DARI": "'.$rec_dr->reff_name.'",
	            "TANGGAL_LUNAS": "'.date('m/d/Y', strtotime($nota->nota_paid_date)).'",
	            "DI": ""
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_DEL($arr){
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
	          $arrdetil .= '{
	            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "REQ_DTL_VIA": "'.$dtl[$arr['config']['DTL_VIA_NAME']].'",
	            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
	            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
	            "REQ_DTL_DEL_DATE": "",
	            "REQ_DTL_NO_SEAL": ""
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
	        $head = (array)$head;
	        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$config['head_no']])->first();
	        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
	          'reff_tr_id' => 5,
	          'reff_id' => $head[$config['head_from']]
	        ])->first();
	        return $json_body = '{
	          "action" : "getDelivery",
	          "header": {
	            "REQ_NO": "'.$head[$config['head_no']].'",
	            "REQ_DELIVERY_DATE": "'.$head[$config['head_date']].'",
	            "NO_NOTA": "'.$nota->nota_no.'",
	            "TGL_NOTA": "'.$nota->nota_date.'",
	            "NM_CONSIGNEE": "'.$head[$config['head_cust_name']].'",
	            "ALAMAT": "'.$head[$config['head_cust_addr']].'",
	            "REQ_MARK": "",
	            "NPWP": '.$head[$config['head_cust_npwp']].'",
	            "DELIVERY_KE": "",
	            "TANGGAL_LUNAS": "'.$nota->nota_paid_date.'",
	            "PERP_DARI": "",
	            "PERP_KE": ""
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		public static function sendInvProforma($arr){
			// liat di concet external function sendNotaProforma
			return ['Success' => true, 'response' => 'by passs'];
		}

		public static function cekSendInvProforma($input){
			// buat funct cek status di simkue kalau behasil update status nota supaya bisa di bayarkan
			// ini semetara di by passs
			$getNota = TxHdrNota::find($input['nota_id']);
			$getNota->nota_status = 3;
			$getNota->save();
			return ['result' => 'Success, by pass'];
        	// ini semetara di by passs
		}

		public static function sendInvPay($arr){
			// liat di concet external function sendNotaProforma
			return ['Success' => true, 'response' => 'by passs'];
		}

		public static function cekSendInvPay($input){
			// buat funct cek status di simkue kalau behasil update status nota supaya bisa telah dibayarkan lalau jalankan funct sendRequestBookingPLG

			// sementara di by pass
			$getNota = TxHdrNota::find($input['nota_id']);
			$getNota->nota_status = 5;
			$getNota->nota_paid = 'Y';
			$getNota->save();
        	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $getNota->nota_group_id)->first();
			$config = json_decode($config->api_set, true);
			$getReq = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$getNota->nota_req_no)->first();
			$getReq = (array)$getReq;
			if ($getReq[$config['head_paymethod']] == 2) {
				$sendRequestBooking = static::sendRequestBookingPLG(['id' => $getReq[$config['head_primery']] ,'config' => $config]);
			}
	    	// sementara di by pass

	    	return ['result' => 'Success, by pass', 'sendRequestBooking' => $sendRequestBooking];
		}


	// PLG
}