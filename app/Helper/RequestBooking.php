<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\BillingEngine;
use App\Models\OmCargo\TxHdrUper;
use App\Models\OmUster\TxHdrNota;
use App\Models\OmUster\TxPayment;

class RequestBooking{

	// BTN
		public static function sendRequest($input){
			$input['table'] = strtoupper($input['table']);
			$config = static::config($input['table']);
			$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['Success' => false, 'result' => "Fail, requst not found!"];
			}
			$find = (array)$find[0];

			$cekStatusNota = DB::connection('mdm')->table('TS_NOTA')->where([
				'branch_id' => $find[$config['head_branch']],
				'branch_code' => $find[$config['head_branch_code']],
				'nota_id' => $input['nota_id']
			])->get();
			if (count($cekStatusNota) == 0) {
				return ['Success' => false, 'result' => "Fail, nota not available!"];
			}else if ($cekStatusNota[0]->flag_status == 'N') {
				return ['Success' => false, 'result' => "Fail, nota not active!"];
			}
			$pbmCek = 'N';
			if ($input['table'] == 'TX_HDR_BM') {
				$countPBM = DB::connection('mdm')->table('TM_PBM_INTERNAL')->where('PBM_ID',$find['bm_pbm_id'])->where('BRANCH_ID',$find['bm_branch_id'])->where('BRANCH_CODE',$find['bm_branch_code'])->count();
				if ($countPBM > 0) { $pbmCek = 'Y'; }
			}
			// build head
				$setH = [];
				$setH['P_NOTA_ID'] = $input['nota_id'];
				$setH['P_BRANCH_ID'] = $find[$config['head_branch']];
				$setH['P_BRANCH_CODE'] = $find[$config['head_branch_code']];
				$setH['P_CUSTOMER_ID'] = $find[$config['head_cust']];
				$setH['P_PBM_INTERNAL'] = $pbmCek;
				$setH['P_BOOKING_NUMBER'] = $find[$config['head_no']];
				$setH['P_REALIZATION'] = 'N';
				$setH['P_RESTITUTION'] = 'N';
				$setH['P_TRADE'] = $find[$config['head_trade']];
				$setH['P_USER_ID'] = $find[$config['head_by']];
			// build head

