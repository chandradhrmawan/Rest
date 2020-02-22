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
	          return ["Success"=>false, "request" => $arr, "response" => $error];
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
		  $res				 			 	= PlgFunctTOS::decodeResultAftrSendToTosNPKS($res, 'repoGet');

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

		    $updateFlReal 		= DB::connection('omuster')->table("TX_DTL_REC")->where('REC_DTL_ID', $updateVal->rec_dtl_id)->update(["rec_dtl_real_date"=>date('Y-m-d h:i:s', strtotime($listR['TGL_PLACEMENT']))]);

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

		public static function flagRealisationRequest(){
			$res = [];
			$nota = DB::connection('mdm')->table('TS_NOTA')->where('FLAG_STATUS','Y')->whereNotNull('API_SET')->whereNotIn('NOTA_ID',[20])->orderBy('nota_id', 'asc')->get();
			$nota_id_old = 0;
			foreach ($nota as $notaData) {
				if ($nota_id_old != $notaData->nota_id) {
					$config = json_decode($notaData->api_set, true);
					$hdr = DB::connection('omuster')->table($config['head_table'])->where($config['head_status'], 3)->get();
					foreach ($hdr as $list) {
						$list = (array)$list;
						$cekNota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no',$list[$config['head_no']])->first();
						// Advance Payment Auto Realisation
						if ($list[$config["head_paymethod"]] == "1") {
							$findFinishflReal = [
									$config["head_forigen"] => $list[$config["head_primery"]],
									$config["DTL_FL_REAL"] 	=> $config["DTL_FL_REAL_F"][0]
							];
							$findFinishfl = [
									$config["head_forigen"] => $list[$config["head_primery"]]
							];
							$cekDetail 		= DB::connection('omuster')->table($config["head_tab_detil"])->where($findFinishfl)->get();
							$cekDetailReal= DB::connection('omuster')->table($config["head_tab_detil"])->where($findFinishflReal)->get();
							$jumlahDet 	  = count($cekDetail);
							$jumlahReal 	= count($cekDetailReal);
							// return ["Jumlah Detail"=>$jumlahDet, "Jumlah Real"=>$jumlahReal];

							if ($jumlahDet == $jumlahReal) {
								$headerUpdate = DB::connection('omuster')->table($config['head_table'])->update([$config["head_status"]=>5]);
							}
						}
						// end

						if (
							( // jika cash maka cek nota harus ada dan dibayarkan
								!empty($cekNota)
								and $cekNota->nota_status == 3
								and $cekNota->nota_paid == 'Y'
								and  $list[$config['head_paymethod']] == 1
							) or ( // jika pihutang maka tidak wajib ada NOTA
								empty($cekNota)
								and  $list[$config['head_paymethod']] == 2
							)
						) {
							$input = [
								"sceduler"=>true,
								"nota_id"=>$notaData->nota_id,
								"id"=>$list[$config['head_primery']]
							];
							$response = PlgFunctTOS::getRealPLG($input);

							$storeHistory = [
								"create_date" => \DB::raw("TO_DATE('".Carbon::now()->format('Y-m-d H:i:s')."', 'YYYY-MM-DD HH24:mi:ss')"),
								"action" => 'flagRealisationDtlRequest',
								"branch_id" => 4,
								"branch_code" => 'PLG',
								"json_request" => json_encode($input),
								"json_response" => json_encode($response),
								"create_name" => 'sceduler'
							];
							$res[] = $storeHistory;
							// static::storeHistory($storeHistory);
						}
						if ($list[$config['head_paymethod']] == 1) { // hanya utk cash
							$dtl = DB::connection('omuster')->table($config['head_tab_detil'])->where([
								$config['head_forigen'] => $list[$config['head_primery']],
							])->whereIn($config['DTL_FL_REAL'], $config['DTL_FL_REAL_S'])->get();
							if (count($dtl) == 0) {
								DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$list[$config['head_primery']])->update([$config['head_status']=>5]);
								$trackInpt = [
									"tab"=>$config['head_table'],
									"id"=>$list[$config['head_primery']],
									"update"=>[$config['head_status']=>5]
								];
								$res[] = $storeHistory;
								$storeHistory = [
									"create_date" => \DB::raw("TO_DATE('".Carbon::now()->format('Y-m-d H:i:s')."', 'YYYY-MM-DD HH24:mi:ss')"),
									"action" => 'flagRealisationHdrRequest',
									"branch_id" => 4,
									"branch_code" => 'PLG',
									"json_request" => json_encode($trackInpt),
									"json_response" => json_encode($trackInpt),
									"create_name" => 'sceduler'
								];
								// static::storeHistory($storeHistory);
							}
						}
					}
				}
				$nota_id_old = $notaData->nota_id;
			}
			return $res;
		}

		public static function storeHistory($inp){
			DB::connection('omuster')->table('TH_LOGS_API_STORE')->insert($inp);
		}
	// PLG
}
