<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\PlgConnectedExternalApps;
use App\Helper\BillingEngine;
use App\Models\OmUster\TxHdrNota;
use App\Models\OmUster\TxPayment;

class PlgRequestBooking{
	// PLG
	    public static function sendRequestPLG($input){
			$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			$config = json_decode($config->api_set, true);
			$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['Success' => false, 'result' => "Fail, requst not found!"];
			}
			$find = (array)$find[0];
			if ($find[$config['head_status']] == 3) {
				return ['Success' => false, 'result' => "Fail, requst already send!"];
			}
			$pbmCek = 'N';
			// build head
				$setH = [];
				$setH['P_SOURCE_ID'] = "NPKS_BILLING";
				$setH['P_NOTA_ID'] = $input['nota_id'];
				$setH['P_BRANCH_ID'] = $find[$config['head_branch']];
				$setH['P_BRANCH_CODE'] = $find[$config['head_branch_code']];
				$setH['P_CUSTOMER_ID'] = $find[$config['head_cust']];
				$setH['P_PBM_INTERNAL'] = 'N';
				$setH['P_BOOKING_NUMBER'] = $find[$config['head_no']];
				$setH['P_REALIZATION'] = 'N';
				$setH['P_RESTITUTION'] = 'N';
				if (empty($config['p_tarde'])) {
					$setH['P_TRADE'] = 'NULL';
				}else{
					$setH['P_TRADE'] = $find[$config['p_tarde']];
				}
				$setH['P_USER_ID'] = $find[$config['head_by']];
			// build head

