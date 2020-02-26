<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\PlgContHist;
use App\Helper\PlgConnectedExternalApps;

class PlgFunctTOS{
	private static function jsonGetTOS($base64){
		return '
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
			            "request": "'.$base64.'"
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
	}

	public static function decodeResultAftrSendToTosNPKS($res, $type){
		$res['request']['json'] = json_decode($res['request']['json'], true);
		$res['request']['json'][$type.'Request']['esbBody']['request'] = json_decode(base64_decode($res['request']['json'][$type.'Request']['esbBody']['request']),true);
        $res['response'][$type.'Response']['esbBody']['result'] = json_decode($res['response'][$type.'Response']['esbBody']['result'],true);
        $res['response'][$type.'Response']['esbBody']['result']['result'] = json_decode(base64_decode($res['response'][$type.'Response']['esbBody']['result']['result']),true);
        $res['result'] = $res['response'][$type.'Response']['esbBody']['result']['result'];
        return $res;
	}

	public static function sendRequestBookingPLG($arr){
    	$in_array = [
    		'TX_HDR_CANCELLED',
    		'TX_HDR_REC',
    		'TX_HDR_DEL',
    		'TX_HDR_STUFF',
    		'TX_HDR_STRIPP',
    		'TX_HDR_FUMI',
    		'TX_HDR_PLUG',
    		'TX_HDR_REC_CARGO',
    		'TX_HDR_DEL_CARGO',
    		'TX_HDR_TL'
    	];
    	if (!in_array($arr['table'], $in_array)) {
    		$res = [
    			'Success' => false,
    			'note' => 'function bulid json send request, not available!'
    		];
    	}else{
	        $toFunct = 'buildJson'.$arr['table'];
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
	        $opt = [
	        	"user" => config('endpoint.tosPostPLG.user'),
	        	"pass" => config('endpoint.tosPostPLG.pass'),
	        	"target" => config('endpoint.tosPostPLG.target'),
	        	"json" => json_encode(json_decode($json,true))
	        ];
	        $res = PlgConnectedExternalApps::sendRequestToExtJsonMet($opt);
	        $res = static::decodeResultAftrSendToTosNPKS($res, 'repoPost');
			// Simpan ke TX_SERVICES error lit ini
			// PlgConnectedExternalApps::storeTxServices($json,json_decode($json,true)["repoPostRequest"]["esbBody"]["request"],$res["result"]["result"]);

    	}
        return ['sendRequestBookingPLG' => $res];
	}

