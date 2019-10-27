<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\BillingEngine;

class RequestBooking{

	public static function sendRequest($input){
		$requst_config = static::config();
		$input['table'] = strtoupper($input['table']);
		$config = $requst_config[$input['table']];
		$find = DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->get();
		if (empty($find)) {
			return response()->json_decode(['result' => "Fail, requst not found!"]);
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
			$setH['P_TL'] = 'N';
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

		$tariffResp = BillingEngine::calculateTariff($set_data);

		if ($tariffResp['out_status'] == true) {
			DB::connection('omcargo')->table($input['table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 2
			]);
		}
		return response()->json($tariffResp);
    }

    private static function config(){
    	return $requst_config = [
        	"TX_HDR_BM" => [
        		"head_nota_id" => 13,
        		"head_tab" => "TX_HDR_BM",
        		"head_tab_detil" => "TX_DTL_BM",
        		"head_status" => "bm_status",
        		"head_primery" => "bm_id",
        		"head_forigen" => "hdr_bm_id",
        		"head_no" => "bm_no",
        		"head_by" => "bm_create_by",
        		"head_date" => "bm_date",
        		"head_branch" => "bm_branch_id",
        		"head_cust" => "bm_cust_id",
        		"head_date_in" => null,
        		"head_date_out" => null,
        		"head_trade" => "bm_trade_type"
        	],
        	"TX_HDR_REC" => [
        		"head_nota_id" => "14",
        		"head_tab" => "TX_HDR_REC",
        		"head_tab_detil" => "TX_DTL_REC",
        		"head_status" => "rec_status",
        		"head_primery" => "rec_id",
        		"head_forigen" => "hdr_rec_id",
        		"head_no" => "rec_no",
        		"head_by" => "rec_create_by",
        		"head_date" => "rec_date",
        		"head_branch" => "rec_branch_id",
        		"head_cust" => "rec_cust_id",
        		"head_date_in" => null,
        		"head_date_out" => null,
        		"head_trade" => "rec_trade_type"
        	],
        	"TX_HDR_DEL" => [
        		"head_nota_id" => "15",
        		"head_tab" => "TX_HDR_DEL",
        		"head_tab_detil" => "TX_DTL_DEL",
        		"head_status" => "del_status",
        		"head_primery" => "del_id",
        		"head_forigen" => "del_hdr_id",
        		"head_no" => "del_no",
        		"head_by" => "del_create_by",
        		"head_date" => "del_date",
        		"head_branch" => "del_branch_id",
        		"head_cust" => "del_cust_id",
        		"head_date_in" => null,
        		"head_date_out" => null,
        		"head_trade" => "del_trade_type"
        	]
        ];
    }
}