			// build detil
				$setD = [];
				$detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']])->get();
				foreach ($detil as $list) {
					$newD = [];
					$list = (array)$list;
					$newD['DTL_VIA'] = 'NULL';
					$newD['DTL_BL'] = empty($list[$config['head_tab_detil_bl']]) ? 'NULL' : $list[$config['head_tab_detil_bl']];
					$newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
					$newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
					$newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
					$newD['DTL_CONT_SIZE'] = empty($list['dtl_cont_size']) ? 'NULL' : $list['dtl_cont_size'];
					$newD['DTL_CONT_TYPE'] = empty($list['dtl_cont_type']) ? 'NULL' : $list['dtl_cont_type'];
					$newD['DTL_CONT_STATUS'] = empty($list['dtl_cont_status']) ? 'NULL' : $list['dtl_cont_status'];
					$newD['DTL_UNIT_ID'] = empty($list['dtl_unit_id']) ? 'NULL' : $list['dtl_unit_id'];
					$newD['DTL_QTY'] = empty($list['dtl_qty']) ? 'NULL' : $list['dtl_qty'];

					$getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', $input['nota_id'])->where('BRANCH_ID',$find[$config['head_branch']])->where('BRANCH_CODE',$find[$config['head_branch_code']])->where('GROUP_TARIFF_ID', 15)->count();
					if ($getPFS > 0) {
						$newD['DTL_PFS'] = 'Y';
					}else{
						$newD['DTL_PFS'] = 'N';
					}

					$DTL_BM_TYPE = 'NULL';
					if ($input['nota_id'] == "13") {
						$DTL_BM_TYPE = empty($list['dtl_bm_type']) ? 'NULL' : $list['dtl_bm_type'];
					}
					$newD['DTL_BM_TYPE'] = $DTL_BM_TYPE;

					$DTL_STACK_AREA = 'NULL';
					if (in_array($input['nota_id'], ["14", "15", 14, 15])) {
						$DTL_STACK_AREA = empty($list['dtl_stacking_type_id']) ? 'NULL' : $list['dtl_stacking_type_id'];
					}
					$newD['DTL_STACK_AREA'] = $DTL_STACK_AREA;

					if ($config['head_tab_detil_tl'] != null) {
						$newD['DTL_TL'] = empty($list[$config['head_tab_detil_tl']]) ? 'NULL' : $list[$config['head_tab_detil_tl']];
					}else{
						$newD['DTL_TL'] = 'NULL';
					}

					if ($config['head_tab_detil_date_in'] != null) {
						if ($input['table'] == 'TX_HDR_REC') {
							$newD['DTL_DATE_IN'] = empty($list[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['head_tab_detil_date_in']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
						}else{
							$newD['DTL_DATE_IN'] = empty($list[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['head_tab_detil_date_in']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
							if (!empty($find['del_ext_from'])) {
								$gthdrId = DB::connection('omcargo')->table('TX_HDR_DEL')->where('del_no', $find['del_ext_from'])->get();
								$gthdrId = $gthdrId[0];
								$getdatein = DB::connection('omcargo')->table('TX_DTL_DEL')->where('hdr_del_id',$gthdrId->del_id)->where('dtl_del_bl', $list['dtl_del_bl'])->get();
								$getdatein = $getdatein[0];
								$getdatein = (array)$getdatein;
								$newD['DTL_DATE_IN'] = 'to_date(\''.\Carbon\Carbon::parse($getdatein[$config['head_tab_detil_date_in']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
							}
						}
					}else{
						$newD['DTL_DATE_IN'] = 'NULL';
					}

					$newD['DTL_DATE_OUT'] = 'NULL';
					$newD['DTL_DATE_OUT_OLD'] = 'NULL';

					if ($config['head_tab_detil_date_out_old'] != null and $input['table'] == 'TX_HDR_DEL') {
						if ($find['del_ext_status'] == 'Y') {
							$newD['DTL_DATE_OUT'] = empty($list['dtl_out']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list['dtl_out'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
							$old_req_id = DB::connection('omcargo')->table('TX_HDR_DEL')->where('DEL_NO',$find['del_ext_from'])->get();
							$old_req_id = $old_req_id[0]->del_id;
							$old_bl = DB::connection('omcargo')->table('TX_DTL_DEL')->where('HDR_DEL_ID',$old_req_id)->where('DTL_DEL_BL',$list['dtl_del_bl'])->get();
							$old_bl_date_out = $old_bl[0]->dtl_out;
							$newD['DTL_DATE_OUT_OLD'] = empty($old_bl_date_out) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($old_bl_date_out)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
						}else{
							$newD['DTL_DATE_OUT'] = empty($list['dtl_out']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list['dtl_out'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
							$newD['DTL_DATE_OUT_OLD'] = 'NULL';
						}
					}else{
						$newD['DTL_DATE_OUT_OLD'] = empty($find[$config['head_tab_detil_date_out_old']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($find[$config['head_tab_detil_date_out_old']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
						$newD['DTL_DATE_OUT'] = empty($find[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($find[$config['head_tab_detil_date_out']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}

					$setD[] = $newD;
				}
			// build detil

			// build eqpt
				$setE = [];
				$eqpt = DB::connection('omcargo')->table('TX_EQUIPMENT')->where('req_no', $find[$config['head_no']])->get();
				foreach ($eqpt as $list) {
					$newE = [];
					$list = (array)$list;
					$newE['EQ_TYPE'] = empty($list['eq_type_id']) ? 'NULL' : $list['eq_type_id'];
					$newE['EQ_QTY'] = empty($list['eq_qty']) ? 'NULL' : $list['eq_qty'];
					$newE['EQ_UNIT_ID'] = empty($list['eq_unit_id']) ? 'NULL' : $list['eq_unit_id'];
					$newE['EQ_GTRF_ID'] = empty($list['group_tariff_id']) ? 'NULL' : $list['group_tariff_id'];
					$newE['EQ_PKG_ID'] = empty($list['package_id']) ? 'NULL' : $list['package_id'];
					$newE['EQ_QTY_PKG'] = empty($list['unit_qty']) ? 'NULL' : $list['unit_qty'];
					$setE[] = $newE;
				}
			// build eqpt

			// build paysplit
				$setP = [];
				if ($find[$config['head_split']] == 'Y') {
					$paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find[$config['head_no']])->get();
					$paysplit = (array)$paysplit;
					foreach ($paysplit as $list) {
						$newP = [];
						$list = (array)$list;
						$newP['PS_CUST_ID'] = $list['cust_id'];
						$newP['PS_GTRF_ID'] = $list['group_tarif_id'];
						$setP[] = $newP;
					}
				}
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

			if ($tariffResp['result_flag'] == 'S') {
				DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 2
				]);
			}
			return $tariffResp;
	    }

	    public static function approvalRequest($input){
	    	$input['table'] = strtoupper($input['table']);
			$config = static::config($input['table']);
			$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['result' => "Fail, requst not found!", "Success" => false];
			}
			$find = (array)$find[0];
			if ($find[$config['head_status']] == 3 and $input['approved'] == 'true') {
				return ['result' => "Fail, requst already approved!", "Success" => false];
			}
			$uper = DB::connection('omcargo')->table('TX_HDR_UPER')->where('uper_req_no',$find[$config['head_no']])->get();
			if (count($uper) > 0) {
				return ['result' => "Fail, request already exist on uper!", "Success" => false];
			}
			if ($input['approved'] == 'false') {
				DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 4,
					$config['head_mark'] => $input['msg']
				]);
				return ['result' => "Success, rejected requst", 'no_req' => $find[$config['head_no']]];
			}

			$datenow    = Carbon::now()->format('Y-m-d');
			$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$find[$config['head_no']]."'";
			$upers = DB::connection('eng')->select(DB::raw($query));
			if (count($upers) == 0) {
				return ['result' => "Fail, uper and tariff not found!", "Success" => false];
			}
			$upPercent = DB::connection('eng')->table('TS_UPER')->where('UPER_NOTA', $config['head_nota_id'])->where('BRANCH_ID', $find[$config['head_branch']])->where('UPER_CUST_ID', $find[$config['head_cust']])->get();
			if (count($upPercent) == 0) {
				$migrateTariff = false;
				$upPercent = DB::connection('eng')->table('TS_UPER')->where('UPER_NOTA', $config['head_nota_id'])->where('BRANCH_ID', $find[$config['head_branch']])->whereNull('UPER_CUST_ID')->get();
				if (count($upPercent) == 0){
					$migrateTariff = false;
				}else{
					$upPercent = $upPercent[0];
					if ($upPercent->uper_presentase == 0) {
						$migrateTariff = false;
					}else{
						$migrateTariff = true;
					}
				}
			}else{
				$upPercent = $upPercent[0];
				if ($upPercent->uper_presentase == 0) {
					$migrateTariff = false;
				}else{
					$migrateTariff = true;
				}
			}
			if ($migrateTariff == true) {
				foreach ($upers as $uper) {
					$uper = (array)$uper;

					$createdUperNo = '';
					// store head
						$headU = new TxHdrUper;
						// $headU->uper_no // dari triger
						$headU->uper_org_id = $uper['branch_org_id'];
						$headU->uper_cust_id = $uper['customer_id'];
						$headU->uper_cust_name = $uper['alt_name'];
						$headU->uper_cust_npwp = $uper['npwp'];
						$headU->uper_cust_address = $uper['address'];
						$headU->uper_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
						$headU->uper_amount = $uper['total_uper'];
						$headU->uper_currency_code = $uper['currency'];
						$headU->uper_status = 'P'; // ? blm fix
						// Tambahan Mas Adi
						$headU->uper_service_code = $uper['nota_service_code'];
						$headU->uper_branch_account = $uper['branch_account'];
						$headU->uper_context = $uper['nota_context'];
						$headU->uper_sub_context = $uper['nota_sub_context'];
						$headU->uper_terminal_code = $find[$config['head_terminal_code']];
						$headU->uper_branch_id = $uper['branch_id'];
						$headU->uper_branch_code = $uper['branch_code'];
						$headU->uper_vessel_name = $find[$config['head_vessel_name']];
						// $headU->uper_faktur_no = ''; // ? dari triger bf i
						$headU->uper_trade_type = $uper['trade_type'];
						$headU->uper_trade_name = $uper['trade_type'] == 'D' ? 'Domestik' : 'Internasional';
						$headU->uper_req_no = $uper['booking_number'];
						$headU->uper_ppn = $uper['ppn_uper'];
						// $headU->uper_paid // ? pasti null
						// $headU->uper_paid_date // ? pasti null
						$headU->uper_percent = $uper['percent_uper'];
						$headU->uper_dpp = $uper['dpp_uper'];
						if ($config['head_pbm_id'] != null) {
							$headU->uper_pbm_id = $find[$config['head_pbm_id']];
						}
						if ($config['head_pbm_name'] != null) {
							$headU->uper_pbm_name = $find[$config['head_pbm_name']];
						}
						if ($config['head_shipping_agent_id'] != null) {
							$headU->uper_shipping_agent_id = $find[$config['head_shipping_agent_id']];
						}
						if ($config['head_shipping_agent_name'] != null) {
							$headU->uper_shipping_agent_name = $find[$config['head_shipping_agent_name']];
						}
						$headU->uper_req_date = $find[$config['head_date']];
						if ($config['head_terminal_name'] != null) {
							$headU->uper_terminal_name = $find[$config['head_terminal_name']];
						}
						$headU->uper_nota_id = $uper['nota_id'];
						$headU->app_id =$find['app_id'];
						$headU->save();

						$headU = TxHdrUper::find($headU->uper_id);
						$createdUperNo .= $headU->uper_no.', ';
					// store head

					$queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$uper['temp_hdr_id']."' AND CUSTOMER_ID = '".$uper['customer_id']."'";
					$group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));

					$countLine = 0;
					foreach ($group_tariff as $grpTrf) {
						$grpTrf = (array)$grpTrf;
						$uperD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$uper['temp_hdr_id'])->where('group_tariff_id',$grpTrf['group_tariff_id'])->get();

						foreach ($uperD as $list) {
							$countLine++;
							$list = (array)$list;
							$set_data = [
								"uper_hdr_id" => $headU->uper_id,
								"dtl_line" => $countLine,
								"dtl_line_desc" => $list['memoline'],
								// "dtl_line_context" => , // perlu konfimasi
								"dtl_service_type" => $list['group_tariff_name'],
								"dtl_amount" => $list['total_uper'],
								"dtl_ppn" => $list["ppn_uper"],
								"dtl_masa" => $list["day_period"],
								// "dtl_masa1" => , // cooming soon
								// "dtl_masa12" => , // cooming soon
								// "dtl_masa2" => , // cooming soon
								"dtl_masa_reff" => $list["stack_combine"],
								"dtl_total_tariff" => $list["tariff_uper"],
								"dtl_tariff" => $list["tariff"],
								"dtl_package" => $list["package_name"],
								"dtl_qty" => $list["qty"],
								"dtl_eq_qty" => $list["eq_qty"],
								"dtl_unit" => $list["unit_id"],
								"dtl_unit_qty" => $list["unit_qty"],
								"dtl_unit_name" => $list["unit_name"],
								"dtl_group_tariff_id" => $list["group_tariff_id"],
								"dtl_group_tariff_name" => $list["group_tariff_name"],
								"dtl_bl" => $list["no_bl"],
								"dtl_dpp" => $list["tariff_cal_uper"],
								"dtl_commodity" => $list["commodity_name"],
								"dtl_equipment" => $list["equipment_name"],
								"dtl_sub_tariff" => $list["sub_tariff"],
								"dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")
							];
							DB::connection('omcargo')->table('TX_DTL_UPER')->insert($set_data);
						}
					}
				}
			}

			DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 3,
				$config['head_mark'] => $input['msg']
			]);

			$sendRequestBooking = '';
			if ($migrateTariff == true) {
				$pesan = "Created Uper No : ".$createdUperNo;
			}else if($migrateTariff == false) {
				$sendRequestBooking = ConnectedExternalApps::sendRequestBooking(['req_no' => $find[$config['head_no']], 'paid_date' => null ]);
				$pesan = "Uper Not created, uper percent for this request is 0%";
			}

			return [
				'result' => "Success, approved request! ".$pesan,
				"note" => $pesan,
				'no_req' => $find[$config['head_no']],
				'sendRequestBooking' => $sendRequestBooking
			];
	    }

	    public static function config($input){
	    	$requst_config = [
	        	"TX_HDR_BM" => [
	        		"head_eta" => "bm_eta",
	        		"head_etd" => "bm_etd",
	        		"head_open_stack" => "bm_open_stack",
	        		"head_closing_time" => "bm_closing_time",
	        		"head_cust_id" => "bm_cust_id",
	        		"head_cust_name" => "bm_cust_name",
	        		"head_cust_addr" => "bm_cust_address",
	        		"head_cust_npwp" => "bm_cust_npwp",
	        		"head_voyin" => "bm_voyin",
	        		"head_voyout" => "bm_voyout",
	        		"head_vvd_id" => "bm_vvd_id",
	        		"head_nota_id" => "13",
	        		"head_tab" => "TX_HDR_BM",
	        		"head_mark" => "bm_mark",
	        		"head_tab_detil" => "TX_DTL_BM",
	        		"head_tab_detil_id" => "dtl_bm_id",
	        		"head_tab_detil_bl" => "dtl_bm_bl",
	        		"head_tab_detil_tl" => "dtl_bm_tl",
	        		"head_tab_detil_date_in" => null,
	        		"head_tab_detil_date_out" => null,
	        		"head_tab_detil_date_out_old" => null,
	        		"head_status" => "bm_status",
	        		"head_primery" => "bm_id",
	        		"head_forigen" => "hdr_bm_id",
	        		"head_no" => "bm_no",
	        		"head_split" => "bm_split",
	        		"head_by" => "bm_create_by",
	        		"head_date" => "bm_date",
	        		"head_branch" => "bm_branch_id",
	        		"head_branch_code" => "bm_branch_code",
	        		"head_cust" => "bm_cust_id",
	        		"head_trade" => "bm_trade_type",
	        		"head_terminal_code" => "bm_terminal_code",
	        		"head_terminal_name" => "bm_terminal_name",
	        		"head_pbm_id" => "bm_pbm_id",
	        		"head_pbm_name" => "bm_pbm_name",
	        		"head_shipping_agent_id" => "bm_shipping_agent_id",
	        		"head_shipping_agent_name" => "bm_shipping_agent_name",
	        		"head_vessel_code" => "bm_vessel_code",
	        		"head_vessel_name" => "bm_vessel_name"
	        	],
	        	"TX_HDR_REC" => [
	        		"head_eta" => "rec_eta",
	        		"head_etd" => "rec_etd",
	        		"head_open_stack" => "rec_open_stack",
	        		"head_closing_time" => "rec_closing_time",
	        		"head_cust_id" => "rec_cust_id",
	        		"head_cust_name" => "rec_cust_name",
	        		"head_cust_addr" => "rec_cust_address",
	        		"head_cust_npwp" => "rec_cust_npwp",
	        		"head_voyin" => "rec_voyin",
	        		"head_voyout" => "rec_voyout",
	        		"head_vvd_id" => "rec_vvd_id",
	        		"head_nota_id" => "14",
	        		"head_tab" => "TX_HDR_REC",
	        		"head_mark" => "rec_mark",
	        		"head_tab_detil" => "TX_DTL_REC",
	        		"head_tab_detil_id" => "dtl_rec_id",
	        		"head_tab_detil_bl" => "dtl_rec_bl",
	        		"head_tab_detil_tl" => null,
	        		"head_tab_detil_date_in" => 'dtl_in',
	        		"head_tab_detil_date_out" => 'rec_etd',
	        		"head_tab_detil_date_out_old" => 'rec_extend_from',
	        		"head_status" => "rec_status",
	        		"head_primery" => "rec_id",
	        		"head_forigen" => "hdr_rec_id",
	        		"head_no" => "rec_no",
	        		"head_split" => "rec_split",
	        		"head_by" => "rec_create_by",
	        		"head_date" => "rec_date",
	        		"head_branch" => "rec_branch_id",
	        		"head_branch_code" => "rec_branch_code",
	        		"head_cust" => "rec_cust_id",
	        		"head_trade" => "rec_trade_type",
	        		"head_terminal_code" => "rec_terminal_code",
	        		"head_terminal_name" => "rec_terminal_name",
	        		"head_pbm_id" => null,
	        		"head_pbm_name" => null,
	        		"head_shipping_agent_id" => null,
	        		"head_shipping_agent_name" => null,
	        		"head_vessel_code" => "rec_vessel_code",
	        		"head_vessel_name" => "rec_vessel_name"
	        	],
	        	"TX_HDR_DEL" => [
	        		"head_eta" => "del_eta",
	        		"head_etd" => "del_etd",
	        		"head_open_stack" => "del_open_stack",
	        		"head_closing_time" => "del_closing_time",
	        		"head_cust_id" => "del_cust_id",
	        		"head_cust_name" => "del_cust_name",
	        		"head_cust_addr" => "del_cust_address",
	        		"head_cust_npwp" => "del_cust_npwp",
	        		"head_voyin" => "del_voyin",
	        		"head_voyout" => "del_voyout",
	        		"head_vvd_id" => "del_vvd_id",
	        		"head_nota_id" => "15",
	        		"head_tab" => "TX_HDR_DEL",
	        		"head_mark" => "del_mark",
	        		"head_tab_detil" => "TX_DTL_DEL",
	        		"head_tab_detil_id" => "dtl_del_id",
	        		"head_tab_detil_bl" => "dtl_del_bl",
	        		"head_tab_detil_tl" => null,
	        		"head_tab_detil_date_in" => 'dtl_in',
	        		"head_tab_detil_date_out" => 'del_ext_from_date',
	        		"head_tab_detil_date_out_old" => 'dtl_out',
	        		"head_status" => "del_status",
	        		"head_primery" => "del_id",
	        		"head_forigen" => "hdr_del_id",
	        		"head_no" => "del_no",
	        		"head_split" => "del_split",
	        		"head_by" => "del_create_by",
	        		"head_date" => "del_date",
	        		"head_branch" => "del_branch_id",
	        		"head_branch_code" => "del_branch_code",
	        		"head_cust" => "del_cust_id",
	        		"head_trade" => "del_trade_type",
	        		"head_terminal_code" => "del_terminal_code",
	        		"head_terminal_name" => "del_terminal_name",
	        		"head_pbm_id" => null,
	        		"head_pbm_name" => null,
	        		"head_shipping_agent_id" => null,
	        		"head_shipping_agent_name" => null,
	        		"head_vessel_code" => "del_vessel_code",
	        		"head_vessel_name" => "del_vessel_name"
	        	]
	        ];

	        return $requst_config[$input];
	    }
	// BTN

	// PLG
	    public static function sendRequestPLG($input){
	    	// dd($input["user"]);
			$input['table'] = strtoupper($input['table']);
			$config = static::configPLG($input['table']);
			$find = DB::connection('omuster')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['Success' => false, 'result' => "Fail, requst not found!"];
			}
			if ($find[$config['head_status']] == 3) {
				return ['Success' => false, 'result' => "Fail, requst already send!"];
			}
			$find = (array)$find[0];
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
				DB::connection('omuster')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
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

	    public static function configPLG($input){
	    	$requst_config = [
	        	"TX_HDR_REC" => [
					"kegiatan" => 1,
					"head_primery" => "rec_id",
					"head_branch" => "rec_branch_id",
					"head_branch_code" => "rec_branch_code",
					"head_cust" => "rec_cust_id",
					"head_cust_name" => "rec_cust_name",
					"head_cust_addr" => "rec_cust_address",
					"head_cust_npwp" => "rec_cust_npwp",
					"head_vvd" => "rec_vvd_id",
					"head_no" => "rec_no",
					"head_by" => "rec_create_by",
					"head_status" => "rec_status",
					"head_vessel_name" => "rec_vessel_name",
					"head_date" => "rec_create_date",
					"head_pbm_id" => "rec_pbm_id",
					"head_pbm_name" => "rec_pbm_name",
					"head_shipping_agent_id" => "rec_stackby_id",
					"head_shipping_agent_name" => "rec_stackby_name",
					"head_paymethod" => "rec_paymethod",
					"head_from" => "rec_from",
					"head_mark" => "rec_msg",
					"p_tarde" => null,
					"head_tab_detil" => "TX_DTL_REC",
					"head_forigen" => "rec_hdr_id",
					"DTL_VIA" => 'rec_dtl_via',
					"DTL_VIA_NAME" => 'rec_dtl_via_name',
					"DTL_BL" => 'rec_dtl_cont',
					"DTL_PKG_ID" => null,
					"DTL_CMDTY_ID" => "rec_dtl_cmdty_id",
					"DTL_CMDTY_NAME" => "rec_dtl_cmdty_name",
					"DTL_CHARACTER" => "rec_dtl_cont_danger",
					"DTL_CONT_SIZE" => "rec_dtl_cont_size",
					"DTL_CONT_TYPE" => "rec_dtl_cont_type",
					"DTL_CONT_STATUS" => "rec_dtl_cont_status",
					"DTL_UNIT_ID" => null,
					"DTL_QTY" => 1,
					"DTL_TL" => null,
					"DTL_OWNER" => 'rec_dtl_owner',
					"DTL_OWNER_NAME" => 'rec_dtl_owner_name',
					"DTL_DATE_IN" => 'rec_dtl_date_plan',
					"DTL_DATE_OUT" => null,
					"DTL_DATE_OUT_OLD" => null
	        	],
	        	"TX_HDR_REC_CARGO" => [
					"head_primery" => "rec_cargo_id",
					"head_branch" => "rec_cargo_branch_id",
					"head_branch_code" => "rec_cargo_branch_code",
					"head_cust" => "rec_cargo_cust_id",
					"head_no" => "rec_cargo_no",
					"head_by" => "rec_cargo_create_by",
					"head_status" => "rec_cargo_status",
					"p_tarde" => null,
					"head_tab_detil" => "TX_DTL_REC_CARGO",
					"head_forigen" => "rec_cargo_hdr_id",
					"DTL_VIA" => 'rec_cargo_dtl_via',
					"DTL_BL" => null,
					"DTL_PKG_ID" => 'rec_cargo_dtl_pkg_id',
					"DTL_CMDTY_ID" => "rec_cargo_dtl_cmdty_id",
					"DTL_CHARACTER" => 'rec_cargo_dtl_character_id',
					"DTL_CONT_SIZE" => null,
					"DTL_CONT_TYPE" => null,
					"DTL_CONT_STATUS" => null,
					"DTL_UNIT_ID" => 'rec_cargo_dtl_unit_id',
					"DTL_QTY" => 'rec_cargo_dtl_qty',
					"DTL_TL" => null,
					"DTL_DATE_IN" => null,
					"DTL_DATE_OUT" => null,
					"DTL_DATE_OUT_OLD" => null
	        	],
	        	"TX_HDR_DEL" => [
	        		"kegiatan" => 2,
					"head_primery" => "del_id",
					"head_branch" => "del_branch_id",
					"head_branch_code" => "del_branch_code",
					"head_cust" => "del_cust_id",
					"head_cust_name" => "del_cust_name",
					"head_cust_addr" => "del_cust_address",
					"head_cust_npwp" => "del_cust_npwp",
					"head_vvd" => "del_vvd_id",
					"head_no" => "del_no",
					"head_by" => "del_create_by",
					"head_status" => "del_status",
					"head_vessel_name" => "del_vessel_name",
					"head_date" => "del_create_date",
					"head_pbm_id" => "del_pbm_id",
					"head_pbm_name" => "del_pbm_name",
					"head_shipping_agent_id" => "del_stackby_id",
					"head_shipping_agent_name" => "del_stackby_name",
					"head_paymethod" => "del_paymethod",
					"head_mark" => "del_msg",
					"p_tarde" => null,
					"head_tab_detil" => "TX_DTL_DEL",
					"head_forigen" => "del_hdr_id",
					"DTL_VIA" => 'del_dtl_via',
					"DTL_BL" => 'del_dtl_cont',
					"DTL_PKG_ID" => null,
					"DTL_CMDTY_ID" => 'del_dtl_cmdty_id',
					"DTL_CHARACTER" => 'del_dtl_cont_danger',
					"DTL_CONT_SIZE" => 'del_dtl_cont_size',
					"DTL_CONT_TYPE" => 'del_dtl_cont_type',
					"DTL_CONT_STATUS" => 'del_dtl_cont_status',
					"DTL_UNIT_ID" => null,
					"DTL_QTY" => 1,
					"DTL_TL" => null,
					"DTL_OWNER" => 'del_dtl_owner',
					"DTL_OWNER_NAME" => 'del_dtl_owner_name',
					"DTL_DATE_IN" => null,
					"DTL_DATE_OUT" => 'del_dtl_date_plan',
					"DTL_DATE_OUT_OLD" => null
	        	],
	        	"TX_HDR_DEL_CARGO" => [
					"head_primery" => "del_cargo_id",
					"head_branch" => "del_cargo_branch_id",
					"head_branch_code" => "del_cargo_branch_code",
					"head_cust" => "del_cargo_cust_id",
					"head_no" => "del_cargo_no",
					"head_by" => "del_cargo_create_by",
					"head_status" => "del_cargo_status",
					"p_tarde" => null,
					"head_tab_detil" => "TX_DTL_DEL_CARGO",
					"head_forigen" => "del_cargo_hdr_id",
					"DTL_VIA" => 'del_cargo_dtl_via',
					"DTL_BL" => null,
					"DTL_PKG_ID" => 'del_cargo_dtl_pkg_id',
					"DTL_CMDTY_ID" => 'del_cargo_dtl_cmdty_id',
					"DTL_CHARACTER" => 'del_cargo_dtl_character_id',
					"DTL_CONT_SIZE" => null,
					"DTL_CONT_TYPE" => null,
					"DTL_CONT_STATUS" => null,
					"DTL_UNIT_ID" => 'del_cargo_dtl_unit_id',
					"DTL_QTY" => 'del_cargo_dtl_qty',
					"DTL_TL" => null,
					"DTL_DATE_IN" => null,
					"DTL_DATE_OUT" => null,
					"DTL_DATE_OUT_OLD" => null
	        	]
	        ];

	        return $requst_config[$input];
	    }

	    public static function approvalRequestPLG($input){
	    	$input['table'] = strtoupper($input['table']);
			$config = static::configPLG($input['table']);
			$find = DB::connection('omuster')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
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
				DB::connection('omuster')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
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

			DB::connection('omuster')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 3,
				$config['head_mark'] => $input['msg']
			]);

			$sendRequestBooking = '';
			if ($migrateTariff == true) {
				$pesan = "Created Nota No : ".$createdUperNo;
			}else if($migrateTariff == false) {
				// $sendRequestBooking = ConnectedExternalApps::sendRequestBooking(['req_no' => $find[$config['head_no']], 'paid_date' => null ]);
				// $pesan = "Nota Not created, uper percent for this request is 0%";
			}

			if ($find[$config['head_paymethod']] == 2) {
				$sendRequestBooking = ConnectedExternalApps::sendRequestBookingPLG(['tabel' => $input['table'], 'id' => $input['id'] ,'config' => $config ]);
			}

			return [
				'result' => "Success, approved request! ".$pesan,
				"note" => $pesan,
				'no_req' => $find[$config['head_no']],
				'sendRequestBooking' => $sendRequestBooking
			];
	    }

	    public static function storePaymentPLG($input)
	    {
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
	    	$store->pay_date = $input['pay_date'];
	    	$store->pay_note = $input['pay_note'];
	    	$store->pay_create_by = $input['user'];
	    	$store->pay_create_date = $input['pay_create_date'];
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
	    	return 'asd';
	    }
	// PLG
}