			// build detil
				$setD = [];
				$detil = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']])->get();
				foreach ($detil as $list) {
					$list = (array)$list;
					$newD = [];
					if (empty($config['DTL_VIA'])) {
						$newD['DTL_VIA'] = 'NULL';
					}else{
						$newD['DTL_VIA'] = empty($list[$config['DTL_VIA']]) ? 'NULL' : $list[$config['DTL_VIA']];
					}
					if (empty($config['DTL_BL'])) {
						$newD['DTL_BL'] = 'NULL';
					}else{
						$newD['DTL_BL'] = empty($list[$config['DTL_BL']]) ? 'NULL' : strtoupper($list[$config['DTL_BL']]);
					}
					$newD['DTL_PKG_ID'] = 8;
					if (empty($config['DTL_CMDTY_ID'])) {
						$newD['DTL_CMDTY_ID'] = 'NULL';
					}else{
						$newD['DTL_CMDTY_ID'] = empty($list[$config['DTL_CMDTY_ID']]) ? 'NULL' : $list[$config['DTL_CMDTY_ID']];
					}
					if (empty($config['DTL_CHARACTER'])) {
						$newD['DTL_CHARACTER'] = 'NULL';
					}else{
						if (empty($list[$config['DTL_CHARACTER']])) {
							$newD['DTL_CHARACTER'] = 'NULL';
						}else if ($list[$config['DTL_CHARACTER']] == 'Y'){
							$newD['DTL_CHARACTER'] = 2;
						}else if ($list[$config['DTL_CHARACTER']] == 'N'){
							$newD['DTL_CHARACTER'] = 0;
						}
					}
					if (empty($config['DTL_CONT_SIZE'])) {
						$newD['DTL_CONT_SIZE'] = 'NULL';
					}else{
						$newD['DTL_CONT_SIZE'] = empty($list[$config['DTL_CONT_SIZE']]) ? 'NULL' : $list[$config['DTL_CONT_SIZE']];
					}
					if (empty($config['DTL_CONT_TYPE'])) {
						$newD['DTL_CONT_TYPE'] = 'NULL';
					}else{
						$newD['DTL_CONT_TYPE'] = empty($list[$config['DTL_CONT_TYPE']]) ? 'NULL' : $list[$config['DTL_CONT_TYPE']];
					}
					if (empty($config['DTL_CONT_STATUS'])) {
						$newD['DTL_CONT_STATUS'] = 'NULL';
					}else{
						$newD['DTL_CONT_STATUS'] = empty($list[$config['DTL_CONT_STATUS']]) ? 'NULL' : $list[$config['DTL_CONT_STATUS']];
					}
					if (empty($config['DTL_UNIT_ID'])) {
						$newD['DTL_UNIT_ID'] = 'NULL';
					}else{
						$newD['DTL_UNIT_ID'] = empty($list[$config['DTL_UNIT_ID']]) ? 'NULL' : $list[$config['DTL_UNIT_ID']];
					}
					if (empty($config['DTL_QTY'])) {
						$newD['DTL_QTY'] = 'NULL';
					}else if ($config['DTL_QTY'] == 1) {
						$newD['DTL_QTY'] = 1;
					}else{
						$newD['DTL_QTY'] = empty($list[$config['DTL_QTY']]) ? 'NULL' : $list[$config['DTL_QTY']];
					}

					$getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', $input['nota_id'])->where('BRANCH_ID',$find[$config['head_branch']])->where('BRANCH_CODE',$find[$config['head_branch_code']])->where('GROUP_TARIFF_ID', 15)->count();
					if ($getPFS > 0) {
						$newD['DTL_PFS'] = 'Y';
					}else{
						$newD['DTL_PFS'] = 'N';
					}

					$newD['DTL_BM_TYPE'] = 'NULL';
					$DTL_STACK_AREA = 'NULL';
					// if (in_array($config['head_nota_id'], ["14", "15", 14, 15])) {
					// 	$DTL_STACK_AREA = empty($list['dtl_stacking_type_id']) ? 'NULL' : $list['dtl_stacking_type_id'];
					// }
					$newD['DTL_STACK_AREA'] = $DTL_STACK_AREA;

					if (empty($config['DTL_TL'])) {
						$newD['DTL_TL'] = 'NULL';
					}else{
						$newD['DTL_TL'] = empty($list[$config['DTL_TL']]) ? 'NULL' : $list[$config['DTL_TL']];
					}
					if (empty($config['DTL_DATE_IN'])) {
						$newD['DTL_DATE_IN'] = 'NULL';
					}else{
						$newD['DTL_DATE_IN'] = empty($list[$config['DTL_DATE_IN']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_IN']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}
					if (empty($config['DTL_DATE_OUT'])) {
						$newD['DTL_DATE_OUT'] = 'NULL';
					}else{
						$newD['DTL_DATE_OUT'] = empty($list[$config['DTL_DATE_OUT']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_OUT']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}
					if (empty($config['DTL_DATE_OUT_OLD'])) {
						$newD['DTL_DATE_OUT_OLD'] = 'NULL';
					}else{
						$newD['DTL_DATE_OUT_OLD'] = empty($list[$config['DTL_DATE_OUT_OLD']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_OUT_OLD']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}
					$setD[] = $newD;
				}
			// build detil

			// build eqpt
				$setE = [];
				// $eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find[$config['head_no']])->get();
				// foreach ($eqpt as $list) {
				// 	$newE = [];
				// 	$list = (array)$list;
				// 	$newE['EQ_TYPE'] = empty($list['eq_type_id']) ? 'NULL' : $list['eq_type_id'];
				// 	$newE['EQ_QTY'] = empty($list['eq_qty']) ? 'NULL' : $list['eq_qty'];
				// 	$newE['EQ_UNIT_ID'] = empty($list['eq_unit_id']) ? 'NULL' : $list['eq_unit_id'];
				// 	$newE['EQ_GTRF_ID'] = empty($list['group_tariff_id']) ? 'NULL' : $list['group_tariff_id'];
				// 	$newE['EQ_PKG_ID'] = empty($list['package_id']) ? 'NULL' : $list['package_id'];
				// 	$newE['EQ_QTY_PKG'] = empty($list['unit_qty']) ? 'NULL' : $list['unit_qty'];
				// 	$setE[] = $newE;
				// }
			// build eqpt

			// build paysplit
				$setP = [];
				// if ($find[$config['head_split']] == 'Y') {
				// 	$paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find[$config['head_no']])->get();
				// 	$paysplit = (array)$paysplit;
				// 	foreach ($paysplit as $list) {
				// 		$newP = [];
				// 		$list = (array)$list;
				// 		$newP['PS_CUST_ID'] = $list['cust_id'];
				// 		$newP['PS_GTRF_ID'] = $list['group_tarif_id'];
				// 		$setP[] = $newP;
				// 	}
				// }
			// build paysplit

			// set data
				$set_data = [
					'head' => $setH,
					'detil' => $setD,
					'eqpt' => $setE,
					'paysplit' => $setP
				];
			// set data

			// return $tariffResp = BillingEngine::calculateTariff($set_data);
			$tariffResp = BillingEngine::calculateTariff($set_data);
			
			$insertTsContHs = [];
			$insertTxHisContHs = [];

			if ($tariffResp['result_flag'] == 'S') {
				DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 2
				]);
				foreach ($detil as $list) {
					$list = (array)$list;
					// history container
						$insertTsCont = [];
						$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where([
							'cont_no' => $list[$config['DTL_BL']],
							'branch_id' => $find[$config['head_branch']],
							'branch_code' => $find[$config['head_branch_code']]
						])->orderBy('cont_counter', 'desc')->get();
						if (count($cekTsCont) == 0) {
							$insertTsCont = [
								'cont_no' => $list[$config['DTL_BL']],
								'branch_id' => $find[$config['head_branch']],
								'branch_code' => $find[$config['head_branch_code']],
								'cont_location' => 'GATO',
								'cont_size' => $list[$config['DTL_CONT_SIZE']],
								'cont_type' => $list[$config['DTL_CONT_TYPE']],
								'cont_counter' => 0
							];
							DB::connection('omuster')->table('TS_CONTAINER')->insert($insertTsCont);
							$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where([
								'cont_no' => $list[$config['DTL_BL']],
								'branch_id' => $find[$config['head_branch']],
								'branch_code' => $find[$config['head_branch_code']]
							])->orderBy('cont_counter', 'desc')->get();
						}
						$cekTsCont = $cekTsCont[0];
						$insertTsContHs[] = $insertTsCont;
						$insertTxHisCont = [
							'no_container' => $list[$config['DTL_BL']],
							'no_request' => $find[$config['head_no']],
							'kegiatan' => $config['kegiatan'],
							'id_user' => $input["user"]->user_id,
							// 'id_yard' => $list[$config['DTL_BL']], ?
							'status_cont' => $list[$config['DTL_CONT_STATUS']],
							'vvd_id' => $find[$config['head_vvd']],
							'counter' => $cekTsCont->cont_counter+1
							// 'sub_counter' => $list[$config['DTL_BL']], ?
							// 'why' => $list[$config['DTL_BL']], ?
							// 'aktif' => $list[$config['DTL_BL']], ?
						];
						DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($insertTxHisCont);
						$insertTxHisContHs[] = $insertTxHisCont;
					// history container
				}
			}
			$tariffResp['his_cont'] = [
				'insertTsContHs' => $insertTsContHs,
				'insertTxHisContHs' => $insertTxHisContHs,
			];
			return $tariffResp;
	    }

	    public static function viewTempTariffPLG($input){
	    	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			$config = json_decode($config->api_set, true);
	    	$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
	    	if (count($find) == 0) {
	    		return ['Success' => false, 'result' => 'fail, not found data!'];
	    	}
	    	$find = (array)$find[0];

	    	$result = [];

	    	$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$find[$config['head_no']]."'";
	    	$getHS = DB::connection('eng')->select(DB::raw($query));
	    	foreach ($getHS as $getH){
	    		$comp_notas = DB::connection('mdm')->table('TM_REFF')->where([
	    			'reff_tr_id' => 10
	    		])->orderBy('reff_order', 'asc')->get();
	    		$nota_view = [];

	    		foreach ($comp_notas as $comp_nota) {
	    			$grArr = DB::connection('mdm')->table('TM_COMP_NOTA')->where([
	    				'branch_id' => $getH->branch_id,
	    				'branch_code' => $getH->branch_code,
	    				'nota_id' => $getH->nota_id,
	    				'comp_nota_view' => $comp_nota->reff_id
	    			])->pluck('group_tariff_id');

	    			$nv = [];
	    			if (count($grArr) > 0) {
	    				$queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$getH->temp_hdr_id."' AND CUSTOMER_ID = '".$getH->customer_id."'";
	    				$group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));
	    				$resultD = [];
	    				foreach ($group_tariff as $grpTrf){
	    					$grpTrf = (array)$grpTrf;
	    					if (in_array($grpTrf['group_tariff_id'], $grArr)) {
	    						$uperD = DB::connection('eng')->table('V_TX_TEMP_TARIFF_DTL_NPKS')->where('TEMP_HDR_ID',$getH->temp_hdr_id)->where('group_tariff_id',$grpTrf['group_tariff_id'])->get();
	    						$countLine = 0;
	    						foreach ($uperD as $list){
	    							$resultD[] = $list;
	    						}
	    					}
	    				}
	    				$nv[$comp_nota->reff_name] = $resultD;
	    			}
	    			if (!empty($nv)) {
	    				$nota_view[] = $nv;
	    			}
	    		}

	          // build head
	    		$head = [
	    			'uper_org_id' => $getH->branch_org_id,
	    			'uper_cust_id' => $getH->customer_id,
	    			'uper_cust_name' => $getH->alt_name,
	    			'uper_cust_npwp' => $getH->npwp,
	    			'uper_cust_address' => $getH->address,
	    			'uper_amount' => $getH->total_uper,
	    			'uper_currency_code' => $getH->currency,
	    			'uper_status' => 'P',
	                // Tambahan Mas Adi
	    			'uper_service_code' => $getH->nota_service_code,
	    			'uper_branch_account' => $getH->branch_account,
	    			'uper_context' => $getH->nota_context,
	    			'uper_sub_context' => $getH->nota_sub_context,
	                // 'uper_terminal_code' => $find[$config['head_terminal_code']],
	    			'uper_branch_id' => $getH->branch_id,
	    			'uper_branch_code' => $getH->branch_code,
	    			'uper_vessel_name' => $find[$config['head_vessel_name']],
	    			'uper_faktur_no' => '-',
	    			'uper_trade_type' => $getH->trade_type,
	    			'uper_req_no' => $getH->booking_number,
	    			'uper_ppn' => $getH->ppn_uper,
	    			'uper_percent' => $getH->percent_uper,
	    			'uper_dpp' => $getH->dpp_uper,
	    			'uper_nota_id' => $getH->nota_id,
	    			'uper_req_date' =>  $find[$config['head_date']]
	    		];
	    		if ($config['head_pbm_id'] != null) {
	    			$head['uper_pbm_id'] = $find[$config['head_pbm_id']];
	    		}
	    		if ($config['head_pbm_name'] != null) {
	    			$head['uper_pbm_name'] = $find[$config['head_pbm_name']];
	    		}
	    		if ($config['head_shipping_agent_id'] != null) {
	    			$head['uper_shipping_agent_id'] = $find[$config['head_shipping_agent_id']];
	    		}
	    		if ($config['head_shipping_agent_name'] != null) {
	    			$head['uper_shipping_agent_name'] = $find[$config['head_shipping_agent_name']];
	    		}
	              // if ($config['head_terminal_name'] != null) {
	              //     $head['uper_terminal_name'] = $find[$config['head_terminal_name']];
	              // }
	    		$head['nota_view'] = $nota_view;
	          // build head

	    		$result[] = $head;
	    	}

	      return [ "Success" => true, "result" => $result];
		}

	    public static function approvalRequestPLG($input){
			$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			$config = json_decode($config->api_set, true);
			$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['result' => "Fail, requst not found!", "Success" => false];
			}
			$find = (array)$find[0];
			if ($find[$config['head_status']] == 3 and $input['approved'] == 'true') {
				return ['result' => "Fail, requst already approved!", "Success" => false];
			}
			$uper = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no',$find[$config['head_no']])->get();
			if (count($uper) > 0) {
				return ['result' => "Fail, request already exist on proforma!", "Success" => false];
			}
			if ($input['approved'] == 'false') {
				DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 4,
					$config['head_mark'] => $input['msg']
				]);
				return ['result' => "Success, rejected requst", 'no_req' => $find[$config['head_no']]];
			}

			$datenow    = Carbon::now()->format('Y-m-d');
			$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$find[$config['head_no']]."'";
			$tarifs = DB::connection('eng')->select(DB::raw($query));
			if (count($tarifs) == 0) {
				return ['result' => "Fail, proforma and tariff not found!", "Success" => false];
			}
			$migrateTariff = true;
			if ($migrateTariff == true) {
				foreach ($tarifs as $tarif) {
					$tarif = (array)$tarif;

					$createdUperNo = '';
					// store head
						$headU = new TxHdrNota;
						// $headU->app_id =$find['app_id'];
						$headU->nota_group_id = $tarif['nota_id'];
						$headU->nota_org_id = $tarif['branch_org_id'];
						$headU->nota_cust_id = $tarif['customer_id'];
						$headU->nota_cust_name = $tarif['alt_name'];
						$headU->nota_cust_npwp = $tarif['npwp'];
						$headU->nota_cust_address = $tarif['address'];
						$headU->nota_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
						$headU->nota_amount = $tarif['total'];
						$headU->nota_currency_code = $tarif['currency'];
						$headU->nota_status = 1;
						$headU->nota_context = $tarif['nota_context'];
						$headU->nota_sub_context = $tarif['nota_sub_context'];
						$headU->nota_service_code = $tarif['nota_service_code'];
						$headU->nota_branch_account = $tarif['branch_account'];
						$headU->nota_tax_code = $tarif['tax_code'];
						// $headU->nota_terminal = $find[$config['head_terminal_name']];
						$headU->nota_branch_id = $tarif['branch_id'];
						$headU->nota_branch_code = $tarif['branch_code'];
						$headU->nota_vessel_name = $find[$config['head_vessel_name']];
						// $headU->ukk = 'ukk';
						$headU->nota_trade_type = $tarif['trade_type'];
						$headU->nota_req_no = $tarif['booking_number'];
						// $headU->nota_real_no = '';

						$headU->nota_ppn = $tarif['ppn_uper'];
						// $headN->nota_paid = $getH->; // pasti null
				        // $headN->nota_paid_date = $getH->; // pasti null
				        // $headN->rest_payment = $getH->; // pasti null
				        $headU->nota_dpp = $tarif['dpp'];
						if ($config['head_pbm_id'] != null) {
							$headU->nota_pbm_id = $find[$config['head_pbm_id']];
						}
						if ($config['head_pbm_name'] != null) {
							$headU->nota_pbm_name = $find[$config['head_pbm_name']];
						}
						if ($config['head_shipping_agent_id'] != null) {
							$headU->nota_stackby_id = $find[$config['head_shipping_agent_id']];
						}
						if ($config['head_shipping_agent_name'] != null) {
							$headU->nota_stackby_name = $find[$config['head_shipping_agent_name']];
						}
						$headU->nota_req_date = $find[$config['head_date']];
						$headU->save();

						$headU = TxHdrNota::find($headU->nota_id);
						$createdUperNo .= $headU->nota_no.', ';
					// store head

					$queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$tarif['temp_hdr_id']."' AND CUSTOMER_ID = '".$tarif['customer_id']."'";
					$group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));

					$countLine = 0;
					foreach ($group_tariff as $grpTrf) {
						$grpTrf = (array)$grpTrf;
						$tarifD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$tarif['temp_hdr_id'])->where('group_tariff_id',$grpTrf['group_tariff_id'])->get();

						foreach ($tarifD as $list) {
							$countLine++;
							$list = (array)$list;
							$set_data = [
								"dtl_group_tariff_id" => $list["group_tariff_id"],
								"dtl_group_tariff_name" => $list["group_tariff_name"],
								"dtl_bl" => $list["no_bl"],
								"dtl_dpp" => $list["tariff_cal"],
								"dtl_commodity" => $list["commodity_name"],
								"dtl_equipment" => $list["equipment_name"],
								"dtl_masa_reff" => $list["stack_combine"],
								// "nota_dtl_id" => '',
								"nota_hdr_id" => $headU->nota_id,
								"dtl_line" => $countLine,
								"dtl_line_desc" => $list['memoline'],
								// "dtl_line_context" => , // perlu konfimasi
								"dtl_service_type" => $list['group_tariff_name'],
								"dtl_amount" => $list['total'],
								"dtl_ppn" => $list["ppn"],
								"dtl_masa" => $list["day_period"],
								// "dtl_masa1" => , // cooming soon
								// "dtl_masa12" => , // cooming soon
								// "dtl_masa2" => , // cooming soon
								"dtl_tariff" => $list["tariff"],
								"dtl_package" => $list["package_name"],
								"dtl_eq_qty" => $list["eq_qty"],
								"dtl_qty" => $list["qty"],
								"dtl_unit" => $list["unit_id"],
								"dtl_unit_qty" => $list["unit_qty"],
								"dtl_unit_name" => $list["unit_name"],
								"dtl_sub_tariff" => $list["sub_tariff"],
								"dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")
							];
							DB::connection('omuster')->table('TX_DTL_NOTA')->insert($set_data);
						}
					}
				}
			}

			DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 3,
				$config['head_mark'] => $input['msg']
			]);

			$sendRequestBooking = '';
			if ($migrateTariff == true) {
				$pesan = "Created Nota No : ".$createdUperNo;
			}else if($migrateTariff == false) {
				// $sendRequestBooking = PlgConnectedExternalApps::sendRequestBooking(['req_no' => $find[$config['head_no']], 'paid_date' => null ]);
				// $pesan = "Nota Not created, uper percent for this request is 0%";
			}

			if ($find[$config['head_paymethod']] == 2) {
				$sendRequestBooking = PlgConnectedExternalApps::sendRequestBookingPLG(['id' => $input['id'] ,'config' => $config]);
			}

			return [
				'result' => "Success, approved request! ".$pesan,
				"note" => $pesan,
				'no_req' => $find[$config['head_no']],
				'sendRequestBooking' => $sendRequestBooking
			];
	    }

	    public static function storePaymentPLG($input){
            $cekNota = TxHdrNota::where([
            	'nota_no'=>$input['pay_nota_no'],
            	'nota_paid'=>'Y'
            ])->count();
            if ($cekNota > 0) {
            	return ['result' => "Fail, proforma already paid!", "Success" => false];
            }
	    	if (empty($input['pay_file']['PATH']) or empty($input['pay_file']['BASE64']) or empty($input['pay_file'])) {
              return ["Success"=>false, "result" => "Fail, file is required"];
            }
			$datenow    = Carbon::now()->format('Y-m-d');
	    	$store = new TxPayment;
	    	// pay_id            number,
	    	// pay_no            varchar2(20 byte),
	    	$store->pay_nota_no = $input['pay_nota_no'];
	    	$store->pay_req_no = $input['pay_req_no'];
	    	$store->pay_method = $input['pay_method'];
	    	$store->pay_cust_id = $input['pay_cust_id'];
	    	$store->pay_cust_name = $input['pay_cust_name'];
	    	$store->pay_bank_code = $input['pay_bank_code'];
	    	$store->pay_bank_name = $input['pay_bank_name'];
	    	$store->pay_branch_id = $input['pay_branch_id'];
	    	$store->pay_account_no = $input['pay_account_no'];
	    	$store->pay_account_name = $input['pay_account_name'];
	    	$store->pay_amount = $input['pay_amount'];
	    	$store->pay_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD')");
	    	$store->pay_note = $input['pay_note'];
	    	$store->pay_create_by = $input['pay_create_by'];
	    	$store->pay_create_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
	    	$store->pay_type = $input['pay_type'];
	    	$store->save();

	    	if (!empty($input['pay_file']['PATH']) and !empty($input['pay_file']['BASE64']) and !empty($input['pay_file'])) {
	    		$directory  = 'omuster/TX_PAYMENT/'.date('d-m-Y').'/';
	    		$response   = FileUpload::upload_file($input['pay_file'], $directory, "TX_PAYMENT", $store->pay_id);
	    		if ($response['response'] == true) {
	    			TxPayment::where('pay_id',$store->pay_id)->update([
	    				'pay_file' => $response['link']
	    			]);
	    		}
	    	}

	    	$getNota = TxHdrNota::where([ 'nota_no'=>$input['pay_nota_no'] ])->first();
        	$getNota->nota_paid = 'Y';
        	$getNota->nota_paid_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
        	$getNota->save();
        	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $getNota->nota_group_id)->first();
			$config = json_decode($config->api_set, true);
			$getReq = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$pay->pay_req_no)->first();
			$getReq = (array)$getReq;
            $sendRequestBooking = PlgConnectedExternalApps::sendRequestBookingPLG(['id' => $getReq[$config['head_primery']] ,'config' => $config]);

	    	$pay = TxPayment::find($store->pay_id);
            return [
				'result' => "Success, pay proforma!",
				'no_pay' => $pay->pay_no,
				'no_nota' => $input['pay_nota_no'],
				'no_req' => $pay->pay_req_no,
				'sendRequestBooking' => $sendRequestBooking
			];
	    }
	// PLG
}