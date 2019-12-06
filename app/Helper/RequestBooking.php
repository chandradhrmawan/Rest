<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\BillingEngine;
use App\Models\OmCargo\TxHdrUper;

class RequestBooking{

	public static function sendRequest($input){
		$input['table'] = strtoupper($input['table']);
		$config = static::config($input['table']);
		$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
		if (empty($find)) {
			return ['result' => "Fail, requst not found!"];
		}
		$find = (array)$find[0];

		// build head
			$setH = [];
			$setH['P_NOTA_ID'] = $config['head_nota_id'];
			$setH['P_BRANCH_ID'] = $find[$config['head_branch']];
			$setH['P_CUSTOMER_ID'] = $find[$config['head_cust']];
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
				$newD['DTL_BL'] = empty($list[$config['head_tab_detil_bl']]) ? 'NULL' : $list[$config['head_tab_detil_bl']];
				$newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
				$newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
				$newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
				$newD['DTL_CONT_SIZE'] = empty($list['dtl_cont_size']) ? 'NULL' : $list['dtl_cont_size'];
				$newD['DTL_CONT_TYPE'] = empty($list['dtl_cont_type']) ? 'NULL' : $list['dtl_cont_type'];
				$newD['DTL_CONT_STATUS'] = empty($list['dtl_cont_status']) ? 'NULL' : $list['dtl_cont_status'];
				$newD['DTL_UNIT_ID'] = empty($list['dtl_unit_id']) ? 'NULL' : $list['dtl_unit_id'];
				$newD['DTL_QTY'] = empty($list['dtl_qty']) ? 'NULL' : $list['dtl_qty'];

				$getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', $config['head_nota_id'])->where('BRANCH_ID',$find[$config['head_branch']])->where('GROUP_TARIFF_ID', 15)->count();
				if ($getPFS > 0) {
					$newD['DTL_PFS'] = 'Y';
				}else{
					$newD['DTL_PFS'] = 'N';
				}

				$DTL_BM_TYPE = 'NULL';
				if ($config['head_nota_id'] == "13") {
					$DTL_BM_TYPE = empty($list['dtl_bm_type']) ? 'NULL' : $list['dtl_bm_type'];
				}
				$newD['DTL_BM_TYPE'] = $DTL_BM_TYPE;

				$DTL_STACK_AREA = 'NULL';
				if (in_array($config['head_nota_id'], ["14", "15", 14, 15])) {
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
					}
				}else{
					$newD['DTL_DATE_IN'] = 'NULL';
				}

				if ($config['head_tab_detil_date_out_old'] != null and ($input['table'] == 'TX_HDR_DEL' and $find['del_ext_status'] != 'N') ) {
					$newD['DTL_DATE_OUT'] = empty($find[$config['head_tab_detil_date_out_old']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($find[$config['head_tab_detil_date_out_old']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					// $findEx = DB::connection('omcargo')->select(DB::raw("
					// 	SELECT
					// 	X.DTL_OUT AS date_out_old,
					// 	Y.DTL_OUT AS date_out
					// 	FROM (
					// 	SELECT
					// 	DEL_ID,DEL_NO,DTL_OUT,DEL_EXT_FROM_DATE
					// 	FROM
					// 	TX_HDR_DEL A
					// 	JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
					// 	) X
					// 	JOIN (
					// 	SELECT
					// 	DEL_ID,DEL_NO,DTL_OUT,DEL_EXT_FROM_DATE
					// 	FROM
					// 	TX_HDR_DEL A
					// 	JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
					// 	) Y
					// 	ON X.DTL_OUT=Y.DEL_EXT_FROM_DATE WHERE Y.DEL_NO='".$find[$config['head_no']]."'
					// 	"));
					// if (empty($findEx)) {
					// 	$newD['DTL_DATE_OUT_OLD'] = 'NULL';
					// 	$newD['DTL_DATE_OUT'] = 'NULL';
					// }else{
					// 	$findEx = $findEx[0];
					// 	$findEx = (array)$findEx;
					// 	$newD['DTL_DATE_OUT_OLD'] = empty($findEx['date_out_old']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($findEx['date_out_old'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					// 	$newD['DTL_DATE_OUT'] = empty($findEx['date_out']) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($findEx['date_out'])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					// }
				}else{
					$newD['DTL_DATE_OUT_OLD'] = 'NULL';

					if ($config['head_tab_detil_date_out'] != null) {
						if ($input['table'] == 'TX_HDR_DEL') {
							$newD['DTL_DATE_OUT'] = empty($list[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['head_tab_detil_date_out']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
						}else{
							$newD['DTL_DATE_OUT'] = empty($find[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($find[$config['head_tab_detil_date_out']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
						}
					}else{
						$newD['DTL_DATE_OUT'] = 'NULL';
					}
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
			$paysplit = DB::connection('omcargo')->table('TX_SPLIT_NOTA')->where('req_no', $find[$config['head_no']])->get();
			$paysplit = (array)$paysplit;
			foreach ($paysplit as $list) {
				$newP = [];
				$list = (array)$list;
				$newP['PS_CUST_ID'] = $list['cust_id'];
				$newP['PS_GTRF_ID'] = $list['group_tarif_id'];
				$setP[] = $newP;
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
		foreach ($upers as $uper) {
			$uper = (array)$uper;

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
					$headU->uper_faktur_no = '12576817'; // ? dari triger bf i
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

		if ($migrateTariff == true) {
			$pesan = "Created Uper No : ".$createdUperNo;
		}else if($migrateTariff == false) {
			ConnectedExternalApps::sendRequestBooking(['req_no' => $find[$config['head_no']], 'paid_date' => null ]);
			$pesan = "Uper Not created, uper percent for this request is 0%";
		}

		return ['result' => "Success, approved request!", "note" => $pesan, 'no_req' => $find[$config['head_no']]];
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
        		"head_by" => "bm_create_by",
        		"head_date" => "bm_date",
        		"head_branch" => "bm_branch_id",
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
        		"head_tab_detil_date_out" => 'rec_atd',
        		"head_tab_detil_date_out_old" => null,
        		"head_status" => "rec_status",
        		"head_primery" => "rec_id",
        		"head_forigen" => "hdr_rec_id",
        		"head_no" => "rec_no",
        		"head_by" => "rec_create_by",
        		"head_date" => "rec_date",
        		"head_branch" => "rec_branch_id",
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
        		"head_tab_detil_date_out" => 'dtl_out',
        		"head_tab_detil_date_out_old" => 'del_ext_from_date',
        		"head_status" => "del_status",
        		"head_primery" => "del_id",
        		"head_forigen" => "hdr_del_id",
        		"head_no" => "del_no",
        		"head_by" => "del_create_by",
        		"head_date" => "del_date",
        		"head_branch" => "del_branch_id",
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
}
