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
			return response()->json(['result' => "Fail, requst not found!"]);
		}
		$find = (array)$find[0];

		// build head
			$setH = [];
			$setH['P_NOTA_ID'] = $config['head_nota_id'];
			$setH['P_BRANCH_ID'] = $find[$config['head_branch']];
			$setH['P_CUSTOMER_ID'] = $find[$config['head_cust']];
			$setH['P_BOOKING_NUMBER'] = $find[$config['head_no']];
			$setH['P_REALIZATION'] = 'N';
			$setH['P_DATE_IN'] = NULL;
			$setH['P_DATE_OUT'] = NULL;
			$setH['P_TRADE'] = $find[$config['head_trade']];
			$setH['P_USER_ID'] = $find[$config['head_by']];
		// build head

		// build detil
			$setD = [];
			$detil = DB::connection('omcargo')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']])->get();
			foreach ($detil as $list) { 
				$newD = [];
				$list = (array)$list;
				$newD['DTL_PKG_ID'] = empty($list['dtl_pkg_id']) ? 'NULL' : $list['dtl_pkg_id'];
				$newD['DTL_CMDTY_ID'] = empty($list['dtl_cmdty_id']) ? 'NULL' : $list['dtl_cmdty_id'];
				$newD['DTL_CHARACTER'] = empty($list['dtl_character_id']) ? 'NULL' : $list['dtl_character_id'];
				$newD['DTL_CONT_SIZE'] = empty($list['dtl_cont_size']) ? 'NULL' : $list['dtl_cont_size'];
				$newD['DTL_CONT_TYPE'] = empty($list['dtl_cont_type']) ? 'NULL' : $list['dtl_cont_type'];
				$newD['DTL_CONT_STATUS'] = empty($list['dtl_cont_status']) ? 'NULL' : $list['dtl_cont_status'];
				$newD['DTL_UNIT_ID'] = empty($list['dtl_unit_id']) ? 'NULL' : $list['dtl_unit_id'];
				$newD['DTL_QTY'] = empty($list['dtl_qty']) ? 'NULL' : $list['dtl_qty'];

				if ($config['head_tab_detil_tl'] != null) {
					$newD['DTL_TL'] = empty($list[$config['head_tab_detil_tl']]) ? 'NULL' : $list[$config['head_tab_detil_tl']];
				}else{
					$newD['DTL_TL'] = 'NULL';
				}

				if ($config['head_tab_detil_date_in'] != null) {
					if ($input['table'] == 'TX_HDR_REC') {
						$newD['DTL_DATE_IN'] = empty($list[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.$list[$config['head_tab_detil_date_in']].'\',\'yyyy-MM-dd\')';
					}else{
						$newD['DTL_DATE_IN'] = empty($find[$config['head_tab_detil_date_in']]) ? 'NULL' : 'to_date(\''.$find[$config['head_tab_detil_date_in']].'\',\'yyyy-MM-dd\')';
					}
				}else{
					$newD['DTL_DATE_IN'] = 'NULL';
				}

				if ($config['head_tab_detil_date_out_old'] != null and ($input['table'] == 'TX_HDR_DEL' and $find['del_extend_status'] != 'N') ) {
					$findEx = DB::connection('omcargo')->select(DB::raw("
						SELECT 
						X.DTL_OUT AS date_out_old,
						Y.DTL_OUT AS date_out 
						FROM (
						SELECT 
						DEL_ID,DEL_NO,DTL_OUT,DEL_EXTEND_FROM 
						FROM 
						TX_HDR_DEL A 
						JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
						) X
						JOIN (
						SELECT 
						DEL_ID,DEL_NO,DTL_OUT,DEL_EXTEND_FROM 
						FROM 
						TX_HDR_DEL A 
						JOIN TX_DTL_DEL B ON A.DEL_ID=B.HDR_DEL_ID
						) Y
						ON X.DEL_NO=Y.DEL_EXTEND_FROM WHERE Y.DEL_NO='".$find[$config['head_no']]."'
						"));
					if (empty($findEx)) {
						$newD['DTL_DATE_OUT_OLD'] = 'NULL';
						$newD['DTL_DATE_OUT'] = 'NULL';
					}else{
						$findEx = $findEx[0];
						$findEx = (array)$findEx;
						$newD['DTL_DATE_OUT_OLD'] = empty($findEx['date_out_old']) ? 'NULL' : 'to_date(\''.$findEx['date_out_old'].'\',\'yyyy-MM-dd\')';
						$newD['DTL_DATE_OUT'] = empty($findEx['date_out']) ? 'NULL' : 'to_date(\''.$findEx['date_out'].'\',\'yyyy-MM-dd\')';
					}
				}else{
					$newD['DTL_DATE_OUT_OLD'] = 'NULL';

					if ($config['head_tab_detil_date_out'] != null) {
						if ($input['table'] == 'TX_HDR_DEL') {
							$newD['DTL_DATE_OUT'] = empty($list[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.$list[$config['head_tab_detil_date_out']].'\',\'yyyy-MM-dd\')';
						}else{
							$newD['DTL_DATE_OUT'] = empty($find[$config['head_tab_detil_date_out']]) ? 'NULL' : 'to_date(\''.$find[$config['head_tab_detil_date_out']].'\',\'yyyy-MM-dd\')';
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
		return response()->json($tariffResp);
    }

    public static function approvalRequest($input){
    	$input['table'] = strtoupper($input['table']);
		$config = static::config($input['table']);
		$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
		if (empty($find)) {
			return response()->json(['result' => "Fail, requst not found!", "Success" => false]);
		}
		$find = (array)$find[0];
		if ($input['approved'] == 'false') {
			DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 4
			]);
			return response()->json(['result' => "Success, rejected requst"]);
		}
		$uper = DB::connection('eng')->table('V_PAY_SPLIT')->where('BOOKING_NUMBER',$find['head_no'])->get();
		if (empty($uper)) {
			return response()->json(['result' => "Fail, uper and tariff not found!", "Success" => false]);
		}
		$uper = $uper[0];
		$uper = (array)$uper;
		$cekU = DB::connection('eng')->table('TX_LOG')->where('TEMP_HDR_ID',$uper['temp_hdr_id'])->get();
		if ($cekU[0]->result_flag != 'S') {
			return response()->json(['result' => "Fail", 'logs' => $cekU[0]]);
		}
		$uperD = DB::connection('eng')->table('TX_TEMP_TARIFF_DTL')->where('TEMP_HDR_ID',$uper['temp_hdr_id'])->get();

		$datenow    = Carbon::now()->format('Y-m-d');
		// $branch_code = DB::connection('mdm')->table('TM_BRANCH')->where('BRANCH_ID',$uper['branch_id'])->get();
		// $branch_code = $branch_code[0]->branch_code;
		$headU = new TxHdrUper;
		// $headU->uper_no // dari triger
		$headU->uper_org_id = $uper['branch_org_id'];
		$headU->uper_cust_id = $uper['customer_id'];
		$headU->uper_cust_name = $uper['alt_name'];
		$headU->uper_cust_npwp = $uper['npwp'];
		$headU->uper_cust_address = $uper['address'];
		$headU->uper_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')");
		$headU->uper_amount = $uper['uper_total'];
		$headU->uper_currency_code = $uper['currency'];
		$headU->uper_status = 'P'; // blm fix
		$headU->uper_context = 'BRG'; // blm fix
		$headU->uper_sub_context = 'BRG03'; // blm fix
		$headU->uper_terminal_code = $find[$config['head_terminal_code']];
		$headU->uper_branch_id = $uper['branch_id'];
		// $headU->uper_branch_code = $branch_code; // kemungkinan ditambah
		$headU->uper_vessel_name = $find[$config['head_vessel_name']];
		$headU->uper_faktur_no = '12576817'; // ? dari triger bf i
		$headU->uper_trade_type = $uper['trade_type'];
		$headU->uper_req_no = $uper['booking_number'];
		$headU->uper_ppn = $uper['ppn'];
		// $headU->uper_paid // ? pasti null
		// $headU->uper_paid_date // ? pasti null
		$headU->uper_percent = $uper['uper_percent'];
		$headU->uper_dpp = $uper['dpp'];
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

		foreach ($uperD as $list) {
			$list = (array)$list;
			$set_data = [
				"uper_hdr_id" => $headU->uper_id,
				// "dtl_line" => , // perlu konfimasi
				// "dtl_line_desc" => , // perlu konfimasi
				// "dtl_line_context" => , // perlu konfimasi
				"dtl_service_type" => $list['group_tariff_name'],
				"dtl_amout" => $list['uper'], // blm fix
				"dtl_ppn" => $list["ppn"],
				// "dtl_masa1" => , // cooming soon
				// "dtl_masa12" => , // cooming soon
				// "dtl_masa2" => , // cooming soon
				"dtl_tariff" => $list["tariff"],
				// "dtl_package" => , // cooming soon
				"dtl_qty" => $list["qty"],
				"dtl_unit" => $list["unit_id"],
				"DTL_GROUP_TARIFF_ID" => $list["group_tariff_id"],
				"DTL_GROUP_TARIFF_NAME" => $list["group_tariff_name"],
				// "DTL_BL" => $list[""], // tunggu dari adi
				"DTL_DPP" => $list["tariff_cal"],
				"DTL_COMMODITY" => $list["commodity_name"],
				"DTL_EQUIPMENT" => $list["equipment_name"],
				"dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD')")
			];
			DB::connection('omcargo')->table('TX_DTL_UPER')->insert($set_data);
		}

		DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
			$config['head_status'] => 3
		]);

		return response()->json(['result' => "Success, approved request!"]);
    }

    private static function config($input){
    	$requst_config = [
        	"TX_HDR_BM" => [
        		"head_nota_id" => 13,
        		"head_tab" => "TX_HDR_BM",
        		"head_tab_detil" => "TX_DTL_BM",
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
        		"head_nota_id" => "14",
        		"head_tab" => "TX_HDR_REC",
        		"head_tab_detil" => "TX_DTL_REC",
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
        		"head_nota_id" => "15",
        		"head_tab" => "TX_HDR_DEL",
        		"head_tab_detil" => "TX_DTL_DEL",
        		"head_tab_detil_tl" => null,
        		"head_tab_detil_date_in" => 'del_atd',
        		"head_tab_detil_date_out" => 'dtl_out',
        		"head_tab_detil_date_out_old" => 'extension',
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
