<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Models\OmUster\TxHdrNota;
use App\Helper\PlgRequestBooking;
use App\Helper\PlgFunctTOS;

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
				$input = ["nota_id"=>1,"id"=>$getIdReal[0]->rec_hdr_id];
				PlgFunctTOS::getRealPLG($input);
			}
			// $getIdReal = DB::connection('omuster')->table('TX_DTL_DEL')->where('DEL_FL_REAL', '1')->select(DB::raw("DISTINCT DEL_HDR_ID"))->get();
			// foreach ($getIdReal as $value) {
			// 	$input = ["nota_id"=>2,"id"=>$value->rec_id];
			// 	PlgFunctTOS::getRealPLG($input);
			// }
		}

		public static function getUpdatePlacement(){
		  $all 						 = [];
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

			if (empty($res["result"]["result"])) {
				return "Placement is uptodate";
			}

			static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

			$updateDetail				= DB::connection('omuster')->table("TX_DTL_REC")->where('REC_FL_REAL', "2")->get();
		  foreach ($updateDetail as $updateVal) {
		    $updateFlReal 		= DB::connection('omuster')->table("TX_DTL_REC")->where('REC_DTL_ID', $updateVal->rec_dtl_id)->update(["rec_fl_real"=>"3"]);
		  }

		  foreach ($res["result"]["result"] as $listR) {
		    $findCont 				= [
		      "CONT_NO" 			=> $listR["NO_CONTAINER"],
		      "CONT_LOCATION" => "GATI",
					"BRANCH_ID"			=> $listR["BRANCH_ID"]
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
		      "HISTORY_DATE"		=> date('Y-m-d h:i:s', strtotime($listR['TGL_PLACEMENT'])),
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
		}

		public static function getRealStuffing() {
			$all 						 = [];
		 $det 						 = DB::connection('omuster')->table('TX_DTL_STUFF')->where('STUFF_FL_REAL', "1")->get();
		 foreach ($det as $lista) {
			 $newDt 				 = [];
			 foreach ($lista as $key => $value) {
				 $newDt[$key] = $value;
			 }

			 $hdr 		 			 = DB::connection('omuster')->table('TX_HDR_STUFF')->where('STUFF_ID', $lista->stuff_hdr_id)->get();
			 foreach ($hdr as $listS) {
				 foreach ($listS as $key => $value) {
					 $newDt[$key] = $value;
				 }
			 }

				 $all[] 				= $newDt;
			 }

		 $dtl 							= '';
		 $arrdtl 						= [];

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["stuff_dtl_cont"].'",
				 "NO_REQUEST"			: "'.$list["stuff_no"].'",
				 "BRANCH_ID"			: "'.$list["stuff_branch_id"].'"
			 },';
		 }

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generateRealStuffing",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= static::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return "STUFF is uptodate";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 foreach ($res["result"]["result"] as $value) {
			$stufBranch 				= $value["REAL_STUFF_BRANCH_ID"];
		 	$stuffReq 					= $value["REAL_STUFF_NOREQ"];
			$stuffCont 					= $value["REAL_STUFF_CONT"];
			$stuffDate 					= date('Y-m-d', strtotime($value["REAL_STUFF_DATE"]));

			$findHdrStuff 			= [
				"STUFF_BRANCH_ID" => $stufBranch,
				"STUFF_NO"				=> $stuffReq
			];

			$stuffHDR 					= DB::connection('omuster')->table('TX_HDR_STUFF')->where($findHdrStuff)->first();

			$findDtlStuff 			= [
				"STUFF_HDR_ID"		=> $stuffHDR->stuff_id,
				"STUFF_DTL_CONT"	=> $stuffCont
			];

			$findHistory 				= [
				"NO_REQUEST" 			=> $stuffReq,
				"NO_CONTAINER" 		=> $stuffCont,
				"KEGIATAN"				=> "13"
			];

			$storeHistory 			= [
				"NO_CONTAINER" 		=> $stuffCont,
				"NO_REQUEST"			=> $stuffReq,
				"KEGIATAN"				=> "13",
				"HISTORY_DATE"			=> date('Y-m-d h:i:s', strtotime($stuffDate)),
				"ID_USER"					=> $value["REAL_STUFF_OPERATOR"],
				"ID_YARD"					=> "",
				"STATUS_CONT"			=> "",
				"VVD_ID"					=> "",
				"COUNTER"					=> $value["REAL_STUFF_COUNTER"],
				"SUB_COUNTER"			=> "",
				"WHY"							=> ""
			];


			$cekHistory 				= DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->first();

			if (empty($cekHistory)) {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeHistory);
			} else {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->update($storeHistory);
			}

			$setReal 						= DB::connection('omuster')->table('TX_DTL_STUFF')->where($findDtlStuff)->update(["STUFF_DTL_REAL_DATE"=>$stuffDate,"STUFF_FL_REAL"=>4]);
			echo "Realization Stuffing Done";
			}
		}

		public static function getRealStripping() {
		 $all 						 = [];
		 $det 						 = DB::connection('omuster')->table('TX_DTL_STRIPP')->where('STRIPP_FL_REAL', "1")->get();
		 foreach ($det as $lista) {
			 $newDt 				 = [];
			 foreach ($lista as $key => $value) {
				 $newDt[$key] = $value;
			 }

			 $hdr 		 			 = DB::connection('omuster')->table('TX_HDR_STRIPP')->where('STRIPP_ID', $lista->stripp_hdr_id)->get();
			 foreach ($hdr as $listS) {
				 foreach ($listS as $key => $value) {
					 $newDt[$key] = $value;
				 }
			 }

				 $all[] 				= $newDt;
			 }

		 $dtl 							= '';
		 $arrdtl 						= [];

		 // return $all;

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["stripp_dtl_cont"].'",
				 "NO_REQUEST"			: "'.$list["stripp_no"].'",
				 "BRANCH_ID"			: "'.$list["stripp_branch_id"].'"
			 },';

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generateRealStripping",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= static::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return "STRIPP is uptodate";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 // return $res["result"]["result"];
		 foreach ($res["result"]["result"] as $value) {
			$stripBranch 				= $value["REAL_STRIP_BRANCH_ID"];
			$stripReq 					= $value["REAL_STRIP_NOREQ"];
			$stripCont 					= $value["REAL_STRIP_CONT"];
			$stripDate 					= date('Y-m-d', strtotime($value["REAL_STRIP_DATE"]));

			$findHdrStrip 			= [
				"STRIPP_BRANCH_ID"=> $stripBranch,
				"STRIPP_NO"				=> $stripReq
			];

			$stripHDR 					= DB::connection('omuster')->table('TX_HDR_STRIPP')->where($findHdrStrip)->first();

			$findDtlStuff 			= [
				"STRIPP_HDR_ID"		=> $stripHDR->stripp_id,
				"STRIPP_DTL_CONT"	=> $stripCont
			];

			$findHistory 				= [
				"NO_REQUEST" 			=> $stripReq,
				"NO_CONTAINER" 		=> $stripCont,
				"KEGIATAN"				=> "14"
			];

			$storeHistory 			= [
				"NO_CONTAINER" 		=> $stripCont,
				"NO_REQUEST"			=> $stripReq,
				"KEGIATAN"				=> "14",
				"HISTORY_DATE"			=> date('Y-m-d h:i:s', strtotime($stripDate)),
				"ID_USER"					=> $value["REAL_STRIP_OPERATOR"],
				"ID_YARD"					=> "",
				"STATUS_CONT"			=> "",
				"VVD_ID"					=> "",
				"COUNTER"					=> $value["REAL_STRIP_COUNTER"],
				"SUB_COUNTER"			=> "",
				"WHY"							=> ""
			];

			$cekHistory 				= DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->first();

			if (empty($cekHistory)) {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeHistory);
			} else {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->update($storeHistory);
			}

				$setReal 						= DB::connection('omuster')->table('TX_DTL_STRIPP')->where($findDtlStuff)->update(["STRIPP_DTL_REAL_DATE"=>$stripDate,"STRIPP_FL_REAL"=>5]);
				echo "Realization Stripping Done";
			 	}
			}
		}

		public static function getRealFumigasi() {
 		 $all 						  = DB::connection('omuster')->table('TX_HDR_FUMI A')->leftJoin('TX_DTL_FUMI B', 'B.FUMI_HDR_ID', '=', 'A.FUMI_ID')->where('B.FUMI_FL_REAL', "1")->get();
 		 $dtl 							= '';
 		 $arrdtl 						= [];
		 $all 							= json_decode(json_encode($all),TRUE);

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["fumi_dtl_cont"].'",
				 "NO_REQUEST"			: "'.$list["fumi_no"].'",
				 "BRANCH_ID"			: "'.$list["fumi_branch_id"].'"
			 },';

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generateFumi",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= static::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return "Fumi is uptodate";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 // return $res["result"]["result"];
		 foreach ($res["result"]["result"] as $value) {
			$fumiBranch 				= $value["REAL_FUMI_BRANCH_ID"];
		 	$fumiReq 						= $value["REAL_FUMI_NOREQ"];
			$fumiCont 					= $value["REAL_FUMI_CONT"];
			$fumiDate 					= date('Y-m-d', strtotime($value["REAL_FUMI_DATE"]));

			$findHdrFumi 			= [
				"FUMI_BRANCH_ID" => $fumiBranch,
				"FUMI_NO"				=> $fumiReq
			];

			$fumiHDR 					= DB::connection('omuster')->table('TX_HDR_FUMI')->where($findHdrFumi)->first();

			$findDtlFumi 			= [
				"FUMI_HDR_ID"		=> $fumiHDR->fumi_id,
				"FUMI_DTL_CONT"	=> $fumiCont
			];

			$findHistory 				= [
				"NO_REQUEST" 			=> $fumiReq,
				"NO_CONTAINER" 		=> $fumiCont,
				"KEGIATAN"				=> "15"
			];

			$storeHistory 			= [
				"NO_CONTAINER" 		=> $fumiCont,
				"NO_REQUEST"			=> $fumiReq,
				"KEGIATAN"				=> "15",
				"HISTORY_DATE"			=> date('Y-m-d h:i:s', strtotime($fumiDate)),
				"ID_USER"					=> $value["REAL_FUMI_OPERATOR"],
				"ID_YARD"					=> "",
				"STATUS_CONT"			=> "",
				"VVD_ID"					=> "",
				"COUNTER"					=> $value["REAL_FUMI_COUNTER"],
				"SUB_COUNTER"			=> "",
				"WHY"							=> ""
			];


			$cekHistory 				= DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->first();

			// return $findDtlFumi;
			if (empty($cekHistory)) {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeHistory);
			} else {
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->where($findHistory)->update($storeHistory);
			}

			$setReal 						= DB::connection('omuster')->table('TX_DTL_FUMI')->where($findDtlFumi)->update(["FUMI_DTL_REAL_DATE"=>$fumiDate,"FUMI_FL_REAL"=>5]);
			echo "Realization Fumigasi Done";
			 	}
			}
		}

		public static function getRealPlug() {
 		 $all 						  = DB::connection('omuster')->table('TX_HDR_PLUG A')->leftJoin('TX_DTL_PLUG B', 'B.PLUG_HDR_ID', '=', 'A.PLUG_ID')->whereIn('B.PLUG_FL_REAL', ["1","7"])->get();
 		 $dtl 							= '';
 		 $arrdtl 						= [];
		 $all 							= json_decode(json_encode($all),TRUE);

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["plug_dtl_cont"].'",
				 "NO_REQUEST"			: "'.$list["plug_no"].'",
				 "BRANCH_ID"			: "'.$list["plug_branch_id"].'"
			 },';
		 }

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generatePlugStart",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= PlgFunctTOS::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return "PLUG Start is uptodate";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 // return $res["result"]["result"];
		 foreach ($res["result"]["result"] as $value) {
			$plugBranch 				= $value["REAL_PLUG_BRANCH_ID"];
		 	$plugReq 						= $value["REAL_PLUG_NOREQ"];
			$plugCont 					= $value["REAL_PLUG_CONT"];
			$plugStatus 				= $value["REAL_PLUG_STATUS"];
			$plugDate 					= date('Y-m-d', strtotime($value["REAL_PLUG_DATE"]));

			$findHdrPlug 			= [
				"PLUG_BRANCH_ID" => $plugBranch,
				"PLUG_NO"				=> $plugReq
			];


			$plugHDR 					= DB::connection('omuster')->table('TX_HDR_PLUG')->where($findHdrPlug)->first();

			$findDtlPlug 			= [
				"PLUG_HDR_ID"		=> $plugHDR->plug_id,
				"PLUG_DTL_CONT"	=> $plugCont
			];

			$storeHistory 			= [
				"NO_CONTAINER" 		=> $plugCont,
				"NO_REQUEST"			=> $plugReq,
				"KEGIATAN"				=> "16",
				"HISTORY_DATE"			=> date('Y-m-d h:i:s', strtotime($plugDate)),
				"ID_USER"					=> $value["REAL_PLUG_OPERATOR"],
				"ID_YARD"					=> "",
				"STATUS_CONT"			=> "",
				"VVD_ID"					=> "",
				"COUNTER"					=> $value["REAL_PLUG_COUNTER"],
				"SUB_COUNTER"			=> "",
				"WHY"							=> ""
			];

				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeHistory);
				if ($plugStatus == "1") {
					$setReal 						= DB::connection('omuster')->table('TX_DTL_PLUG')->where($findDtlPlug)->update(["PLUG_DTL_REAL_START_DATE"=>$plugDate,"PLUG_FL_REAL"=>7]);
				} else {
					$setReal 						= DB::connection('omuster')->table('TX_DTL_PLUG')->where($findDtlPlug)->update(["PLUG_DTL_REAL_END_DATE"=>$plugDate,"PLUG_FL_REAL"=>8]);
				}

			}
		}

		public static function getRealRecBRG() {
 		 $all 						  = DB::connection('omuster')
		 											->table('TX_HDR_REC_CARGO A')
													->join('TX_DTL_REC_CARGO B', 'B.REC_CARGO_HDR_ID', '=', 'A.REC_CARGO_ID')
													->where('B.REC_CARGO_FL_REAL', "1")
													->get();

 		 $dtl 							= '';
 		 $arrdtl 						= [];
		 $all 							= json_decode(json_encode($all),TRUE);

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["rec_cargo_dtl_si_no"].'",
				 "NO_REQUEST"			: "'.$list["rec_cargo_no"].'",
				 "BRANCH_ID"			: "'.$list["rec_cargo_branch_id"].'"
			 },';
		 }

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generateRecRealStorage",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= PlgFunctTOS::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return " Real Receiving Cargo is uptodate ";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 // return $res["result"]["result"];
		 foreach ($res["result"]["result"] as $value) {
			$recBrgBranch 				= $value["BRANCH_ID"];
		 	$recBrgReq 						= $value["NO_REQUEST"];
			$recBrgCont 					= $value["NO_CONTAINER"];
			$recBrgJml 						= $value["JUMLAH"];

			$findHdrRecBrg 				= [
				"REC_CARGO_BRANCH_ID" => $recBrgBranch,
				"REC_CARGO_NO"				=> $recBrgReq
			];


			$recBrgHDR 					= DB::connection('omuster')->table('TX_HDR_REC_CARGO')->where($findHdrRecBrg)->first();

			$findDtlRecBrg 			= [
				"REC_CARGO_HDR_ID"		=> $recBrgHDR->rec_cargo_id,
				"REC_CARGO_DTL_SI_NO"	=> $recBrgCont
				];
		 	}

			$dataDetail 				= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update(["REC_CARGO_DTL_REAL_QTY"=>$recBrgJml, "REC_CARGO_REMAINING_QTY"=>$recBrgJml]);

		 	$dataDetail 				= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->first();
			$qty 								= $dataDetail->rec_cargo_dtl_qty;
			$qtyReal 						= $dataDetail->rec_cargo_dtl_real_qty;


			if ($qty <= $qtyReal) {
				$updateFlReal 			= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update(["REC_CARGO_FL_REAL"=>10]);
			}
		}

		public static function getRealDelBRG() {
 		 $all 						  = DB::connection('omuster')
		 											->table('TX_HDR_DEL_CARGO A')
													->join('TX_DTL_DEL_CARGO B', 'B.DEL_CARGO_HDR_ID', '=', 'A.DEL_CARGO_ID')
													->where('B.DEL_CARGO_FL_REAL', "1")
													->get();

		 return $all;

 		 $dtl 							= '';
 		 $arrdtl 						= [];
		 $all 							= json_decode(json_encode($all),TRUE);

		 foreach ($all as $list) {
			 $dtl .= '
			 {
				 "NO_CONTAINER"		: "'.$list["rec_cargo_dtl_si_no"].'",
				 "NO_REQUEST"			: "'.$list["rec_cargo_no"].'",
				 "BRANCH_ID"			: "'.$list["rec_cargo_branch_id"].'"
			 },';
		 }

		 $dtl 	= substr($dtl, 0,-1);
		 $json = '
		 {
			 "action" : "generateRecRealStorage",
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
						 "user"		 		=> config('endpoint.tosGetPLG.user'),
						 "pass" 		 	=> config('endpoint.tosGetPLG.pass'),
						 "target" 	 	=> config('endpoint.tosGetPLG.target'),
						 "json" 		 	=> $json
					 ];
		 $res 							 	= static::sendRequestToExtJsonMet($arr);
		 $res				 			 		= PlgFunctTOS::decodeResultAftrSendToTosNPKS($res, 'repoGet');

		 if (empty($res["result"]["result"])) {
			 return " Real Receiving Cargo is uptodate ";
		 }

		 static::storeTxServices($json,json_decode($json,true)["repoGetRequest"]["esbBody"]["request"],$res["result"]["result"]);

		 // return $res["result"]["result"];
		 foreach ($res["result"]["result"] as $value) {
			$recBrgBranch 				= $value["BRANCH_ID"];
		 	$recBrgReq 						= $value["NO_REQUEST"];
			$recBrgCont 					= $value["NO_CONTAINER"];
			$recBrgJml 						= $value["JUMLAH"];

			$findHdrRecBrg 				= [
				"REC_CARGO_BRANCH_ID" => $recBrgBranch,
				"REC_CARGO_NO"				=> $recBrgReq
			];


			$recBrgHDR 					= DB::connection('omuster')->table('TX_HDR_REC_CARGO')->where($findHdrRecBrg)->first();

			$findDtlRecBrg 			= [
				"REC_CARGO_HDR_ID"		=> $recBrgHDR->rec_cargo_id,
				"REC_CARGO_DTL_SI_NO"	=> $recBrgCont
				];
		 	}

		 	$dataDetail 				= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->first();
			$qty 								= $dataDetail->rec_cargo_dtl_qty;
			$qtyReal 						= $dataDetail->rec_cargo_dtl_real_qty;


			if ($qty != $qtyReal) {
				$dataDetail 				= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update(["REC_CARGO_DTL_REAL_QTY"=>$recBrgJml, "REC_CARGO_REMAINING_QTY"=>$recBrgJml]);
			} else {
				$updateFlReal 			= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update(["REC_CARGO_FL_REAL"=>10]);
			}
		}

		public static function storeTxServices($json, $jsonRequest, $jsonResponse) {
			$request 									= json_decode($json,true);
			$service["request"] 			= base64_decode($jsonRequest);
			$service["response"]			= json_encode($jsonResponse);

			$storeService 						= [
				"SERVICES_ID"						=> "",
				"SERVICES_BRANCH_ID"		=> "4",
				"SERVICES_TIME"					=> date('Y-m-d H:i'),
				"SERVICES_REQUEST"			=> $service["request"],
				"SERVICES_RESPONSE"			=> $service["response"]
			];

			$insert 									= DB::connection('omuster')->table('TX_SERVICES')->insert($storeService);
		}

	// PLG
}