	public static function getRealPLG($input){
		$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
		$config = json_decode($config->api_set, true);
		$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->first();
		$find = (array)$find;
		$dtlLoop = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $input['id'])->whereIn($config['DTL_FL_REAL'], $config['DTL_FL_REAL_S']);
		if (!empty($config['DTL_IS_ACTIVE'])) {
			$dtlLoop = $dtlLoop->where($config['DTL_IS_ACTIVE'],'Y');
		}
		$dtlLoop = $dtlLoop->get();
		$his_cont = [];
		$Success = true;
		$msg = 'Success get realisasion';
		if (count($dtlLoop) > 0) {
			$arr = static::getRealJsonPLG($find,$dtlLoop,$config);
			$res = PlgConnectedExternalApps::sendRequestToExtJsonMet($arr);
			$res = static::decodeResultAftrSendToTosNPKS($res, 'repoGet');
			if ($res['result']['count'] == 0) {
				$Success = false;
				$msg = 'realisasion not finish';
			}else{
				$his_cont = static::storeRealPLG($res['result']['result'],$find,$config,$input);
			}
		}
		$res['his_cont'] = $his_cont;
		$dtl = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $input['id']);
		if (!empty($config['DTL_IS_ACTIVE'])) {
			$dtl = $dtl->where($config['DTL_IS_ACTIVE'],'Y');
		}
		$dtl = $dtl->get();
        return [
        	'response' => $Success,
        	'result' => $msg,
        	'no_req' =>$find[$config['head_no']],
        	'hdr' =>$find,
        	'dtl' => $dtl,
        	$config['funct_REAL_GET'] => $res
        ];
	}

	private static function storeRealPLG($data,$hdr,$config,$input){
		$his_cont = [];
		foreach ($data as $listR) {
			$funfun = $config['funct_REAL_STR'];
			$real_value = static::$funfun($listR,$hdr,$config,$input);
			$upSttDtl = [
				$config['DTL_FL_REAL']=>$real_value['real_val']
			];
			DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $hdr[$config['head_primery']])->where($config['DTL_BL'], $listR['NO_CONTAINER'])->update($upSttDtl);

			$findTsCont = [
				'cont_no' => $listR['NO_CONTAINER'],
				'branch_id' => $hdr[$config['head_branch']],
				'branch_code' => $hdr[$config['head_branch_code']]
			];
			$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->first();
			$cont_counter = $cekTsCont->cont_counter;
			if ($config['kegiatan_real'] == 3) { //kusus gate in
				$cont_counter++;
			}
			$arrStoreTsContAndTxHisCont = [
				'history_date' => date('Y-m-d h:i:s', strtotime($real_value['real_date'])),
				'cont_no' => $listR['NO_CONTAINER'],
				'branch_id' => $hdr[$config['head_branch']],
				'branch_code' => $hdr[$config['head_branch_code']],
				'cont_location' => $config['cont_loc_on_real'],
				'cont_size' => null,
				'cont_type' => null,
				'cont_counter' => $cont_counter,
				'no_request' => $listR['NO_REQUEST'],
				'kegiatan' => $config['kegiatan_real'],
				'id_user' => "1",
				'status_cont' => $listR['STATUS'],
				'vvd_id' => $hdr[$config['head_vvd']]
			];
			if (!empty($input["user"])) {
				$arrStoreTsContAndTxHisCont['id_user'] = $input["user"]->user_id;
			}
			$his_cont[] = PlgContHist::storeTsContAndTxHisCont($arrStoreTsContAndTxHisCont);
		}
		return $his_cont;
	}

	public static function getRealJsonPLG($find,$dtlLoop,$config){
		$dtl = '';
		$arrdtl = [];
		foreach ($dtlLoop as $list) {
			$list = (array)$list;
			$dtl .= '
			{
				"NO_CONTAINER": "'.$list[$config['DTL_BL']].'",
				"NO_REQUEST": "'.$find[$config['head_no']].'",
				"BRANCH_ID": "'.$find[$config['head_branch']].'"
			},';
		}
		$dtl = substr($dtl, 0,-1);
		$json = '
		{
			"action" : "'.$config['funct_REAL_GET'].'",
			"data": ['.$dtl.']
		}';
		$json = base64_encode(json_encode(json_decode($json,true)));
		$json = static::jsonGetTOS($json);
        $json = json_encode(json_decode($json,true));
		return $arr = [
        	"user" => config('endpoint.tosGetPLG.user'),
        	"pass" => config('endpoint.tosGetPLG.pass'),
        	"target" => config('endpoint.tosGetPLG.target'),
        	"json" => $json
        ];
	}

	public static function storeGATI($listR,$hdr,$config,$input){
		$findGATI = [
			'GATEIN_CONT' => $listR['NO_CONTAINER'],
			'GATEIN_REQ_NO' => $listR['NO_REQUEST'],
			'GATEIN_BRANCH_ID' => $hdr[$config['head_branch']],
			'GATEIN_BRANCH_CODE' => $hdr[$config['head_branch_code']]
		];
		$cek = DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->first();
		$datenow    = Carbon::now()->format('Y-m-d');
		$storeGATI = [
			"gatein_cont" => $listR['NO_CONTAINER'],
			"gatein_req_no" => $listR['NO_REQUEST'],
			"gatein_pol_no" => $listR['NOPOL'],
			"gatein_cont_status" => $listR['STATUS'],
			"gatein_date" => date('Y-m-d h:i:s', strtotime($listR['TGL_IN'])),
			"gatein_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')"),
			"gatein_create_by" => "1",
			"gatein_branch_id" => $hdr[$config['head_branch']],
			"gatein_branch_code" => $hdr[$config['head_branch_code']]
		];
		if (!empty($input["user"])) {
			$storeGATI['gatein_create_by'] = $input["user"]->user_id;
		}
		if (empty($cek)) {
			DB::connection('omuster')->table('TX_GATEIN')->insert($storeGATI);
		}else{
			DB::connection('omuster')->table('TX_GATEIN')->where($findGATI)->update($storeGATI);
		}

		return ["real_val" => $config['DTL_FL_REAL_V'], "real_date" => $listR['TGL_IN']];
	}

	public static function storeGATO($listR,$hdr,$config,$input){
		$findGATO = [
			'GATEOUT_CONT' 					=> $listR['NO_CONTAINER'],
			'GATEOUT_REQ_NO' 				=> $listR['NO_REQUEST'],
			'GATEOUT_BRANCH_ID' 		=> $hdr[$config['head_branch']],
			'GATEOUT_BRANCH_CODE' 	=> $hdr[$config['head_branch_code']]
		];

		$cek 		= DB::connection('omuster')->table('TX_GATEOUT')->where($findGATO)->first();
		$datenow    = Carbon::now()->format('Y-m-d');
		$storeGATO  = [
			"gateout_cont" 			 	=> $listR['NO_CONTAINER'],
			"gateout_req_no" 		 	=> $listR['NO_REQUEST'],
			"gateout_pol_no" 		 	=> $listR['NOPOL'],
			"gateout_cont_status" => $listR['STATUS'],
			"gateout_date" 				=> date('Y-m-d h:i:s', strtotime($listR['TGL_OUT'])),
			"gateout_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')"),
			"gateout_create_by" 	=> 1,
			"gateout_branch_id" 	=> $hdr[$config['head_branch']],
			"gateout_branch_code" => $hdr[$config['head_branch_code']]
		];

		if (!empty($input["user"])) {
			$storeGATI['gateout_create_by'] = $input["user"]->user_id;
		}

		if (empty($cek)) {
			DB::connection('omuster')->table('TX_GATEOUT')->insert($storeGATO);
		} else {
			DB::connection('omuster')->table('TX_GATEOUT')->where($findGATO)->update($storeGATO);
		}

		DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $hdr[$config['head_primery']])->where($config['DTL_BL'], $listR['NO_CONTAINER'])->update([
			$config['DTL_REAL_DATE'] =>date('Y-m-d h:i:s', strtotime($listR['TGL_OUT']))
		]);

		return ["real_val" => $config['DTL_FL_REAL_V'], "real_date" => $listR['TGL_OUT']];
	}

	public static function storeRealDate($listR,$hdr,$config,$input){
		DB::connection('omuster')->table($config['head_tab_detil'])->where([
			$config['head_forigen'] => $hdr[$config['head_primery']],
			$config['DTL_BL'] => $listR['NO_CONTAINER']
		])->update([
			$config['DTL_REAL_DATE']['uster'] =>date('Y-m-d h:i:s', strtotime($listR[$config['DTL_REAL_DATE']['tos']]))
		]);

		return ["real_val" => $config['DTL_FL_REAL_V'], "real_date" => $listR[$config['DTL_REAL_DATE']['tos']]];
	}

	public static function storeRealDateSE($listR,$hdr,$config,$input){
		if ($listR["STATUS"] == 1) {
			$ret_val =  $config['DTL_FL_REAL_V'][0];
			$ret_date = $listR[$config['DTL_REAL_DATE']['date']];
			$up = [ $config['DTL_REAL_DATE']['usterStart'] => date('Y-m-d h:i:s', strtotime($ret_date)) ];
		}else{
			$ret_val = $config['DTL_FL_REAL_V'][1];
			$ret_date = $listR[$config['DTL_REAL_DATE']['date']];
			$up = [ $config['DTL_REAL_DATE']['usterEnd'] => date('Y-m-d h:i:s', strtotime($ret_date)) ];
		}

		DB::connection('omuster')->table($config['head_tab_detil'])->where([
			$config['head_forigen'] => $hdr[$config['head_primery']],
			$config['DTL_BL'] => $listR['NO_CONTAINER']
		])->update($up);

		return ["real_val" => $ret_val, "real_date" => $ret_date];
	}

	public static function storeRealDateRec($listR,$hdr,$config,$input) {
		$recBrgJml 									= $listR["JUMLAH"];
		$findDtlRecBrg 							= [
			"REC_CARGO_HDR_ID"				=> $hdr[$config["head_primery"]],
			"REC_CARGO_DTL_SI_NO"			=> $listR["NO_CONTAINER"]
			];

		$updateVal 						 			= [
			"REC_CARGO_DTL_REAL_QTY"	=>$recBrgJml,
			"REC_CARGO_REMAINING_QTY"	=>$recBrgJml
		];

		$dataDetail 								= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update($updateVal);
		$dataDetail 								= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->first();
		$qty 												= $dataDetail->rec_cargo_dtl_qty;
		$qtyReal 										= $dataDetail->rec_cargo_dtl_real_qty;

		if ($qty <= $qtyReal) {
			$updateFlReal 			= DB::connection('omuster')->table('TX_DTL_REC_CARGO')->where($findDtlRecBrg)->update(["REC_CARGO_FL_REAL"=>$config["DTL_FL_REAL_V"]]);
		}
	}

	public static function storeRealDateDel($listR,$hdr,$config,$input) {
		$delBrgJml 									= $listR["JUMLAH"];
		$findDtlRecBrg 							= [
			"REC_CARGO_HDR_ID"				=> $hdr[$config["head_primery"]],
			"REC_CARGO_DTL_SI_NO"			=> $listR["NO_CONTAINER"]
			];

		$updateVal 						 			= [
			"DEL_CARGO_DTL_REAL_QTY"	=>$delBrgJml
		];

		$dataDetail 								= DB::connection('omuster')->table('TX_DTL_DEL_CARGO')->where($findDtlRecBrg)->update($updateVal);
		$dataDetail 								= DB::connection('omuster')->table('TX_DTL_DEL_CARGO')->where($findDtlRecBrg)->first();
		$qty 												= $dataDetail->del_cargo_dtl_qty;
		$qtyReal 										= $dataDetail->del_cargo_dtl_real_qty;

		if ($qty <= $qtyReal) {
			$updateFlReal 			= DB::connection('omuster')->table('TX_DTL_DEL_CARGO')->where($findDtlRecBrg)->update(["DEL_CARGO_FL_REAL"=>$config["DTL_FL_REAL_V"]]);
		}
	}

	// store request data to tos

		private static function duplicateAndStoreToRec($head,$dtls,$arr){
			$storeRecDtl = '';
			foreach ($dtls as $dtl) {
                                $dtl = (array)$dtl;
				$storeRecDtl .= '
				{
					"rec_dtl_id": null,
					"rec_hdr_id": null,
					"rec_dtl_owner": "'.$dtl[$arr['config']['DTL_OWNER']].'",
					"rec_dtl_owner_name": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
					"rec_dtl_cont": "'.$dtl[$arr['config']['DTL_BL']].'",
					"rec_dtl_cont_size": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
					"rec_dtl_cont_type": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
					"rec_dtl_cont_status": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
					"rec_dtl_cont_danger": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
					"rec_dtl_via": "'.$dtl[$arr['config']['DTL_VIA']['rec']].'",
					"rec_dtl_via_name": "'.$dtl[$arr['config']['DTL_VIA_NAME']['rec']].'",
					"rec_dtl_cmdty_id": null,
					"rec_dtl_cmdty_name": "",
					"rec_dtl_date_plan": "'.$dtl[$arr['config']['DTL_DATE_REC']].'",
				},
				';
			}
	        $storeRecDtl = substr($storeRecDtl, 0,-1);
			$storeRecHead = '
				{
					"REC_ID": "",
					"REC_NO": "'.$head[$arr['config']['head_no']].'",
					"REC_DATE": "'.$head[$arr['config']['head_date']].'",
					"REC_PAYMETHOD": "'.$head[$arr['config']['head_paymethod']].'",
					"REC_CUST_ID": "'.$head[$arr['config']['head_cust']].'",
					"REC_CUST_NAME": "'.$head[$arr['config']['head_cust_name']].'",
					"REC_CUST_NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
					"REC_CUST_ACCOUNT": null,
					"REC_STACKBY_ID": "'.$head[$arr['config']['head_shipping_agent_id']].'",
					"REC_STACKBY_NAME": "'.$head[$arr['config']['head_shipping_agent_name']].'",
					"REC_VESSEL_CODE": "'.$head[$arr['config']['head_vessel_code']].'",
					"REC_VESSEL_NAME": "'.$head[$arr['config']['head_vessel_name']].'",
					"REC_VOYIN": "'.$head[$arr['config']['head_vin']].'",
					"REC_VOYOUT": "'.$head[$arr['config']['head_vout']].'",
					"REC_VVD_ID": "'.$head[$arr['config']['head_vvd']].'",
					"REC_VESSEL_ETA": "'.$head[$arr['config']['head_vessel_eta']].'",
					"REC_VESSEL_ETD": "'.$head[$arr['config']['head_vessel_etd']].'",
					"REC_BRANCH_ID": "'.$head[$arr['config']['head_branch']].'",
					"REC_NOTA": "'.$head[$arr['config']['head_nota']].'",
					"REC_FROM": "'.$head[$arr['config']['head_from']].'",
					"REC_CREATE_BY": "'.$head[$arr['config']['head_by']].'",
					"REC_STATUS": 10,
					"REC_VESSEL_AGENT": "",
					"REC_VESSEL_AGENT_NAME": "",
					"REC_CUST_ADDRESS": "'.$head[$arr['config']['head_cust_addr']].'",
					"REC_BRANCH_CODE": "'.$head[$arr['config']['head_branch_code']].'",
					"REC_PBM_ID": "'.$head[$arr['config']['head_pbm_id']].'",
					"REC_PBM_NAME": "'.$head[$arr['config']['head_pbm_name']].'",
					"REC_VESSEL_PKK": "'.$head[$arr['config']['head_pbm_id']].'"
				}
	        	';
	        $json = '{
	        	        		"action": "saveheaderdetail",
	        	        		"data": [
	        	        			"HEADER",
	        	        			"DETAIL"
	        	        		],
	        	        		"HEADER": {
	        	        			"DB": "omuster",
	        	        			"TABLE": "TX_HDR_REC",
	        	        			"PK": "REC_ID",
	        	        			"VALUE": ['.$storeRecHead.']
	        	        		},
	        	        		"DETAIL": {
	        	        			"DB": "omuster",
	        	        			"TABLE": "TX_DTL_REC",
	        	        			"FK": [
	        	        				"rec_hdr_id",
	        	        				"rec_id"
	        	        			],
	        	        			"VALUE": ['.$storeRecDtl.']
	        	        		}
	        	        	}';
	        $arr = json_decode($json,true);
	        return GlobalHelper::saveheaderdetail($arr);
		}

		private static function getHeadFromTmReff10($head,$arr){
			return DB::connection('omuster')->table('TM_REFF')->where([
	          'reff_tr_id' => 5,
	          'reff_id' => $head[$arr['config']['head_from']]
	        ])->first();
		}

		private static function buildJsonTX_HDR_CANCELLED($arr) {
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id', $arr['id'])->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
	          $arrdetil .= '{
	            "REQ_DTL_CONT": "'.$dtl['cancl_cont'].'",
	            "REQ_DTL_SI": "'.$dtl['cancl_si'].'",
	            "REQ_DTL_COMMODITY": "'.$dtl['cancl_cmdty_id'].'",
	            "REQ_DTL_PKG": "'.$dtl['cancl_pkg_id'].'",
	            "REQ_DTL_PKG_PARENT": "'.$dtl['cancl_pkg_parent_id'].'",
	            "REQ_DTL_UNIT": "'.$dtl['cancl_unit_id'].'",
	            "REQ_DTL_QTY": "'.$dtl['cancl_qty'].'"
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $head = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id', $arr['id'])->first();
	        $head = (array)$head;
	        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head['cancelled_no'])->first();
	        $nota_no = null;
	        $nota_date = null;
	        $nota_paid_date = null;
	        if (!empty($nota)) {
	        	$nota_no = $nota->nota_no;
	        	$nota_date = date('m/d/Y', strtotime($nota->nota_date));
	        	$nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
	        }
	        return $json_body = '{
	          "action" : "getCancelledReq",
	          "header": {
	            "REQ_NO": "'.$head['cancelled_no'].'",
	            "REQ_RECEIVING_DATE": "'.date('m/d/Y', strtotime($head['cancelled_create_date'])).'",
	            "NO_NOTA": "'.$nota_no.'",
	            "TGL_NOTA": "'.$nota_date.'",
	            "REQ_MARK": "",
	            "BRANCH_ID" : "'.$head['cancelled_branch_id'].'"
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_REC($arr) {
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
	        $dr = static::getHeadFromTmReff10($head,$arr);
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
	            "RECEIVING_DARI": "'.$dr->reff_name.'",
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
	            "REQ_DTL_DEL_DATE": "'.date('m/d/Y', strtotime($dtl[$arr['config']['DTL_DATE_HIS_CONT']])).'",
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
	            "DELIVERY_KE": "'.$rec_dr->reff_name.'",
	            "TANGGAL_LUNAS": "'.$nota_paid_date.'",
	            "PERP_DARI": "",
	            "PERP_KE": "",
							"BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_STUFF($arr){
			if (!is_array($arr['config']['kegiatan']) and $arr['config']['kegiatan'] == 5) {
	        	$actionJ = 'getStuffing';
	        }else{
	        	if ($arr['config']['kegiatan'] == [3,5]) {
	        		$actionJ = 'getRecStuffing';
	        	}else{
	        		$actionJ = 'getRecStuffingDel';
	        	}
	        }
	        $arrdetil = '';
					$head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
	        $head = (array)$head;
					$contFromName = DB::connection('omuster')
											->table($arr['config']['head_table']." A")
											->join("TM_REFF B", "A.".$arr['config']['head_from'], '=', 'B.REFF_ID')
											->where("B.REFF_TR_ID", "5")
											->first();
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
	          $arrdetil .= '{
	          	"REQ_DTL_OWNER_CODE": "'.$dtl[$arr['config']['DTL_OWNER']].'",
	            "REQ_DTL_OWNER_NAME": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
	            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
	            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
	            "REQ_DTL_REMARK_SP2": "",
	            "REQ_DTL_ORIGIN": "'.$contFromName->reff_name.'",
	            "REQ_DTL_VIA": "'.$dtl[$arr['config']['DTL_VIA_NAME']['rec']].'",
	            "REQ_DTL_DEL_VIA": "'.$dtl[$arr['config']['DTL_VIA_NAME']['del']].'",
	            "TGL_MULAI": "'.date('m/d/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_START_DATE']])).'",
	            "TGL_SELESAI": "'.date('m/d/Y h:i:s', strtotime($dtl[$arr['config']['DTL_DATE_END_DATE']])).'"
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no', $head[$arr['config']['head_no']])->first();
	        $nota_no = null;
	        $nota_date = null;
	        $nota_paid_date = null;
	        if (!empty($nota)) {
	        	$nota_no = $nota->nota_no;
	        	$nota_date = date('m/d/Y', strtotime($nota->nota_date));
	        	$nota_paid_date = date('m/d/Y', strtotime($nota->nota_paid_date));
	        }
	        $dr = static::getHeadFromTmReff10($head,$arr);
	        if (in_array($actionJ, ['getRecStuffing','getRecStuffingDel'])) {
	        	static::duplicateAndStoreToRec($head,$dtls,$arr);
	        }
	        return $json_body = '{
	          "action" : "'.$actionJ.'",
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
	            "STUFFING_DARI": "'.$dr->reff_name.'",
	            "RECEIVING_DARI": "'.$dr->reff_name.'",
	            "PERP_DARI": "'.$head[$arr['config']['head_ext_from']].'",
	            "PERP_KE": "'.$head[$arr['config']['head_ext_loop']].'",
	            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'",
	            "DI" : ""
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_STRIPP($arr){
	        if (!is_array($arr['config']['kegiatan']) and $arr['config']['kegiatan'] == 6) {
	        	$actionJ = 'getStripping';
	        }else{
	        	if ($arr['config']['kegiatan'] == [3,6]) {
	        		$actionJ = 'getRecStripping';
	        	}
	        }
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
	          $arrdetil .= '{
	          	"REQ_DTL_OWNER_CODE": "'.$dtl[$arr['config']['DTL_OWNER']].'",
	            "REQ_DTL_OWNER_NAME": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
	            "REQ_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "REQ_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "REQ_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "REQ_DTL_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "REQ_DTL_TYPE": "'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
	            "REQ_DTL_VIA": "'.$dtl[$arr['config']['DTL_VIA_NAME']['rec']].'",
	            "REQ_DTL_CONT_HAZARD": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
	            "REQ_DTL_ORIGIN": "'.$dtl[$arr['config']['DTL_CONT_FROM']].'",
	            "TGL_MULAI": "'.date('m/d/Y', strtotime($dtl[$arr['config']['DTL_DATE_START_DATE']])).'",
	            "TGL_SELESAI": "'.date('m/d/Y', strtotime($dtl[$arr['config']['DTL_DATE_END_DATE']])).'"
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
	        if (in_array($actionJ, ['getRecStripping'])) {
	        	static::duplicateAndStoreToRec($head,$dtls,$arr);
	        }
	        return $json_body = '{
	          "action" : "'.$actionJ.'",
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
	            "RECEIVING_DARI": "'.$rec_dr->reff_name.'",
	            "PERP_DARI": "'.$head[$arr['config']['head_ext_from']].'",
	            "PERP_KE": "'.$head[$arr['config']['head_ext_loop']].'",
	            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'",
	            "DI" : ""
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_FUMI($arr){
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
		        $getCountCounter = DB::connection('omuster')->table('TS_CONTAINER')->where('cont_no',$dtl[$arr['config']['DTL_BL']])->orderBy('cont_counter','desc')->first();
	          $arrdetil .= '{
	            "FUMI_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "FUMI_DTL_CONT_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "FUMI_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "FUMI_DTL_STATUS": "0",
	            "FUMI_DTL_CANCELLED": "'.$dtl[$arr['config']['DTL_IS_CANCEL']].'",
	            "FUMI_DTL_ACTIVE": "'.$dtl[$arr['config']['DTL_IS_ACTIVE']].'",
	            "FUMI_DTL_START_FUMI_PLAN": "'.date('d-M-y', strtotime($dtl[$arr['config']['DTL_DATE_ACTIVITY']])).'",
	            "FUMI_DTL_END_FUMI_PLAN": "'.date('d-M-y', strtotime($dtl[$arr['config']['DTL_DATE_ACTIVITY']])).'",
	            "FUMI_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "FUMI_DTL_COUNTER": "'.$getCountCounter->cont_counter.'"
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
	        $head = (array)$head;

	        return $json_body = '{
	          "action" : "getFumigasi",
	          "header": {
	          	"FUMI_ID" : "",
	            "FUMI_NO": "'.$head[$arr['config']['head_no']].'",
	            "FUMI_CREATE_BY": "'.$head[$arr['config']['head_by']].'",
	            "FUMI_CREATE_DATE": "'.date('d-M-y', strtotime($head[$arr['config']['head_date']])).'",
	            "FUMI_CONSIGNEE_ID": "'.$head[$arr['config']['head_cust']].'",
	            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_PLUG($arr){
	        $arrdetil = '';
	        $dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->where($arr['config']['DTL_IS_ACTIVE'],'Y')->get();
	        foreach ($dtls as $dtl) {
	          $dtl = (array)$dtl;
		        $getCountCounter = DB::connection('omuster')->table('TS_CONTAINER')->where('cont_no',$dtl[$arr['config']['DTL_BL']])->orderBy('cont_counter','desc')->first();
	          $arrdetil .= '{
	            "PLUG_DTL_CONT": "'.$dtl[$arr['config']['DTL_BL']].'",
	            "PLUG_DTL_CONT_SIZE": "'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
	            "PLUG_DTL_CONT_STATUS": "'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
	            "PLUG_DTL_STATUS": "0",
	            "PLUG_DTL_CANCELLED": "'.$dtl[$arr['config']['DTL_IS_CANCEL']].'",
	            "PLUG_DTL_ACTIVE": "'.$dtl[$arr['config']['DTL_IS_ACTIVE']].'",
	            "PLUG_DTL_START_PLUG_PLAN": "'.date('d-M-y', strtotime($dtl[$arr['config']['DTL_DATE_ACTIVITY']])).'",
	            "PLUG_DTL_END_PLUG_PLAN": "'.date('d-M-y', strtotime($dtl[$arr['config']['DTL_DATE_ACTIVITY']])).'",
	            "PLUG_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
	            "PLUG_DTL_COUNTER": "'.$getCountCounter->cont_counter.'"
	          },';
	        }
	        $arrdetil = substr($arrdetil, 0,-1);
	        $head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
	        $head = (array)$head;

	        return $json_body = '{
	          "action" : "getPlugging",
	          "header": {
	          	"PLUG_ID" : "",
	            "PLUG_NO": "'.$head[$arr['config']['head_no']].'",
	            "PLUG_CREATE_BY": "'.$head[$arr['config']['head_by']].'",
	            "PLUG_CREATE_DATE": "'.date('d-M-y', strtotime($head[$arr['config']['head_date']])).'",
	            "PLUG_CONSIGNEE_ID": "'.$head[$arr['config']['head_cust']].'",
	            "PLUG_STATUS" : "",
	            "BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
	          },
	          "arrdetail": ['.$arrdetil.']
	        }';
		}

		private static function buildJsonTX_HDR_REC_CARGO($arr) {
					$arrdetil = '';
					$head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
					$head = (array)$head;
					$dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->get();
					foreach ($dtls as $dtl) {
						$dtl = (array)$dtl;
						$arrdetil .= '{
							"REQUEST_DTL_SI": "'.$dtl[$arr['config']['DTL_BL']].'",
							"REQUEST_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
							"REQUEST_DTL_DANGER": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
							"REQUEST_DTL_VOY": "",
							"REQUEST_DTL_VESSEL_NAME": "'.$head[$arr['config']['head_vessel_name']].'",
							"REQUEST_DTL__VESSEL_CODE": "",
							"REQUEST_DTL_CALL_SIGN": "",
							"REQUEST_DTL_DEST_DEPO": "",
							"REQUEST_DTL_STATUS": "0",
							"REQUEST_DTL_OWNER_CODE": "'.$dtl[$arr['config']['DTL_OWNER']].'",
							"REQUEST_DTL_OWNER_NAME": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
							"REQUEST_DTL_TOTAL": "'.$dtl[$arr['config']['DTL_QTY']].'",
							"REQUEST_DTL_UNIT": "'.$dtl[$arr['config']['DTL_UNIT_NAME']].'"
						},';
					}
					$arrdetil = substr($arrdetil, 0,-1);
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
						"action" : "getReceivingBrg",
						"header": {
							"REQUEST_NO": "'.$head[$arr['config']['head_no']].'",
							"REQUEST_CONSIGNEE_ID": "'.$head[$arr['config']['head_cust']].'",
							"REQUEST_MARK": "'.$head[$arr['config']['head_mark']].'",
							"REQUEST_CREATE_DATE": "'.date('d-M-y').'",
							"REQUEST_CREATE_BY": "'.$head[$arr['config']['head_by']].'",
							"REQUEST_NOTA": "'.$nota_no.'",
							"REQUEST_NO_TPK": "",
							"REQUEST_DO_NO": "",
							"REQUEST_BL_NO": "",
							"REQUEST_SPPB_NO": "",
							"REQUEST_SPPB_DATE": "",
							"REQUEST_RECEIVING_DATE": "'.date('d-M-y', strtotime($head[$arr['config']['head_date']])).'",
							"REQUEST_NOTA_DATE": "'.date('d-M-y', strtotime($nota_date)).'",
							"REQUEST_PAID_DATE": "'.date('d-M-y', strtotime($nota_paid_date)).'",
							"REQUEST_FROM": "'.$rec_dr->reff_name.'",
							"REQUEST_STATUS": "'.$head[$arr['config']['head_status']].'",
							"REQUEST_DI": "",
							"BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
						},
						"arrdetail": ['.$arrdetil.']
					}';
				}

		private static function buildJsonTX_HDR_DEL_CARGO($arr) {
					$arrdetil = '';
					$head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
					$head = (array)$head;
					$dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->get();
					foreach ($dtls as $dtl) {
						$dtl = (array)$dtl;
						$arrdetil .= '{
							"REQUEST_DTL_SI": "'.$dtl[$arr['config']['DTL_BL']].'",
							"REQUEST_DTL_COMMODITY": "'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
							"REQUEST_DTL_DANGER": "'.$dtl[$arr['config']['DTL_CHARACTER']].'",
							"REQUEST_DTL_VOY": "",
							"REQUEST_DTL_VESSEL_NAME": "'.$head[$arr['config']['head_vessel_name']].'",
							"REQUEST_DTL__VESSEL_CODE": "",
							"REQUEST_DTL_CALL_SIGN": "",
							"REQUEST_DTL_DEST_DEPO": "",
							"REQUEST_DTL_STATUS": "0",
							"REQUEST_DTL_OWNER_CODE": "'.$dtl[$arr['config']['DTL_OWNER']].'",
							"REQUEST_DTL_OWNER_NAME": "'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
							"REQUEST_DTL_TOTAL": "'.$dtl[$arr['config']['DTL_QTY']].'",
							"REQUEST_DTL_UNIT": "'.$dtl[$arr['config']['DTL_UNIT_NAME']].'"
						},';
					}
					$arrdetil = substr($arrdetil, 0,-1);
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
						"action" : "getDeliveryBrg",
						"header": {
							"REQUEST_NO": "'.$head[$arr['config']['head_no']].'",
							"REQUEST_CONSIGNEE_ID": "'.$head[$arr['config']['head_cust']].'",
							"REQUEST_MARK": "'.$head[$arr['config']['head_mark']].'",
							"REQUEST_CREATE_DATE": "'.date('d-M-y').'",
							"REQUEST_CREATE_BY": "'.$head[$arr['config']['head_by']].'",
							"REQUEST_NOTA": "'.$nota_no.'",
							"REQUEST_NO_TPK": "",
							"REQUEST_DO_NO": "",
							"REQUEST_BL_NO": "",
							"REQUEST_SPPB_NO": "",
							"REQUEST_SPPB_DATE": "",
							"REQUEST_RECEIVING_DATE": "'.date('d-M-y', strtotime($head[$arr['config']['head_date']])).'",
							"REQUEST_NOTA_DATE": "'.date('d-M-y', strtotime($nota_date)).'",
							"REQUEST_PAID_DATE": "'.date('d-M-y', strtotime($nota_paid_date)).'",
							"REQUEST_FROM": "'.$rec_dr->reff_name.'",
							"REQUEST_STATUS": "'.$head[$arr['config']['head_status']].'",
							"REQUEST_DI": "",
							"BRANCH_ID" : "'.$head[$arr['config']['head_branch']].'"
						},
						"arrdetail": ['.$arrdetil.']
					}';
				}

		private static function buildJsonTX_HDR_TL($arr) {
					$arrdetil = '';
					$head = DB::connection('omuster')->table($arr['config']['head_table'])->where($arr['config']['head_primery'], $arr['id'])->first();
					$head = (array)$head;
					$dtls = DB::connection('omuster')->table($arr['config']['head_tab_detil'])->where($arr['config']['head_forigen'], $arr['id'])->get();
					foreach ($dtls as $dtl) {
						$dtl = (array)$dtl;
						$arrdetil .= '{
							"TL_DTL_ID" : "'.$dtl[$arr['config']['DTL_ID']].'",
					        "TL_HDR_ID": "'.$dtl[$arr['config']['head_forigen']].'",
					        "TL_DTL_CONT":"'.$dtl[$arr['config']['DTL_BL']].'",
					        "TL_DTL_CONT_SIZE":"'.$dtl[$arr['config']['DTL_CONT_SIZE']].'",
					        "TL_DTL_CONT_TYPE":"'.$dtl[$arr['config']['DTL_CONT_TYPE']].'",
					        "TL_DTL_CONT_STATUS":"'.$dtl[$arr['config']['DTL_CONT_STATUS']].'",
					        "TL_DTL_CONT_DANGER"  :"'.$dtl[$arr['config']['DTL_CHARACTER']].'",
					        "TL_DTL_CMDTY_ID" :"'.$dtl[$arr['config']['DTL_CMDTY_ID']].'",
					        "TL_DTL_CMDTY_NAME" :"'.$dtl[$arr['config']['DTL_CMDTY_NAME']].'",
					        "TL_DTL_REC_VIA" :"'.$dtl[$arr['config']['rec']].'",
					        "TL_DTL_ACTIVITY_DATE" :"'.$dtl[$arr['config']['TL_DTL_ACTIVITY_DATE']].'",
					        "TL_DTL_OWNER" :"'.$dtl[$arr['config']['DTL_OWNER']].'",
					        "TL_DTL_OWNER_NAME" :"'.$dtl[$arr['config']['DTL_OWNER_NAME']].'",
					        "TL_DTL_VIA_REC_NAME" :"'.$dtl[$arr['config']['DTL_VIA_REC_NAME']].'",
					        "TL_DTL_SI_NO" :"'.$dtl[$arr['config']['DTL_SI_NO']].'",
					        "TL_DTL_QTY" : "'.$dtl[$arr['config']['DTL_QTY']].'",
					        "TL_DTL_CHARACTER_ID" :"'.$dtl[$arr['config']['DTL_CHARACTER_ID']].'",
					        "TL_DTL_CHARACTER_NAME" :"'.$dtl[$arr['config']['DTL_CHARACTER_NAME']].'",
					        "TL_DTL_PKG_ID" :"'.$dtl[$arr['config']['DTL_PKG_ID']].'",
					        "TL_DTL_PKG_NAME" :"'.$dtl[$arr['config']['DTL_PKG_NAME']].'",
					        "TL_DTL_UNIT_ID" :"'.$dtl[$arr['config']['DTL_UNIT_ID']].'",
					        "TL_DTL_UNIT_NAME" :"'.$dtl[$arr['config']['DTL_UNIT_NAME']].'",
					        "TL_DTL_DEL_VIA" :"'.$dtl[$arr['config']['del']].'",
					        "TL_DTL_DEL_VIA_NAME" :"'.$dtl[$arr['config']['DTL_DEL_VIA_NAME']].'",
					        "TL_DTL_ISACTIVE" :"'.$dtl[$arr['config']['DTL_IS_ACTIVE']].'",
					        "TL_DTL_REC_DATE" :"'.$dtl[$arr['config']['DTL_DATE_IN']].'",
					        "TL_DTL_DEL_DATE" :"'.$dtl[$arr['config']['DTL_DATE_OUT']].'",
					        "TL_DTL_IS_TL" :"'.$dtl[$arr['config']['DTL_TL']].'",
					        "TL_DTL_ISCANCELLED" : "'.$dtl[$arr['config']['DTL_ISCANCELLED']].'",
					        "TL_DTL_REAL_REC_DATE" : "'.$dtl[$arr['config']['DTL_REAL_REC_DATE']].'",
					        "TL_DTL_FL_REAL":"'.$dtl[$arr['config']['DTL_FL_REAL']].'",
					        "TL_DTL_REAL_DEL_DATE" :"'.$dtl[$arr['config']['DTL_REAL_DEL_DATE']].'"
						},';
					}
					$arrdetil = substr($arrdetil, 0,-1);
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
						"action" : "getTL",
						"header": {
							"TL_ID" : "'.$head[$arr['config']['head_primery']].'",
					        "TL_NO" : "'.$head[$arr['config']['head_no']].'",
					        "TL_DATE" : "'.$head[$arr['config']['head_date']].'",
					        "TL_PAYMETHOD":"'.$head[$arr['config']['head_paymethod']].'",
					        "TL_CUST_ID": "'.$head[$arr['config']['head_cust']].'",
					        "TL_CUST_NAME":"'.$head[$arr['config']['head_cust_name']].'",
					        "TL_CUST_ADDRESS":"'.$head[$arr['config']['head_cust_addr']].'",
					        "TL_CUST_NPWP": "'.$head[$arr['config']['head_cust_npwp']].'",
					        "TL_CUST_ACCOUNT": "'.$head[$arr['config']['head_cust']].'",
					        "TL_STACKBY_ID": "'.$head[$arr['config']['head_shipping_agent_id']].'",
					        "TL_STACKBY_NAME": "'.$head[$arr['config']['head_shipping_agent_name']].'",
					        "TL_VESSEL_CODE":"'.$head[$arr['config']['head_vessel_code']].'",
					        "TL_VESSEL_NAME":"'.$head[$arr['config']['head_vessel_name']].'",
					        "TL_VOYIN":"'.$head[$arr['config']['head_voyin']].'",
					        "TL_VOYOUT":"'.$head[$arr['config']['head_voyout']].'",
					        "TL_VVD_ID":"'.$head[$arr['config']['head_vvd']].'",
					        "TL_POL":"'.$head[$arr['config']['head_pol']].'",
					        "TL_POD":"'.$head[$arr['config']['head_pod']].'",
					        "TL_BRANCH_ID":"'.$head[$arr['config']['head_branch']].'",
					        "TL_NOTA":"'.$head[$arr['config']['head_nota']].'",
					        "TL_CORRECTION":"'.$head[$arr['config']['head_correction']].'",
					        "TL_CORRECTION_DATE":"'.$head[$arr['config']['head_correction_date']].'",
					        "TL_PRINT_CARD":"'.$head[$arr['config']['head_print_card']].'",
					        "TL_FROM":"'.$head[$arr['config']['head_from']].'",
					        "TL_TO":"'.$head[$arr['config']['head_to']].'",
					        "TL_VESSEL_AGENT" : "'.$head[$arr['config']['head_vessel_agent']].'"
					        "TL_VESSEL_AGENT_NAME":"'.$head[$arr['config']['head_vessel_agent_name']].'",
					        "TL_CREATE_DATE":"'.$head[$arr['config']['head_date']].'",
					        "TL_CREATE_BY":"'.$head[$arr['config']['head_by']].'",
					        "TL_STATUS":"'.$head[$arr['config']['head_status']].'",
					        "TL_PBM_ID":"'.$head[$arr['config']['head_pbm_id']].'",
					        "TL_PBM_NAME":"'.$head[$arr['config']['head_pbm_name']].'",
					        "TL_VESSEL_PKK":"'.$head[$arr['config']['head_vessel_pkk']].'",
					        "TL_BRANCH_CODE":"'.$head[$arr['config']['head_branch_code']].'",
					        "TL_BTL_STATUS":"'.$head[$arr['config']['head_btl_status']].'",
					        "TL_BTL_FROM_ID":"'.$head[$arr['config']['head_btl_from_id']].'",
					        "TL_BTL_FROM":"'.$head[$arr['config']['head_btl_from']].'",
					        "TL_MSG":"'.$head[$arr['config']['head_mark']].'",
					        "TL_VESSEL_ETA":"'.$head[$arr['config']['head_vessel_eta']].'",
					        "TL_VESSEL_ETD":"'.$head[$arr['config']['head_vessel_etd']].'",
					        "APP_ID": "'.$head[$arr['config']['head_app_id']].'"
					},
						"arrdetail": ['.$arrdetil.']
					}';
				}
	// store request data to tos
}
