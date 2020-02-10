<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\OmUster\TxHdrNota;
use App\Helper\PlgRequestBooking;

class PlgConnectedExternalApps{
	// PLG
		public static function sendRequestToExtJsonMet($arr){
	        $client = new Client();
	        $options= array(
	          'auth' => [
	            $arr['user'],
	            $arr['pass']
	          ],
	          'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json'],
	          'body' => $arr['json'],
	          "debug" => false
	        );
			try {
	          $res = $client->post($arr['target'], $options);
	        } catch (ClientException $e) {
	          $error = $e->getRequest() . "\n";
	          if ($e->hasResponse()) {
	            $error .= $e->getResponse() . "\n";
	          }
	          return ["Success"=>false, "request" => $options, "response" => $error];
	        }
	        $res = json_decode($res->getBody()->getContents(), true);
	        return ["Success"=>true, "request" => $arr, "response" => $res];
		}

		private static function decodeResultAftrSendToTosNPKS($res, $type){
			// return $res;
			$res['request']['json'] = json_decode($res['request']['json'], true);
			$res['request']['json'][$type.'Request']['esbBody']['request'] = json_decode(base64_decode($res['request']['json'][$type.'Request']['esbBody']['request']),true);
	        $res['response'][$type.'Response']['esbBody']['result'] = json_decode($res['response'][$type.'Response']['esbBody']['result'],true);
	        $res['response'][$type.'Response']['esbBody']['result']['result'] = json_decode(base64_decode($res['response'][$type.'Response']['esbBody']['result']['result']),true);
	        $res['result'] = $res['response'][$type.'Response']['esbBody']['result']['result'];
	        return $res;
		}

		public static function getVesselNpks($input){
			$json = '
			{
				"getVesselNpksRequest": {
					"esbHeader": {
						"internalId": "",
			        	"externalId": "",
			        	"timestamp": "",
			        	"responseTimestamp": "",
			        	"responseCode": "",
			        	"responseMessage": ""
						},
						"esbBody":   {
							"vessel":"'.$input['query'].'"
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
			$json = json_encode(json_decode($json,true));
			$res = static::sendRequestToExtJsonMet([
	        	"user" => config('endpoint.esbGetVesselNpks.user'),
	        	"pass" => config('endpoint.esbGetVesselNpks.pass'),
	        	"target" => config('endpoint.esbGetVesselNpks.target'),
	        	"json" => $json
	        ]);
			$vesel = $res['response']['getVesselNpksResponse']['esbBody']['results'];

			$result = [];
			foreach ($vesel as $query) {
				$query = (object)$query;
				$result[] = [
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
			}
			return ["result"=>$result, "count"=>count($result)];
		}

	    public static function sendRequestBookingPLG($arr){
	    	if (!in_array($arr['config']['head_table'], ['TX_HDR_REC','TX_HDR_DEL'])) {
	    		$res = [
	    			'Success' => false,
	    			'note' => 'function bulid json send request, not available!'
	    		];
	    	}else{
		        $toFunct = 'buildJson'.$arr['config']['head_table'];
		        $json = static::$toFunct($arr);
		        $json = base64_encode(json_encode(json_decode($json,true)));
		        $json = '
					{
					    "repoPostRequest": {
					        "esbHeader": {
					            "internalId": "",
					            "externalId": "",
					            "timestamp": "",
					            "responseTimestamp": "",
					            "responseCode": "",
					            "responseMessage": ""
					        },
					        "esbBody": {
					            "request": "'.$json.'"
					        },
					        "esbSecurity": {
					            "orgId": "",
					            "batchSourceId": "",
					            "lastUpdateLogin": "",
					            "userId": "",
					            "respId": "",
					            "ledgerId": "",
					            "respAppId": "",
					            "batchSourceName": ""
					        }
					    }
					}
		        ';
		        $res = static::sendRequestToExtJsonMet([
		        	"user" => config('endpoint.tosPostPLG.user'),
		        	"pass" => config('endpoint.tosPostPLG.pass'),
		        	"target" => config('endpoint.tosPostPLG.target'),
		        	"json" => json_encode(json_decode($json,true))
		        ]);
		        $res = static::decodeResultAftrSendToTosNPKS($res, 'repoPost');
	    	}
	        return ['sendRequestBookingPLG' => $res];
		}

		// store request data to tos
		    private static function buildJsonTX_HDR_REC($arr){
		        $arrdetil = '';
		        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
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
		        $nota_no = null;
		        $nota_date = null;
		        $nota_paid_date = null;
		        if (!empty($nota)) {
		        	$nota_no = $nota->nota_no;
		        	$nota_date = date('m/d/Y', strtotime($nota->nota_date));
		        	$nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
		        }
		        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
		          'reff_tr_id' => 5,
		          'reff_id' => $head[$arr['config']['head_from']]
		        ])->first();
		        return $json_body = '{
		          "action" : "getReceiving",
		          "header": {
		            "REQ_NO": "'.$head[$arr['config']['head_no']].'",
		            "REQ_RECEIVING_DATE": "'.date('m/d/Y', strtotime($head[$arr['config']['head_date']])).'",
		            "NO_NOTA": "'.$nota_no.'",
		            "TGL_NOTA": "'.$nota_date.'",
		            "NM_CONSIGNEE": "'.$head[$arr['config']['head_cust_name']].'",
		            "ALAMAT": "'.$head[$arr['config']['head_cust_addr']].'",
		            "REQ_MARK": "",
		            "NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
		            "RECEIVING_DARI": "'.$rec_dr->reff_name.'",
		            "TANGGAL_LUNAS": "'.$nota_paid_date.'",
		            "DI": "",
		            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
		          },
		          "arrdetail": ['.$arrdetil.']
		        }';
			}

			private static function buildJsonTX_HDR_DEL($arr){
		        $arrdetil = '';
		        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
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
						$nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$arr['config']['head_no']])->first();
			      $nota_no = null;
			      $nota_date = null;
			      $nota_paid_date = null;
			      if (!empty($nota)) {
			        $nota_no = $nota->nota_no;
			        $nota_date = date('m/d/Y', strtotime($nota->nota_date));
			        $nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
			      }
		        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
		          'reff_tr_id' => 5,
		          'reff_id' => $head[$arr['config']['head_from']]
		        ])->first();

						$delivery_date = date("m/d/Y", strtotime($head[$arr['config']['head_date']]));

		        return $json_body = '{
		          "action" : "getDelivery",
		          "header": {
		            "REQ_NO": "'.$head[$arr['config']['head_no']].'",
		            "REQ_DELIVERY_DATE": "'.$delivery_date.'",
		            "NO_NOTA": "'.$nota_no.'",
		            "TGL_NOTA": "'.$nota_date.'",
		            "NM_CONSIGNEE": "'.$head[$arr['config']['head_cust_name']].'",
		            "ALAMAT": "'.$head[$arr['config']['head_cust_addr']].'",
		            "REQ_MARK": "",
		            "NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
		            "DELIVERY_KE": "",
		            "TANGGAL_LUNAS": "'.$nota_paid_date.'",
		            "PERP_DARI": "",
		            "PERP_KE": "",
								"BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
		          },
		          "arrdetail": ['.$arrdetil.']
		        }';
			}

			private static function buildJsonTX_HDR_STUFF($arr){
		        $arrdetil = '';
		        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
		        foreach ($dtls as $dtl) {
		          $dtl = (array)$dtl;
		          $arrdetil .= '{
		            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
		            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
		            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
		            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
		            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
		            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
		            "REQ_DTL_REMARK_SP2": "",
		            "REQ_DTL_ORIGIN": "'.$dtl[$arr['config']['DTL_CONT_FROM']].'",
		            "TGL_MULAI": "'.date('d/m/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_START_DATE']])).'",
		            "TGL_SELESAI": "'.date('d/m/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_END_DATE']])).'"
		          },';
		        }
		        $arrdetil = substr($arrdetil, 0,-1);
		        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
		        $head = (array)$head;
		        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$arr['config']['head_no']])->first();
		        $nota_no = null;
		        $nota_date = null;
		        $nota_paid_date = null;
		        if (!empty($nota)) {
		        	$nota_no = $nota->nota_no;
		        	$nota_date = date('m/d/Y', strtotime($nota->nota_date));
		        	$nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
		        }
		        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
		          'reff_tr_id' => 5,
		          'reff_id' => $head[$arr['config']['head_from']]
		        ])->first();
		        return $json_body = '{
		          "action" : "getStuffing",
		          "header": {
		            "REQ_NO": "'.$head[$arr['config']['head_no']].'",
		            "REQ_STUFF_DATE": "'.date('m/d/Y', strtotime($head[$arr['config']['head_date']])).'",
		            "NO_NOTA": "'.$nota_no.'",
		            "TGL_NOTA": "'.$nota_date.'",
		            "NM_CONSIGNEE": "'.$head[$arr['config']['head_cust_name']].'",
		            "ALAMAT": "'.$head[$arr['config']['head_cust_addr']].'",
		            "REQ_MARK": "",
		            "NO_UKK": "'.$head[$arr['config']['head_vvd']].'",
		            "NO_BOOKING": "",
		            "NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
		            "TANGGAL_LUNAS": "'.$nota_paid_date.'",
		            "NO_REQUEST_RECEIVING": "'.$head[$arr['config']['head_rec_no']].'",
		            "STUFFING_DARI": "'.$rec_dr->reff_name.'",
		            "PERP_DARI": "'.$head[$arr['config']['head_ext_from']].'",
		            "PERP_KE": "'.$head[$arr['config']['head_ext_loop']].'",
		            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
		          },
		          "arrdetail": ['.$arrdetil.']
		        }';
			}

			private static function buildJsonTX_HDR_STRIPP($arr){
		        $arrdetil = '';
		        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
		        foreach ($dtls as $dtl) {
		          $dtl = (array)$dtl;
		          $arrdetil .= '{
		            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
		            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
		            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
		            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
		            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
		            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
		            "REQ_DTL_ORIGIN": "'.$dtl[$arr['config']['DTL_CONT_FROM']].'",
		            "TGL_MULAI": "'.date('d/m/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_START_DATE']])).'",
		            "TGL_SELESAI": "'.date('d/m/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_END_DATE']])).'"
		          },';
		        }
		        $arrdetil = substr($arrdetil, 0,-1);
		        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
		        $head = (array)$head;
		        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$arr['config']['head_no']])->first();
		        $nota_no = null;
		        $nota_date = null;
		        $nota_paid_date = null;
		        if (!empty($nota)) {
		        	$nota_no = $nota->nota_no;
		        	$nota_date = date('m/d/Y', strtotime($nota->nota_date));
		        	$nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
		        }
		        $rec_dr = DB::connection('omuster')->table('TM_REFF')->where([
		          'reff_tr_id' => 5,
		          'reff_id' => $head[$arr['config']['head_from']]
		        ])->first();
		        return $json_body = '{
		          "action" : "getStripping",
		          "header": {
		            "REQ_NO": "'.$head[$arr['config']['head_no']].'",
		            "REQ_STRIP_DATE": "'.date('m/d/Y', strtotime($head[$arr['config']['head_date']])).'",
		            "NO_NOTA": "'.$nota_no.'",
		            "TGL_NOTA": "'.$nota_date.'",
		            "NM_CONSIGNEE": "'.$head[$arr['config']['head_cust_name']].'",
		            "ALAMAT": "'.$head[$arr['config']['head_cust_addr']].'",
		            "REQ_MARK": "",
		            "NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
		            "DO": "'.$head[$arr['config']['head_do']].'",
		            "BL": "'.$head[$arr['config']['head_bl']].'",
		            "NO_REQUEST_RECEIVING": "'.$head[$arr['config']['head_rec_no']].'",
		            "TANGGAL_LUNAS": "'.$nota_paid_date.'",
		            "STRIP_DARI": "'.$rec_dr->reff_name.'",
		            "PERP_DARI": "'.$head[$arr['config']['head_ext_from']].'",
		            "PERP_KE": "'.$head[$arr['config']['head_ext_loop']].'",
		            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
		          },
		          "arrdetail": ['.$arrdetil.']
		        }';
			}
		// store request data to tos

		public static function sendInvProforma($arr){
			return ['Success' => true, 'sendInvProforma' => 'by pass dulu']; // by pass dulu
			$branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$arr['nota']['nota_branch_id'])->where('branch_code',$arr['nota']['nota_branch_code'])->get();
			if (count($branch) == 0) {
				return ['Success' =>false, 'response' => 'branch not found!'];
			}
			$branch = (array)$branch[0];
			$nota_date = $arr['nota']['nota_date'];
			$nota_date_noHour = date('Y-m-d', strtotime($arr['nota']['nota_date']));

			$lines = '';
			if ($arr['nota']['nota_group_id'] == 1) { // rec
				$getNotaDtl = DB::connection('omuster')->table('TX_DTL_NOTA')->where('nota_hdr_id',$arr['nota']['nota_id'])->get();
				foreach ($getNotaDtl as $list) {
					$lines .= '
					{
						"billerRequestId": "'.$arr['nota']['nota_req_no'].'",
						"trxNumber": "'.$arr['nota']['nota_no'].'",
						"lineId": null,
						"lineNumber": "'.$list->dtl_line.'",
						"description": "'.$list->dtl_service_type.'",
						"memoLineId": null,
						"glRevId": null,
						"lineContext": "",
						"taxFlag": "Y",
						"serviceType": "'.$list->dtl_line_desc.'",
						"eamCode": "",
						"locationTerminal": "",
						"amount": "'.$list->dtl_dpp.'",
						"taxAmount": "'.$list->dtl_ppn.'",
						"startDate": "'.$nota_date_noHour.'",
						"endDate": "'.$nota_date_noHour.'",
						"createdBy": "-1",
						"creationDate": "'.$nota_date_noHour.'",
						"lastUpdatedBy": "-1",
						"lastUpdatedDate": "'.$nota_date_noHour.'",
						"interfaceLineAttribute1": "",
						"interfaceLineAttribute2": "'.$list->dtl_service_type.'",
						"interfaceLineAttribute3": "",
						"interfaceLineAttribute4": "",
						"interfaceLineAttribute5": "",
						"interfaceLineAttribute6": "",
						"interfaceLineAttribute7": "",
						"interfaceLineAttribute8": "",
						"interfaceLineAttribute9": "",
						"interfaceLineAttribute10": "",
						"interfaceLineAttribute11": "",
						"interfaceLineAttribute12": "",
						"interfaceLineAttribute13": "'.$list->dtl_qty.'",
						"interfaceLineAttribute14": "'.$list->dtl_unit_name.'",
						"interfaceLineAttribute15": "",
						"lineDoc": ""
					},';
				}
			}
	        $lines = substr($lines, 0,-1);

			$json = '
			{
			    "arRequestDoc": {
			        "esbHeader": {
			        	"internalId": "",
			        	"externalId": "",
			        	"timestamp": "",
			        	"responseTimestamp": "",
			        	"responseCode": "",
			        	"responseMessage": ""
			        },
			        "esbBody": [
			            {
			                "header": {
			                	"billerRequestId":"'.$arr['nota']['nota_req_no'].'",
			                	"orgId":"'.$branch['branch_org_id'].'",
			                	"trxNumber":"'.$arr['nota']['nota_no'].'",
			                	"trxNumberOrig":"",
			                	"trxNumberPrev":"",
			                	"trxTaxNumber":"",
			                	"trxDate":"'.$nota_date.'",
			                	"trxClass":"INV",
			                	"trxTypeId":"-1",
			                	"paymentReferenceNumber":"",
			                	"referenceNumber":"",
			                	"currencyCode":"'.$arr['nota']['nota_currency_code'].'",
			                    "currencyType": "",
			                    "currencyRate": "0",
			                    "currencyDate": null,
			                    "amount": "'.$arr['nota']['nota_amount'].'",
			                    "customerNumber": "'.$arr['nota']['nota_cust_id'].'",
			                    "customerClass": "",
			                    "billToCustomerId": "-1",
			                    "billToSiteUseId": "-1",
			                    "termId": null,
			                    "status": "P",
			                    "headerContext": "'.$arr['nota']['nota_context'].'",
			                    "headerSubContext": "'.$arr['nota']['nota_sub_context'].'",
			                    "startDate": null,
			                    "endDate": null,
			                    "terminal": "-",
			                    "vesselName": "'.$arr['nota']['nota_vessel_name'].'",
			                    "branchCode": "'.$arr['nota']['nota_branch_code'].'",
			                    "errorMessage": "",
			                    "apiMessage": "",
			                    "createdBy": "-1",
			                    "creationDate": "'.$nota_date.'",
			                    "lastUpdatedBy": "-1",
			                    "lastUpdateDate": "'.$nota_date.'",
			                    "lastUpdateLogin": "-1",
			                    "customerTrxIdOut": null,
			                    "processFlag": "",
			                    "attribute1": "'.$arr['nota']['nota_sub_context'].'",
			                    "attribute2": "'.$arr['nota']['nota_cust_id'].'",
			                    "attribute3": "'.$arr['nota']['nota_cust_name'].'",
			                    "attribute4": "'.$arr['nota']['nota_cust_address'].'",
			                    "attribute5": "'.$arr['nota']['nota_cust_npwp'].'",
			                    "attribute6": "",
			                    "attribute7": "",
			                    "attribute8": "",
			                    "attribute9": "",
			                    "attribute10": "",
			                    "attribute11": "",
			                    "attribute12": "",
			                    "attribute13": "",
			                    "attribute14": "'.$arr['nota']['nota_no'].'",
			                    "attribute15": "",
			                    "interfaceHeaderAttribute1": "",
			                    "interfaceHeaderAttribute2": "'.$arr['nota']['nota_vessel_name'].'",
			                    "interfaceHeaderAttribute3": "",
			                    "interfaceHeaderAttribute4": "",
			                    "interfaceHeaderAttribute5": "",
			                    "interfaceHeaderAttribute6": "",
			                    "interfaceHeaderAttribute7": "",
			                    "interfaceHeaderAttribute8": "",
			                    "interfaceHeaderAttribute9": "",
			                    "interfaceHeaderAttribute10": "",
			                    "interfaceHeaderAttribute11": "",
			                    "interfaceHeaderAttribute12": "",
			                    "interfaceHeaderAttribute13": "",
			                    "interfaceHeaderAttribute14": "",
			                    "interfaceHeaderAttribute15": "",
			                    "customerAddress": "'.$arr['nota']['nota_cust_address'].'",
			                    "customerName": "'.$arr['nota']['nota_cust_name'].'",
			                    "sourceSystem": "NPKSBILLING",
			                    "arStatus": "N",
			                    "sourceInvoice": "'.$arr['nota']['nota_context'].'",
			                    "arMessage": "",
			                    "customerNPWP": "'.$arr['nota']['nota_cust_npwp'].'",
			                    "perKunjunganFrom": null,
			                    "perKunjunganTo": null,
			                    "jenisPerdagangan": "",
			                    "docNum": "",
			                    "statusLunas": "",
			                    "tglPelunasan": "'.$nota_date_noHour.'",
			                    "amountTerbilang": "",
			                    "ppnDipungutSendiri": "'.$arr['nota']['nota_ppn'].'",
			                    "ppnDipungutPemungut": "",
			                    "ppnTidakDipungut": "",
			                    "ppnDibebaskan": "",
			                    "uangJaminan": "",
			                    "piutang": "'.$arr['nota']['nota_amount'].'",
			                    "sourceInvoiceType": "'.$arr['nota']['nota_context'].'",
			                    "branchAccount": "'.$arr['nota']['nota_branch_account'].'",
			                    "statusCetak": "",
			                    "statusKirimEmail": "",
			                    "amountDasarPenghasilan": "'.$arr['nota']['nota_dpp'].'",
			                    "amountMaterai": null,
			                    "ppn10Persen": "'.$arr['nota']['nota_ppn'].'",
			                    "statusKoreksi": "",
			                    "tanggalKoreksi": null,
			                    "keteranganKoreksi": ""
			                },
			                "lines": ['.$lines.']
			            }
			        ],
			        "esbSecurity": {
			            "orgId": "'.$branch['branch_org_id'].'",
			            "batchSourceId": "",
			            "lastUpdateLogin": "",
			            "userId": "",
			            "respId": "",
			            "ledgerId": "",
			            "respApplId": "",
			            "batchSourceName": ""
			        }
			    }
			}';
			return json_decode($json, true);
			$json = json_encode(json_decode($json, true));
			$res = static::sendRequestToExtJsonMet([
	        	"user" => config('endpoint.esbPutInvoice.user'),
	        	"pass" => config('endpoint.esbPutInvoice.pass'),
	        	"target" => config('endpoint.esbPutInvoice.target'),
	        	"json" => $json
	        ]);
	        $hsl = true;
	        if ($res['response']['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
	        	$hsl = false;
	        }
			return ['Success' => $hsl, 'sendInvProforma' => $res];
		}

		public static function sendInvPay($arr){
			// di by passs dulu
			return ['Success' => true, 'response' => 'by passs'];
			// di by passs dulu
			$branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$arr['nota']['nota_branch_id'])->where('branch_code',$arr['nota']['nota_branch_code'])->get();
			if (count($branch) == 0) {
				return ['Success' =>false, 'response' => 'branch not found!'];
			}
			$branch = (array)$branch[0];
			$json = '
			{
				"arRequestDoc": {
					"esbHeader": {
						"internalId": "",
						"externalId": "",
						"timestamp": "",
						"responseTimestamp": "",
						"responseCode": "",
						"responseMessage": ""
					},
					"esbBody": [
						{
							"header": {
								"orgId": "'.$branch['branch_org_id'].'",
								"receiptNumber": "'.$arr['nota']['nota_no'].'",
								"receiptMethod": "BANK",
								"receiptAccount": "Mandiri IDR 120.00.4107201.3",
								"bankId": "105009",
								"customerNumber": "12777901",
								"receiptDate": "2019-11-28 20:15:08",
								"currencyCode": "IDR",
								"status": "P",
								"amount": "20000",
								"processFlag": "",
								"errorMessage": "",
								"apiMessage": "",
								"attributeCategory": "UPER",
								"referenceNum": "",
								"receiptType": "",
								"receiptSubType": "",
								"createdBy": "-1",
								"creationDate": "2019-11-28",
								"terminal": "",
								"attribute1": "",
								"attribute2": "",
								"attribute3": "",
								"attribute4": "",
								"attribute5": "",
								"attribute6": "",
								"attribute7": "",
								"attribute8": "",
								"attribute9": "",
								"attribute10": "",
								"attribute11": "",
								"attribute12": "",
								"attribute13": "",
								"attribute14": "BRG10",
								"attribute15": "",
								"statusReceipt": "N",
								"sourceInvoice": "BRG",
								"statusReceiptMsg": "",
								"invoiceNum": "",
								"amountOrig": null,
								"lastUpdateDate": "2019-11-28",
								"lastUpdateBy": "-1",
								"branchCode": "BTN",
								"branchAccount": "081",
								"sourceInvoiceType": "NPKBILLING",
								"remarkToBankId": "BANK_ACCOUNT_ID",
								"sourceSystem": "NPKBILLING",
								"comments": "Pembayaran uper",
								"cmsYn": "N",
								"tanggalTerima": null,
								"norekKoran": ""
							}
						}
					],
					"esbSecurity": {
						"orgId": "1822",
						"batchSourceId": "",
						"lastUpdateLogin": "",
						"userId": "",
						"respId": "",
						"ledgerId": "",
						"respApplId": "",
						"batchSourceName": ""
					}
				}
			}
			';

			$res = static::sendRequestToExtJsonMet([ // kirim putReceipt
				"user" => config('endpoint.esbPutReceipt.user'),
				"pass" => config('endpoint.esbPutReceipt.pass'),
				"target" => config('endpoint.esbPutReceipt.target'),
				"json" => $json
			]);
		}

		public static function getRealGati() {
			$getIdReal = DB::connection('omuster')->table('TX_DTL_REC')->where('REC_FL_REAL', '1')->select(DB::raw("DISTINCT REC_HDR_ID"))->get();
			foreach ($getIdReal as $value) {
				$input = ["rec_id"=>$value->rec_hdr_id];
				static::getRealRecPLG($input);
			}
		}

		public static function getRealRecPLG($input){
			$his_cont = [];
			$Success = true;
			$msg = 'Success get realisasion';
			$find = DB::connection('omuster')->table('TX_HDR_REC')->where('REC_ID', $input['rec_id'])->first();
			$dtlLoop = DB::connection('omuster')->table('TX_DTL_REC')->where('REC_HDR_ID', $input['rec_id'])->where('REC_DTL_ISACTIVE','Y')->where('REC_FL_REAL', '1')->get();
			if (count($dtlLoop) > 0) {
				$res = static::getRecGATI($find,$dtlLoop);
				if ($res['result']['count'] > 0) {
					$his_cont = static::storeRecGATI($res['result']['result'], $find);
				}else{
					$Success = false;
					$msg = 'realisasion not finish';
				}
			}
			$res['his_cont'] = $his_cont;
			$dtl = DB::connection('omuster')->table('TX_DTL_REC')
				->leftJoin('TX_GATEIN', function($join) use ($find){
					$join->on('TX_GATEIN.gatein_cont', '=', 'TX_DTL_REC.rec_dtl_cont');
					$join->on('TX_GATEIN.gatein_req_no', '=', DB::raw("'".$find->rec_no."'"));
				})->where('REC_HDR_ID', $input['rec_id'])->where('REC_DTL_ISACTIVE','Y')->get();
	        return [
	        	'response' => $Success,
	        	'result' => $msg,
	        	'no_rec' =>$find->rec_no,
	        	'hdr' =>$find,
	        	'dtl' => $dtl,
	        	'getRealRecPLG' => $res
	        ];
		}

		public static function getRecGATI($find,$dtlLoop){
			$dtl = '';
			$arrdtl = [];
			foreach ($dtlLoop as $list) {
				// Adding udate where('REC_FL_REAL', '0') For Every Detail
				$dtl .= '
				{
					"NO_CONTAINER": "'.$list->rec_dtl_cont.'",
					"NO_REQUEST": "'.$find->rec_no.'",
					"BRANCH_ID": "'.$find->rec_branch_id.'"
				},';
				$arrdtlset = [
					"NO_CONTAINER" => $list->rec_dtl_cont,
					"NO_REQUEST" => $find->rec_no,
					"BRANCH_ID" => $find->rec_branch_id
				];
				$arrdtl[] = $arrdtlset;
			}
			$head = [
				"action" => "generateGetIn",
				"data" => $arrdtl
			];
	        $dtl = substr($dtl, 0,-1);
			$json = '
			{
				"action" : "generateGetIn",
				"data": ['.$dtl.']
			}';
			$json = base64_encode(json_encode(json_decode($json,true)));
			$json = '
				{
				    "repoGetRequest": {
				        "esbHeader": {
				            "internalId": "",
				            "externalId": "",
				            "timestamp": "",
				            "responseTimestamp": "",
				            "responseCode": "",
				            "responseMessage": ""
				        },
				        "esbBody": {
				            "request": "'.$json.'"
				        },
				        "esbSecurity": {
				            "orgId": "",
				            "batchSourceId": "",
				            "lastUpdateLogin": "",
				            "userId": "",
				            "respId": "",
				            "ledgerId": "",
				            "respAppId": "",
				            "batchSourceName": ""
				        }
				    }
				}
	        ';
	        $json = json_encode(json_decode($json,true));
			$arr = [
	        	"user" => config('endpoint.tosGetPLG.user'),
	        	"pass" => config('endpoint.tosGetPLG.pass'),
	        	"target" => config('endpoint.tosGetPLG.target'),
	        	"json" => $json
	        ];
			$res = static::sendRequestToExtJsonMet($arr);
			return $res = static::decodeResultAftrSendToTosNPKS($res, 'repoGet');
		}

		public static function storeRecGATI($data,$hdr){
			$his_cont = [];
			foreach ($data as $listR) {
				$findGATI = [
					'GATEIN_CONT' => $listR['NO_CONTAINER'],
					'GATEIN_REQ_NO' => $listR['NO_REQUEST'],
					'GATEIN_BRANCH_ID' => $hdr->rec_branch_id,
					'GATEIN_BRANCH_CODE' => $hdr->rec_branch_code
				];
				$cek = DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->first();
				$datenow    = Carbon::now()->format('Y-m-d');
				$storeGATI = [
					"gatein_cont" => $listR['NO_CONTAINER'],
					"gatein_req_no" => $listR['NO_REQUEST'],
					"gatein_pol_no" => $listR['NOPOL'],
					"gatein_cont_status" => $listR['STATUS'],
						// "gatein_seal_no" => $listR[''],
						// "gatein_trucking" => $listR[''],
						// "gatein_yard" => $listR[''],
						// "gatein_mark" => $listR[''],
					"gatein_date" => date('Y-m-d', strtotime($listR['TGL_IN'])),
					"gatein_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')"),
					"gatein_create_by" => "1", //$input["user"]->user_id
					"gatein_branch_id" => $hdr->rec_branch_id,
					"gatein_branch_code" => $hdr->rec_branch_code
				];
				if (empty($cek)) {
					DB::connection('omuster')->table('TX_GATEIN')->insert($storeGATI);
				}else{
					DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->update($storeGATI);
				}
				$findTsCont = [
					'cont_no' => $listR['NO_CONTAINER'],
					'branch_id' => $hdr->rec_branch_id,
					'branch_code' => $hdr->rec_branch_code
				];
				$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->first(); //remove ->orderBy('cont_counter', 'desc')
				$cont_counter = $cekTsCont->cont_counter+1; //kusus gate in
				$cekKegiatan = DB::connection('omuster')->table('TM_REFF')->where([
					"reff_tr_id" => 12,
					"reff_name" => 'GATE IN'
				])->first();
				$arrStoreTsContAndTxHisCont = [
					'cont_no' => $listR['NO_CONTAINER'],
					'branch_id' => $hdr->rec_branch_id,
					'branch_code' => $hdr->rec_branch_code,
					'cont_location' => 'GATI',
					'cont_size' => null,
					'cont_type' => null,
					'cont_counter' => $cont_counter,
					'no_request' => $listR['NO_REQUEST'],
					'kegiatan' => $cekKegiatan->reff_id,
					'id_user' => "1", //$input["user"]->user_id
					'status_cont' => $listR['STATUS'],
					'vvd_id' => $hdr->rec_vvd_id
				];
				DB::connection('omuster')->table('TX_DTL_REC')->where('REC_HDR_ID', $hdr->rec_id)->where('REC_DTL_CONT', $listR['NO_CONTAINER'])->update(['REC_FL_REAL'=>"2"]);
				$his_cont[] = PlgRequestBooking::storeTsContAndTxHisCont($arrStoreTsContAndTxHisCont);
			}
			return $his_cont;
		}

		public static function getRealDelPLG($input){
			$find 				= DB::connection('omuster')->table('TX_HDR_DEL')->where('DEL_ID', $input['del_id'])->first();
			$dtlLoop 			= DB::connection('omuster')->table('TX_DTL_DEL')->where('DEL_HDR_ID', $input['del_id'])->where('DEL_DTL_ISACTIVE','Y')->get();
			$dtl 					= '';
			$arrdtl 			= [];

			foreach ($dtlLoop as $list) {
				$dtl .= '
				{
					"NO_CONTAINER"	: "'.$list->del_dtl_cont.'",
					"NO_REQUEST"		: "'.$find->del_no.'",
					"BRANCH_ID"			: "'.$find->del_branch_id.'"
				},';
				$arrdtlset = [
					"NO_CONTAINER" 	=> $list->del_dtl_cont,
					"NO_REQUEST"	  => $find->del_no,
					"BRANCH_ID" 		=> $find->del_branch_id
				];
				$arrdtl[]  = $arrdtlset;
			}

			$head = [
				"action" 	=> "generateGetOut",
				"data" 		=> $arrdtl
			];

		  $dtl 	= substr($dtl, 0,-1);
			$json = '
			{
				"action" : "generateGetOut",
				"data": ['.$dtl.']
			}';
			$json = base64_encode(json_encode(json_decode($json,true)));
			// $json = '{ "request" : "'.$json.'"}';
			$json = '
				{
				    "repoGetRequest": {
				        "esbHeader": {
				            "internalId": "",
				            "externalId": "",
				            "timestamp": "",
				            "responseTimestamp": "",
				            "responseCode": "",
				            "responseMessage": ""
				        },
				        "esbBody": {
				            "request": "'.$json.'"
				        },
				        "esbSecurity": {
				            "orgId": "",
				            "batchSourceId": "",
				            "lastUpdateLogin": "",
				            "userId": "",
				            "respId": "",
				            "ledgerId": "",
				            "respAppId": "",
				            "batchSourceName": ""
				        }
				    }
				}
	        ';
		    $json = json_encode(json_decode($json,true));
			$arr = [
	        	"user" => config('endpoint.tosGetPLG.user'),
	        	"pass" => config('endpoint.tosGetPLG.pass'),
	        	"target" => config('endpoint.tosGetPLG.target'),
	        	"json" => $json
	        ];
			$res 			= static::sendRequestToExtJsonMet($arr);
			$res	 		= static::decodeResultAftrSendToTosNPKS($res, 'repoGet');
			$his_cont = [];
			if ($res['result']['count'] > 0) {
				foreach ($res['result']['result'] as $listR) {
					$findGATO = [
						'GATEOUT_CONT' 					=> $listR['NO_CONTAINER'],
						'GATEOUT_REQ_NO' 				=> $listR['NO_REQUEST'],
						'GATEOUT_BRANCH_ID' 		=> $find->del_branch_id,
						'GATEOUT_BRANCH_CODE' 	=> $find->del_branch_code
					];


					$cek 				= DB::connection('omuster')->table('TX_GATEOUT')->where($findGATO)->first();
					$datenow    = Carbon::now()->format('Y-m-d');
					$storeGATO  = [
						"gateout_cont" 			 	=> $listR['NO_CONTAINER'],
						"gateout_req_no" 		 	=> $listR['NO_REQUEST'],
						"gateout_pol_no" 		 	=> $listR['NOPOL'],
						"gateout_cont_status" => $listR['STATUS'],
						// "gateout_seal_no" => $listR[''],
						// "gateout_trucking" => $listR[''],
						// "gateout_yard" => $listR[''],
						// "gateout_mark" => $listR[''],
						"gateout_date" 				=> date('Y-m-d', strtotime($listR['TGL_OUT'])),
						"gateout_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')"),
						"gateout_create_by" 	=> $input['user']->user_id,
						"gateout_branch_id" 	=> $find->del_branch_id,
						"gateout_branch_code" => $find->del_branch_code
					];


					if (empty($cek)) {
						DB::connection('omuster')->table('TX_GATEOUT')->insert($storeGATO);
					} else {
						DB::connection('omuster')->table('TX_GATEOUT')->where($findGATO)->update($storeGATO);
					}

					$findTsCont = [
						'cont_no' 			=> $listR['NO_CONTAINER'],
						'branch_id' 		=> $find->del_branch_id,
						'branch_code' 	=> $find->del_branch_code
					];


					$cekTsCont 				= DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
					$cont_counter 		= $cekTsCont->cont_counter;

					$cekKegiatan 			= DB::connection('omuster')->table('TM_REFF')->where([
						"reff_tr_id" 		=> 12,
						"reff_name" 		=> 'GATE OUT'
					])->first();


					$arrStoreTsContAndTxHisCont = [
						'cont_no' 			=> $listR['NO_CONTAINER'],
						'branch_id'			=> $find->del_branch_id,
						'branch_code' 	=> $find->del_branch_code,
						'cont_location' => 'GATO',
						'cont_size' 		=> null,
						'cont_type' 		=> null,
						'cont_counter'  => $cont_counter,
						'no_request' 		=> $listR['NO_REQUEST'],
						'kegiatan' 			=> $cekKegiatan->reff_id,
						'id_user' 			=> $input["user"]->user_id,
						'status_cont' 	=> $listR['STATUS'],
						'vvd_id' 				=> $find->del_vvd_id
					];

					$his_cont[] 			= PlgRequestBooking::storeTsContAndTxHisCont($arrStoreTsContAndTxHisCont);
				}

				$Success = true;
				$msg 		 = 'Success get realisasion';

			} else {
				$Success = false;
				$msg 		 = 'realisasion not finish';
			}

			$res['his_cont'] = $his_cont;
			$dtl 						 = DB::connection('omuster')->table('TX_DTL_DEL')
												->leftJoin('TX_GATEOUT', function($join) use ($find){
													$join->on('TX_GATEOUT.gateout_cont', '=', 'TX_DTL_DEL.del_dtl_cont');
													$join->on('TX_GATEOUT.gateout_req_no', '=', DB::raw("'".$find->del_no."'"));
												})
												->where('DEL_HDR_ID', $input['del_id'])->where('DEL_DTL_ISACTIVE','Y')->get();
			return [
				'response' 		 => $Success,
				'result' 			 => $msg,
				'no_del' 			 => $find->del_no,
				'hdr' 				 => $find,
				'dtl' 				 => $dtl,
				'getRealRecPLG'=> $res
			];
		}

		public static function getRealStuffPLG($input){
			$his_cont = [];
			$Success = true;
			$msg = 'Success get realisasion';
			$find = DB::connection('omuster')->table('TX_HDR_STUFF')->where('stuff_id', $input['stuff_id'])->first();
			$dtlLoop = DB::connection('omuster')->table('TX_DTL_STUFF')->where('stuff_hdr_id', $input['stuff_id'])->get();
			if (count($dtlLoop) > 0) {
				$res = static::getStuffGATI($find,$dtlLoop);
				if ($res['result']['count'] > 0) {
					$his_cont = static::storeStuffGATI($res['result']['result'], $find);
				}else{
					$Success = false;
					$msg = 'realisasion not finish';
				}
			}
			$res['his_cont'] = $his_cont;
			$dtl = DB::connection('omuster')->table('TX_DTL_REC')
				->leftJoin('TX_GATEIN', function($join) use ($find){
					$join->on('TX_GATEIN.gatein_cont', '=', 'TX_DTL_REC.rec_dtl_cont');
					$join->on('TX_GATEIN.gatein_req_no', '=', DB::raw("'".$find->rec_no."'"));
				})->where('REC_HDR_ID', $input['rec_id'])->where('REC_DTL_ISACTIVE','Y')->get();
	        return [
	        	'response' => $Success,
	        	'result' => $msg,
	        	'no_rec' =>$find->rec_no,
	        	'hdr' =>$find,
	        	'dtl' => $dtl,
	        	'getRealRecPLG' => $res
	        ];
		}

		public static function getStuffGATI($find,$dtlLoop){
			$dtl = '';
			$arrdtl = [];
			foreach ($dtlLoop as $list) {
				// Adding udate where('REC_FL_REAL', '0') For Every Detail
				$dtl .= '
				{
					"NO_CONTAINER": "'.$list->rec_dtl_cont.'",
					"NO_REQUEST": "'.$find->rec_no.'",
					"BRANCH_ID": "'.$find->rec_branch_id.'"
				},';
				$arrdtlset = [
					"NO_CONTAINER" => $list->rec_dtl_cont,
					"NO_REQUEST" => $find->rec_no,
					"BRANCH_ID" => $find->rec_branch_id
				];
				$arrdtl[] = $arrdtlset;
			}
			$head = [
				"action" => "generateGetIn",
				"data" => $arrdtl
			];
	        $dtl = substr($dtl, 0,-1);
			$json = '
			{
				"action" : "generateGetIn",
				"data": ['.$dtl.']
			}';
			$json = base64_encode(json_encode(json_decode($json,true)));
			$json = '
				{
				    "repoGetRequest": {
				        "esbHeader": {
				            "internalId": "",
				            "externalId": "",
				            "timestamp": "",
				            "responseTimestamp": "",
				            "responseCode": "",
				            "responseMessage": ""
				        },
				        "esbBody": {
				            "request": "'.$json.'"
				        },
				        "esbSecurity": {
				            "orgId": "",
				            "batchSourceId": "",
				            "lastUpdateLogin": "",
				            "userId": "",
				            "respId": "",
				            "ledgerId": "",
				            "respAppId": "",
				            "batchSourceName": ""
				        }
				    }
				}
	        ';
	        $json = json_encode(json_decode($json,true));
			$arr = [
	        	"user" => config('endpoint.tosGetPLG.user'),
	        	"pass" => config('endpoint.tosGetPLG.pass'),
	        	"target" => config('endpoint.tosGetPLG.target'),
	        	"json" => $json
	        ];
			$res = static::sendRequestToExtJsonMet($arr);
			return $res = static::decodeResultAftrSendToTosNPKS($res, 'repoGet');
		}

		public static function storeStuffGATI($data,$hdr){
			$his_cont = [];
			foreach ($data as $listR) {
				$findGATI = [
					'GATEIN_CONT' => $listR['NO_CONTAINER'],
					'GATEIN_REQ_NO' => $listR['NO_REQUEST'],
					'GATEIN_BRANCH_ID' => $hdr->rec_branch_id,
					'GATEIN_BRANCH_CODE' => $hdr->rec_branch_code
				];
				$cek = DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->first();
				$datenow    = Carbon::now()->format('Y-m-d');
				$storeGATI = [
					"gatein_cont" => $listR['NO_CONTAINER'],
					"gatein_req_no" => $listR['NO_REQUEST'],
					"gatein_pol_no" => $listR['NOPOL'],
					"gatein_cont_status" => $listR['STATUS'],
						// "gatein_seal_no" => $listR[''],
						// "gatein_trucking" => $listR[''],
						// "gatein_yard" => $listR[''],
						// "gatein_mark" => $listR[''],
					"gatein_date" => date('Y-m-d', strtotime($listR['TGL_IN'])),
					"gatein_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')"),
					"gatein_create_by" => "1", //$input["user"]->user_id
					"gatein_branch_id" => $hdr->rec_branch_id,
					"gatein_branch_code" => $hdr->rec_branch_code
				];
				if (empty($cek)) {
					DB::connection('omuster')->table('TX_GATEIN')->insert($storeGATI);
				}else{
					DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->update($storeGATI);
				}
				$findTsCont = [
					'cont_no' => $listR['NO_CONTAINER'],
					'branch_id' => $hdr->rec_branch_id,
					'branch_code' => $hdr->rec_branch_code
				];
				$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->first(); //remove ->orderBy('cont_counter', 'desc')
				$cont_counter = $cekTsCont->cont_counter+1; //kusus gate in
				$cekKegiatan = DB::connection('omuster')->table('TM_REFF')->where([
					"reff_tr_id" => 12,
					"reff_name" => 'GATE IN'
				])->first();
				$arrStoreTsContAndTxHisCont = [
					'cont_no' => $listR['NO_CONTAINER'],
					'branch_id' => $hdr->rec_branch_id,
					'branch_code' => $hdr->rec_branch_code,
					'cont_location' => 'GATI',
					'cont_size' => null,
					'cont_type' => null,
					'cont_counter' => $cont_counter,
					'no_request' => $listR['NO_REQUEST'],
					'kegiatan' => $cekKegiatan->reff_id,
					'id_user' => "1", //$input["user"]->user_id
					'status_cont' => $listR['STATUS'],
					'vvd_id' => $hdr->rec_vvd_id
				];
				DB::connection('omuster')->table('TX_DTL_REC')->where('REC_HDR_ID', $hdr->rec_id)->where('REC_DTL_CONT', $listR['NO_CONTAINER'])->update(['REC_FL_REAL'=>"2"]);
				$his_cont[] = PlgRequestBooking::storeTsContAndTxHisCont($arrStoreTsContAndTxHisCont);
			}
			return $his_cont;
		}

		public static function getUpdatePlacement(){
		  $all 						 = [];
		  $now 						 = Carbon::now()->format('Y-m-d');
		  $det 						 = DB::connection('omuster')->table('TX_DTL_REC')->where('REC_FL_REAL', "2")->get();
		  foreach ($det as $lista) {
		    $newDt 				 = [];
		    foreach ($lista as $key => $value) {
		      $newDt[$key] = $value;
		    }

		    $hdr 		 			 = DB::connection('omuster')->table('TX_HDR_REC')->where('REC_ID', $lista->rec_hdr_id)->get();
		    foreach ($hdr as $listS) {
		      foreach ($listS as $key => $value) {
		        $newDt[$key] = $value;
		      }
		    }

		      $all[] 				= $newDt;
		    }

		  $dtl 							= '';
		  $arrdtl 					= [];

		  foreach ($all as $list) {
		    $dtl .= '
		    {
		      "NO_CONTAINER"	: "'.$list["rec_dtl_cont"].'",
		      "NO_REQUEST"		: "'.$list["rec_no"].'",
		      "BRANCH_ID"			: "'.$list["rec_branch_id"].'"
		    },';
		    $arrdtlset = [
		      "NO_CONTAINER" 	=> $list["rec_dtl_cont"],
		      "NO_REQUEST"	  => $list["rec_no"],
		      "BRANCH_ID" 		=> $list["rec_branch_id"]
		    ];
		    $arrdtl[]  = $arrdtlset;
		  }

		  $head = [
		    "action" 	=> "generatePlacement",
		    "data" 		=> $arrdtl
		  ];

		  $dtl 	= substr($dtl, 0,-1);
		  $json = '
		  {
		    "action" : "generatePlacement",
		    "data": ['.$dtl.']
		  }';

		  $json = base64_encode(json_encode(json_decode($json,true)));
		  $json = '
		    {
		        "repoGetRequest": {
		            "esbHeader": {
		                "internalId": "",
		                "externalId": "",
		                "timestamp": "",
		                "responseTimestamp": "",
		                "responseCode": "",
		                "responseMessage": ""
		            },
		            "esbBody": {
		                "request": "'.$json.'"
		            },
		            "esbSecurity": {
		                "orgId": "",
		                "batchSourceId": "",
		                "lastUpdateLogin": "",
		                "userId": "",
		                "respId": "",
		                "ledgerId": "",
		                "respAppId": "",
		                "batchSourceName": ""
		            }
		        }
		    }
		      ';
		  $json = json_encode(json_decode($json,true));
		  $arr = [
		          "user"		 	=> config('endpoint.tosGetPLG.user'),
		          "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
		          "target" 	 	=> config('endpoint.tosGetPLG.target'),
		          "json" 		 	=> $json
		        ];
		  $res 							 	= static::sendRequestToExtJsonMet($arr);
		  $res				 			 	= static::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		  foreach ($res["result"]["result"] as $listR) {
		    $findCont 				= [
		      "CONT_NO" 			=> $listR["NO_CONTAINER"],
		      "CONT_LOCATION" => "GATI"
		    ];

		    $findPlacement 		= [
		      "NO_REQUEST" 		=> $listR["NO_REQUEST"],
		      "NO_CONTAINER" 	=> $listR["NO_CONTAINER"]
		    ];

				$findHistory 			= [
		      "NO_REQUEST" 		=> $listR["NO_REQUEST"],
		      "NO_CONTAINER" 	=> $listR["NO_CONTAINER"],
					"KEGIATAN"			=> "12"
		    ];

		    $tsContainer 		 	= DB::connection('omuster')->table('TS_CONTAINER')->where($findCont)->first();
		                        DB::connection('omuster')->table('TS_CONTAINER')->where($findCont)->update(['CONT_LOCATION'=>"IN_YARD"]);
		    $placementID 			= DB::connection('omuster')->table('DUAL')->select('SEQ_TX_PLACEMENT.NEXTVAL')->get();

		    $storePlacement  	= [
		      "PLACEMENT_ID"	=> $placementID[0]->nextval,
		      "NO_REQUEST"		=> $listR["NO_REQUEST"],
		      "NO_CONTAINER"	=> $listR["NO_CONTAINER"],
		      "YBC_SLOT"			=> $listR["YBC_SLOT"],
		      "YBC_ROW"				=> $listR["YBC_ROW"],
		      "YBC_BLOCK_ID"	=> $listR["YBC_BLOCK_ID"],
		      "TIER"					=> $listR["TIER"],
		      "ID_YARD"				=> $listR["ID_YARD"],
		      "ID_USER"				=> $listR["ID_USER"],
		      "CONT_STATUS"		=> $listR["CONT_STATUS"],
		      "TGL_PLACEMENT"	=> date('Y-m-d h:i:s', strtotime($listR['TGL_PLACEMENT'])),
		      "BRANCH_ID"			=> $listR["BRANCH_ID"],
		      "CONT_COUNTER"	=> $tsContainer->cont_counter
		    ];

		    $storeHistory 		= [
		      "NO_CONTAINER" 	=> $listR["NO_CONTAINER"],
		      "NO_REQUEST"		=> $listR["NO_REQUEST"],
		      "KEGIATAN"			=> "12",
		      "TGL_UPDATE"		=> date('Y-m-d h:i:s', strtotime($listR['TGL_PLACEMENT'])),
		      "ID_USER"				=> $listR["ID_USER"],
		      "ID_YARD"				=> $listR["ID_YARD"],
		      "STATUS_CONT"		=> $listR["CONT_STATUS"],
		      "VVD_ID"				=> "",
		      "COUNTER"				=> $tsContainer->cont_counter,
		      "SUB_COUNTER"		=> "",
		      "WHY"						=> ""
		    ];

		    $cekPlacement 		= DB::connection('omuster')->table('TX_PLACEMENT')->where($findPlacement)->first();
		    if (empty($cekPlacement)) {
		      DB::connection('omuster')->table('TX_PLACEMENT')->insert($storePlacement);
		    } else {
		      DB::connection('omuster')->table('TX_PLACEMENT')->where($findPlacement)->update($storePlacement);
		    }

		    $cekHistory 			= DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->first();
		    if (empty($cekHistory)) {
		      DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeHistory);
		    } else {
		      DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->update($storeHistory);
		    }
		  }

		  $updateDetail				= DB::connection('omuster')->table("TX_DTL_REC")->where('REC_FL_REAL', "3")->get();
		  foreach ($updateDetail as $updateVal) {
		    $updateFlReal 		= DB::connection('omuster')->table("TX_DTL_REC")->where('REC_DTL_ID', $updateVal->rec_dtl_id)->update(["rec_fl_real"=>"2"]);
		  }
		}
	// PLG
}
