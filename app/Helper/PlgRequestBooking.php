<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\PlgEInvo;
use App\Helper\PlgFunctTOS;
use App\Helper\PlgConnectedExternalApps;
use App\Helper\BillingEngine;
use App\Models\OmUster\TxHdrNota;
use App\Models\OmUster\TxPayment;

class PlgRequestBooking{
	// PLG
		private static function calculateHours($st,$ed){
			$st = strtotime($st);
			$ed = strtotime($ed);
			$difference = abs($ed - $st)/3600;
			return ceil($difference);
		}

		private static function getLastContFromTX_GATEIN($list,$hdr,$config){
			$tglIn 	= DB::connection('omuster')
				->table('TX_GATEIN')
				->where('GATEIN_CONT', $list[$config['DTL_BL']])
				->where('GATEIN_BRANCH_ID', $hdr[$config['head_branch']])
				->where('GATEIN_BRANCH_CODE', $hdr[$config['head_branch_code']])
				->orderBy("GATEIN_CREATE_DATE", "DESC")
				->first();
			return $dateIn = $tglIn->gatein_date;
		}

		private static function getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config){
			$in = [3,13,14];
			$tglIn 	= DB::connection('omuster')
			->table('TX_HISTORY_CONTAINER')
			->where('NO_CONTAINER', $list[$config['DTL_BL']])
			->whereIn('KEGIATAN', $in)
			->orderBy("HISTORY_DATE", "DESC")
			->first();
			if (empty($tglIn)) {
				return [
					"result_flag"=>"F",
					"result_msg"=>"Not found countainer!",
					"no_req"=>$hdr[$config['head_no']],
					"Success"=>false
				];
			}
			return $dateIn = $tglIn->history_date;
		}

		private static function getDTL_VIA($config,$list,$hdr,$input){
			if (empty($config['DTL_VIA'])) {
				$DTL_VIA = 'NULL';
			}else{
				if (is_array($config['DTL_VIA'])) {
					$DTL_VIA = empty($list[$config['DTL_VIA']['rec']]) ? 'NULL' : $list[$config['DTL_VIA']['rec']];
				}else{
					$DTL_VIA = empty($list[$config['DTL_VIA']]) ? 'NULL' : $list[$config['DTL_VIA']];
				}
			}
			return $DTL_VIA;
		}

		private static function getDTL_BL($config,$list,$hdr,$input){
			if (empty($config['DTL_BL'])) {
				$DTL_BL = 'NULL';
			}else{
				$DTL_BL = empty($list[$config['DTL_BL']]) ? 'NULL' : strtoupper($list[$config['DTL_BL']]);
			}
			return $DTL_BL;
		}

		private static function getDTL_FUMI_TYPE($config,$list,$hdr,$input){
			if (empty($config['DTL_FUMI_TYPE'])) {
				$DTL_FUMI_TYPE = 'NULL';
			}else{
				$DTL_FUMI_TYPE = empty($list[$config['DTL_FUMI_TYPE']]) ? 'NULL' : strtoupper($list[$config['DTL_FUMI_TYPE']]);
			}
			return $DTL_FUMI_TYPE;
		}

		private static function getDTL_PKG_ID($config,$list,$hdr,$input){
			if (empty($config['DTL_PKG_ID'])) {
				$DTL_PKG_ID = 8;
			}else{
				$DTL_PKG_ID = empty($list[$config['DTL_PKG_ID']]) ? 'NULL' : $list[$config['DTL_PKG_ID']];
			}
			return $DTL_PKG_ID;
		}

		private static function getDTL_CMDTY_ID($config,$list,$hdr,$input){
			if (empty($config['DTL_CMDTY_ID'])) {
				$DTL_CMDTY_ID = 'NULL';
			}else{
				$DTL_CMDTY_ID = empty($list[$config['DTL_CMDTY_ID']]) ? 'NULL' : $list[$config['DTL_CMDTY_ID']];
			}
			return $DTL_CMDTY_ID;
		}

		private static function getDTL_CHARACTER($config,$list,$hdr,$input){
			if (empty($config['DTL_CHARACTER'])) {
				$DTL_CHARACTER = 'NULL';
			}else{
				if (empty($list[$config['DTL_CHARACTER']])) {
					$DTL_CHARACTER = 'NULL';
				}else if ($list[$config['DTL_CHARACTER']] == 'Y'){
					$DTL_CHARACTER = 2;
				}else if ($list[$config['DTL_CHARACTER']] == 'N'){
					$DTL_CHARACTER = 0;
				}
			}
			return $DTL_CHARACTER;
		}

		private static function getDTL_CONT_SIZE($config,$list,$hdr,$input){
			if (empty($config['DTL_CONT_SIZE'])) {
				$DTL_CONT_SIZE = 'NULL';
			}else{
				$DTL_CONT_SIZE = empty($list[$config['DTL_CONT_SIZE']]) ? 'NULL' : $list[$config['DTL_CONT_SIZE']];
			}
			return $DTL_CONT_SIZE;
		}

		private static function getDTL_CONT_TYPE($config,$list,$hdr,$input){
			if (empty($config['DTL_CONT_TYPE'])) {
				$DTL_CONT_TYPE = 'NULL';
			}else{
				$DTL_CONT_TYPE = empty($list[$config['DTL_CONT_TYPE']]) ? 'NULL' : $list[$config['DTL_CONT_TYPE']];
			}
			return $DTL_CONT_TYPE;
		}

		private static function getDTL_CONT_STATUS($config,$list,$hdr,$input){
			if (empty($config['DTL_CONT_STATUS'])) {
				$DTL_CONT_STATUS = 'NULL';
			}else{
				$DTL_CONT_STATUS = empty($list[$config['DTL_CONT_STATUS']]) ? 'NULL' : $list[$config['DTL_CONT_STATUS']];
			}
			return $DTL_CONT_STATUS;
		}

		private static function getDTL_UNIT_ID($config,$list,$hdr,$input){
			if (empty($config['DTL_UNIT_ID'])) {
				$DTL_UNIT_ID = 'NULL';
			}else if ($config['DTL_UNIT_ID'] == 6) {
				$DTL_UNIT_ID = 6;
			}else{
				$DTL_UNIT_ID = empty($list[$config['DTL_UNIT_ID']]) ? 'NULL' : $list[$config['DTL_UNIT_ID']];
			}
			return $DTL_UNIT_ID;
		}

		private static function getDTL_QTY($config,$list,$hdr,$input){
			if (empty($config['DTL_QTY'])) {
				$DTL_QTY = 'NULL';
			}else if ($config['DTL_QTY'] == 1) {
				$DTL_QTY = 1;
			}else if (is_array($config['DTL_QTY'])) {
				if (!empty($config['DTL_QTY']['func'])) {
					$DTL_QTY = static::$config['DTL_QTY']['func']($config['DTL_QTY']['start'],$config['DTL_QTY']['end']);
				}else{
					$DTL_QTY = 'NULL';
				}
			}else{
				$DTL_QTY = empty($list[$config['DTL_QTY']]) ? 'NULL' : $list[$config['DTL_QTY']];
			}
			return $DTL_QTY;
		}

		private static function getDTL_PFS($config,$list,$hdr,$input){
			$getPFS = DB::connection('mdm')->table('TM_COMP_NOTA')->where('NOTA_ID', $input['nota_id'])->where('BRANCH_ID',$hdr[$config['head_branch']])->where('BRANCH_CODE',$hdr[$config['head_branch_code']])->where('GROUP_TARIFF_ID', 15)->count();
			if ($getPFS > 0) {
				$DTL_PFS = 'Y';
			}else{
				$DTL_PFS = 'N';
			}
			return $DTL_PFS;
		}

		private static function getDTL_STACK_AREA($config,$list,$hdr,$input){
			$DTL_STACK_AREA = 'NULL';
			if ($config['head_table'] == "TX_HDR_DEL" || $config['head_table'] == 'TX_HDR_DEL_CARGO') {
				$DTL_STACK_AREA = '1';
			}
			if (!empty($config['DTL_STACK_AREA'])) {
				$DTL_STACK_AREA = empty($list[$config['DTL_STACK_AREA']]) ? 'NULL' : $list[$config['DTL_STACK_AREA']];
			}
			// if (in_array($config['head_nota_id'], ["14", "15", 14, 15])) {
			// 	$DTL_STACK_AREA = empty($list['dtl_stacking_type_id']) ? 'NULL' : $list['dtl_stacking_type_id'];
			// }
			return $DTL_STACK_AREA;
		}

		private static function getDTL_TL($config,$list,$hdr,$input){
			if (empty($config['DTL_TL'])) {
				$DTL_TL = 'NULL';
			}else if ($config['DTL_TL'] == 'Y') {
				$DTL_TL = 'Y';
			}else{
				$DTL_TL = empty($list[$config['DTL_TL']]) ? 'NULL' : $list[$config['DTL_TL']];
			}
			return $DTL_TL;
		}

		private static function getDTL_DATE_IN($config,$list,$hdr,$input){
			if (empty($config['DTL_DATE_IN'])) {
				$DTL_DATE_IN = 'NULL';
			}else{
				if (in_array($config['DTL_DATE_IN'], ["TX_GATEIN"])) {
					$dateIn = static::getLastContFromTX_GATEIN($list,$hdr,$config);
					$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
				} else if (in_array($config['DTL_DATE_IN'], ["TX_HISTORY_CONTAINER"])){
					$dateIn = static::getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config);
					if (!empty($config['DTL_STACK_DATE'])) {
						DB::connection('omuster')->table($config['head_tab_detil'])->where($config['DTL_PRIMARY'],$list[$config['DTL_PRIMARY']])->update([$config['DTL_STACK_DATE']=>$dateIn]);
					}
					$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
				} else if (is_array($config['DTL_DATE_IN'])){
					$ddiType = $config['DTL_DATE_IN']['paymethod'.$hdr[$config['head_paymethod']]];
					if (in_array($ddiType, ["TX_HISTORY_CONTAINER"])) {
						$dateIn = static::getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config);
						$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}else{
						$DTL_DATE_IN = empty($list[$ddiType]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$ddiType])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}
				} else {
					$DTL_DATE_IN = empty($list[$config['DTL_DATE_IN']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_IN']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
				}
			}
			return $DTL_DATE_IN;
		}

		private static function getDTL_DATE_OUT($config,$list,$hdr,$input){
			if (empty($config['DTL_DATE_OUT'])) {
				$DTL_DATE_OUT = 'NULL';
			}else{
				if ($config['head_table'] == 'TX_HDR_DEL_CARGO') {
					$tglOut = DB::connection('omuster')->table('TX_GATEOUT')->orderBy("GATEOUT_DATE", "DESC")->first();
					$dateout = $tglOut->gateout_date;
					$DTL_DATE_OUT = 'to_date(\''.\Carbon\Carbon::parse($dateout)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
				} else {
					if (is_array($config['DTL_DATE_OUT'])) {
						$dtlOut = $list[$config['DTL_DATE_OUT']['paymethod'.$hdr[$config['head_paymethod']]]];
						$DTL_DATE_OUT = empty($dtlOut) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($dtlOut)->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}else{
						$DTL_DATE_OUT = empty($list[$config['DTL_DATE_OUT']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_OUT']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
					}
				}
			}

			return $DTL_DATE_OUT;
		}

		private static function getDTL_DATE_OUT_OLD($config,$list,$hdr){
			if (!empty($config['head_ext_status']) and $hdr[$config['head_ext_status']] == 'Y') {
				$getOldIdHdr = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$hdr[$config['head_no']])->first();
				$getOldIdHdr = (array)$getOldIdHdr;
				$getOldIdHdr = $getOldIdHdr[$config['head_primery']];
				$getOldDtDtl = DB::connection('omuster')->table($config['head_tab_detil'])->where([
					$config['head_forigen'] => $hdr[$config['head_primery']],
					$config['DTL_BL'] => $list[$config['DTL_BL']]
				])->first();
				$getOldDtDtl = (array)$getOldDtDtl;
				$DTL_DATE_OUT_OLD = 'to_date(\''.\Carbon\Carbon::parse($getOldDtDtl[$config['DTL_DATE_OUT']])->format('Y-m-d').'\',\'yyyy-MM-dd\')';
			}else{
				$DTL_DATE_OUT_OLD = 'NULL';
			}
			return $DTL_DATE_OUT_OLD;
		}

		public static function calculateTariffBuild($find, $input, $config, $canceledReqPrepare){
			if (in_array($config['kegiatan'], [7,8]) and $find[$config['head_status']] == 1) {
				return [
					"result_flag"=>"S",
					"result_msg"=>"Success",
					"no_req"=>$find[$config['head_no']],
					"Success"=>true
				];
			}
			$setH = static::calculateTariffBuildHead($find, $input, $config, $canceledReqPrepare);// build head
			// build detil
				$setD = [];
				$detil = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']]);
				if (!empty($config['DTL_IS_ACTIVE'])) {
					$detil->where($config['DTL_IS_ACTIVE'], 'Y');
				}
				$detil = $detil->get();
				foreach ($detil as $list) {
					$list = (array)$list;
					$dtl = static::calculateTariffBuildDetail($find, $list, $input, $config);
					if (!empty($dtl['Success']) and $dtl['Success'] == false) {
						return $dtl;
					}
					$setD[] = $dtl;
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
			$tariffResp = BillingEngine::calculateTariff($set_data);
			if (in_array($config['kegiatan'], [1])){
				$tariffResp['detil_data'] = $detil;
			}
			return $tariffResp;
		}

		private static function calculateTariffBuildHead($data, $input, $config, $canceledReqPrepare){
			$result = [];
			$result['P_SOURCE_ID'] = "NPKS_BILLING";
			$result['P_NOTA_ID'] = $input['nota_id'];
			$result['P_BRANCH_ID'] = $data[$config['head_branch']];
			$result['P_BRANCH_CODE'] = $data[$config['head_branch_code']];
			$result['P_CUSTOMER_ID'] = $data[$config['head_cust']];
			$result['P_PBM_INTERNAL'] = 'N';
			if (empty($canceledReqPrepare)) {
				$result['P_BOOKING_NUMBER'] = $data[$config['head_no']];
			}else{
				$result['P_BOOKING_NUMBER'] = $canceledReqPrepare['canc']['cancelled_no'];
			}
			$result['P_REALIZATION'] = 'N';
			$result['P_RESTITUTION'] = 'N';
			if (empty($config['p_tarde'])) {
				$result['P_TRADE'] = 'NULL';
			}else{
				$result['P_TRADE'] = $data[$config['p_tarde']];
			}
			$result['P_USER_ID'] = $data[$config['head_by']];
			return $result;
		}

		private static function calculateTariffBuildDetail($hdr, $list, $input, $config){
			$newD = [];
			$newD['DTL_VIA'] = static::getDTL_VIA($config,$list,$hdr,$input);
			$newD['DTL_BL'] = static::getDTL_BL($config,$list,$hdr,$input);
			$newD['DTL_FUMI_TYPE'] = static::getDTL_FUMI_TYPE($config,$list,$hdr,$input);
			$newD['DTL_PKG_ID'] = static::getDTL_PKG_ID($config,$list,$hdr,$input);
			$newD['DTL_CMDTY_ID'] = static::getDTL_CMDTY_ID($config,$list,$hdr,$input);
			$newD['DTL_CHARACTER'] = static::getDTL_CHARACTER($config,$list,$hdr,$input);
			$newD['DTL_CONT_SIZE'] = static::getDTL_CONT_SIZE($config,$list,$hdr,$input);
			$newD['DTL_CONT_TYPE'] = static::getDTL_CONT_TYPE($config,$list,$hdr,$input);
			$newD['DTL_CONT_STATUS'] = static::getDTL_CONT_STATUS($config,$list,$hdr,$input);
			$newD['DTL_UNIT_ID'] = static::getDTL_UNIT_ID($config,$list,$hdr,$input);
			$newD['DTL_QTY'] = static::getDTL_QTY($config,$list,$hdr,$input);
			$newD['DTL_PFS'] = static::getDTL_PFS($config,$list,$hdr,$input);
			$newD['DTL_BM_TYPE'] = 'NULL';
			$newD['DTL_STACK_AREA'] = static::getDTL_STACK_AREA($config,$list,$hdr,$input);
			$newD['DTL_TL'] = static::getDTL_TL($config,$list,$hdr,$input);
			$newD['DTL_DATE_IN'] = static::getDTL_DATE_IN($config,$list,$hdr,$input);
			$newD['DTL_DATE_OUT'] = static::getDTL_DATE_OUT($config,$list,$hdr,$input);
			$newD['DTL_DATE_OUT_OLD'] = static::getDTL_DATE_OUT_OLD($config,$list,$hdr,$input);
			return $newD;
		}

		private static function migrateNotaData($find, $config, $findCanc){
			if (in_array($config['kegiatan'], [7,8]) and $find[$config['head_status']] == 2) {
				return ['result' => null, "Success" => true];
			}
			$datenow = Carbon::now()->format('Y-m-d');
			$no_nota = '';
			if (empty($findCanc)) {
				$findReqNo = $find[$config['head_no']];
			}else{
				$findReqNo = $findCanc->cancelled_no;
			}
			$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$findReqNo."'";
			$tarifs = DB::connection('eng')->select(DB::raw($query));
			if (count($tarifs) == 0) {
				return ['result' => "Fail, proforma and tariff not found!", "Success" => false];
			}
			foreach ($tarifs as $tarif) {
				$tarif = (array)$tarif;
				$cekOldNota = TxHdrNota::where('nota_req_no', $findReqNo)->first();
				if (empty($cekOldNota)) {
					$headU = new TxHdrNota;
				}else{
					$headU = TxHdrNota::find($cekOldNota->nota_id);
				}
							// $headU->app_id =$find['app_id'];
				$headU->nota_group_id = $tarif['nota_id'];
				$headU->nota_org_id = $tarif['branch_org_id'];
				$headU->nota_cust_id = $tarif['customer_id'];
				$headU->nota_cust_name = $tarif['alt_name'];
				$headU->nota_cust_npwp = $tarif['npwp'];
				$headU->nota_cust_address = $tarif['address'];
				$headU->nota_date = \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')");
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
				$no_nota .= $headU->nota_no.', ';
				$queryAgain = "SELECT * FROM TX_TEMP_TARIFF_SPLIT WHERE TEMP_HDR_ID = '".$tarif['temp_hdr_id']."' AND CUSTOMER_ID = '".$tarif['customer_id']."'";
				$group_tariff = DB::connection('eng')->select(DB::raw($queryAgain));

				$countLine = 0;
				DB::connection('omuster')->table('TX_DTL_NOTA')->where('nota_hdr_id',$headU->nota_id)->delete();
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
							"dtl_cont_size" => $list["cont_size"],
							"dtl_cont_type" => $list["cont_type"],
							"dtl_cont_status" => $list["cont_status"],
							"dtl_sub_tariff" => $list["sub_tariff"],
							"dtl_create_date" => \DB::raw("TO_DATE('".$datenow."', 'YYYY-MM-DD HH24:MI')")
						];
						DB::connection('omuster')->table('TX_DTL_NOTA')->insert($set_data);
					}
				}
			}
			return ['result' => "Created Nota No : ".$no_nota, "Success" => true];
		}

		private static function canceledReqPrepare($input, $config){
			$cnclHdr = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->first();
			if (empty($cnclHdr)) {
				return ['Success' => false, 'result' => 'canceled request not found'];
			}
			$reqsHdr = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$cnclHdr->cancelled_req_no)->first();
			if (empty($reqsHdr)) {
				return ['Success' => false, 'result' => 'canceled request not found'];
			}
			$reqsHdr = (array)$reqsHdr;
			DB::connection('omuster')->table($config['head_tab_detil'])->where([
				$config['head_forigen'] => $reqsHdr[$config['head_primery']]
			])->update([
				$config['DTL_IS_ACTIVE'] => 'Y',
				$config['DTL_IS_CANCEL'] => 'N'
			]);
			$cnclDtl = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id',$cnclHdr->cancelled_id)->get();
			foreach ($cnclDtl as $list) {
				$noDtl = $list->cancl_cont.$list->cancl_si;
				DB::connection('omuster')->table($config['head_tab_detil'])->where([
					$config['head_forigen'] => $reqsHdr[$config['head_primery']],
					$config['DTL_BL'] => $noDtl
				])->update([
					$config['DTL_IS_ACTIVE'] => 'N',
					$config['DTL_IS_CANCEL'] => 'Y'
				]);
			}

			return ['Success' => true, 'find' => $reqsHdr, 'canc' => (array)$cnclHdr];
		}

	    public static function sendRequestPLG($input){
			$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			if (empty($config) or empty($config->api_set)) {
				return ['Success' => false, 'result' => "Fail, nota not set!"];
			}
			if ($config->flag_status == 'N') {
				return ['Success' => false, 'result' => "Fail, nota not active!"];
			}
			$config = json_decode($config->api_set, true);

			// request batal
			$canceledReqPrepare = null;
			if (!empty($input['canceled']) and $input['canceled'] == 'true') {
				$canceledReqPrepare = static::canceledReqPrepare($input, $config);
				if ($canceledReqPrepare['Success'] == false) {
					return $canceledReqPrepare;
				}
			}
			// request batal

			if (empty($canceledReqPrepare)) {
				$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->first();
			}else{
				$find = $canceledReqPrepare['find'];
			}
			if (empty($find)) {
				return ['Success' => false, 'result' => "Fail, requst not found!"];
			}
			$find = (array)$find;
			if ($find[$config['head_status']] == 3 and empty($canceledReqPrepare)) {
				return ['Success' => false, 'result' => "Fail, requst already send!"];
			}

			$his_cont = [];
			$tariffResp = static::calculateTariffBuild($find, $input, $config, $canceledReqPrepare);
			if (empty($tariffResp['result_flag']) or $tariffResp['result_flag'] != 'S') {
				return $tariffResp;
			} else if ($tariffResp['result_flag'] == 'S' and empty($canceledReqPrepare)) {
				DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 2
				]);
				$confKgt = $config['kegiatan'];
				if (is_array($confKgt)) {
					$confKgt = $confKgt[0];
				}
				if (in_array($confKgt, [1])) {
					foreach ($tariffResp['detil_data'] as $list) {
						$list = (array)$list;
						$findTsCont = [
							'cont_no' => $list[$config['DTL_BL']],
							'branch_id' => $find[$config['head_branch']],
							'branch_code' => $find[$config['head_branch_code']]
						];
						$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
						if (empty($cekTsCont)) {
							$cont_counter = 0;
						}else{
							$cont_counter = $cekTsCont->cont_counter;
						}
						$arrStoreTsContAndTxHisCont = [
							'history_date' => Carbon::now()->format('Y-m-d h:i:s'),
							'cont_no' => $list[$config['DTL_BL']],
							'branch_id' => $find[$config['head_branch']],
							'branch_code' => $find[$config['head_branch_code']],
							'cont_location' => 'GATO',
							'cont_size' => $list[$config['DTL_CONT_SIZE']],
							'cont_type' => $list[$config['DTL_CONT_TYPE']],
							'cont_counter' => $cont_counter,
							'no_request' => $find[$config['head_no']],
							'kegiatan' => $confKgt,
							'id_user' => $input["user"]->user_id,
							'status_cont' => $list[$config['DTL_CONT_STATUS']],
							'vvd_id' => $find[$config['head_vvd']]
						];
						$his_cont[] = static::storeTsContAndTxHisCont($arrStoreTsContAndTxHisCont);
					}
				}
			}
			$tariffResp['his_cont'] = $his_cont;
			if (!empty($canceledReqPrepare)) {
				DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->update(['cancelled_status'=>2]);
			}
			return $tariffResp;
	    }

	    public static function viewTempTariffPLG($input){
	    	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			$config = json_decode($config->api_set, true);
			$findCanc = null;
	    	if (!empty($input['canceled']) and $input['canceled'] == 'true') {
				$findCanc = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->first();
				$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$findCanc->cancelled_req_no)->get();
			}else{
		    	$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
			}
	    	$find = (array)$find[0];
	    	if (count($find) == 0) {
	    		return ['Success' => false, 'result' => 'fail, not found data!'];
	    	}
	    	if (empty($findCanc)) {
		    	$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$find[$config['head_no']]."'";
	    	}else{
	    		$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$findCanc->cancelled_no."'";
	    	}
	    	$result = [];
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
	    			'dpp' => $getH->dpp,
	    			'ppn' => $getH->ppn,
	    			'total' => $getH->total,
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
			$findCanc = null;
			if (!empty($input['canceled']) and $input['canceled'] == 'true') {
				$findCanc = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->first();
			}

			if (empty($findCanc)) {
				$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
				if (empty($find)) {
					return ['result' => "Fail, requst not found!", "Success" => false];
				}
				$find = (array)$find[0];
				$retHeadNo = $find[$config['head_no']];

				$upHead =DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id']);
			}else{
				$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$findCanc->cancelled_req_no)->get();
				if (empty($find)) {
					return ['result' => "Fail, requst not found!", "Success" => false];
				}
				$find = (array)$find[0];
				$retHeadNo = $findCanc->cancelled_no;

				$upHead = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id']);
			}

			if (
				(empty($findCanc) and $find[$config['head_status']] == 3 and $input['approved'] == 'true') or
				(!empty($findCanc) and $findCanc->cancelled_status == 3 and $input['approved'] == 'true')
			) {
				return ['result' => "Fail, requst already approved!", 'no_req' => $retHeadNo, "Success" => false];
			}
			$nota = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no',$retHeadNo)->whereNotIn('nota_status', [4])->get();
			if (count($nota) > 0) {
				return ['result' => "Fail, request already exist on proforma!", 'no_req' => $retHeadNo, "Success" => false];
			}

			if ($input['approved'] == 'false') {
				if (empty($findCanc)){
					 DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
						$config['head_status'] => 4,
						$config['head_mark'] => $input['msg']
					]);
				}else{
					DB::connection('omuster')->table('tx_hdr_cancelled')->where('cancelled_id',$input['id'])->update([
						'cancelled_status' => 4,
						'cancelled_mark' => $input['msg']
					]);
				}

				return ['result' => "Success, rejected requst", 'no_req' => $retHeadNo];
			}

			$migrateTariff = true;
			if ($find[$config['head_paymethod']] == 2) {
				$migrateTariff = false;
			}
			$pesan = [];
			$pesan['result'] = null;
			if ($migrateTariff == true) {
				$pesan = static::migrateNotaData($find, $config, $findCanc);
				if ($pesan['Success'] == false) {
					return $pesan;
				}
			}

			if (empty($findCanc)){
				DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
					$config['head_status'] => 3,
					$config['head_mark'] => $input['msg']
				]);
			}else{
				DB::connection('omuster')->table('tx_hdr_cancelled')->where('cancelled_id',$input['id'])->update([
					'cancelled_status' => 3,
					'cancelled_mark' => $input['msg']
				]);
			}


			$sendRequestBooking = null;
			if ($find[$config['head_paymethod']] == 2) {
				$sendRequestBooking = PlgFunctTOS::sendRequestBookingPLG(['id' => $input['id'] ,'config' => $config]);
			}

			return [
				'result' => "Success, approved request! ".$pesan['result'],
				"note" => $pesan['result'],
				'no_req' => $retHeadNo,
				'sendRequestBooking' => $sendRequestBooking
			];
	    }

	    public static function confirmRealisasion($input){
	    	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $input['nota_id'])->first();
			$config = json_decode($config->api_set, true);
			$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->first();
			$find = (array)$find;
			if ($find[$config['head_status']] == 5) {
				return [
					'Success' => false,
					'result' => "Fail, realisasion is confirmed!",
					'no_req' => $find[$config['head_no']]
				];
			}

			if ($input['nota_id'] != 20 /*brg rec*/ or $input['nota_id'] != 21 /*brg del*/) { // tdk samsa dengan req brg
				$notIN = [$config['DTL_FL_REAL_F'][count($config['DTL_FL_REAL_F'])-1]];
				$dtl = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $input['id'])->where($config['DTL_IS_ACTIVE'],'Y')->whereNotIn($config['DTL_FL_REAL'], $notIN)->get();
				if (count($dtl) > 0) {
					return [
						'Success' => false,
						'result' => "Fail, realisasion is not finish!",
						'no_req' => $find[$config['head_no']]
					];
				}
			}

			$pesan = [];
			$pesan['result'] = null;
			if ($find[$config['head_paymethod']] == 2) {
				// calculate tariff
					$tariffResp = static::calculateTariffBuild($find, $input, $config, null);
					if ($tariffResp['result_flag'] != 'S') {
						return $tariffResp;
					}
				// calculate tariff
				// migrate nota
					$pesan = static::migrateNotaData($find, $config, null);
					if ($pesan['Success'] == false) {
						return $pesan;
					}
				// migrate nota
			}
			DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->update([
				$config['head_status'] => 5
			]);
			return [
				'result' => "Success, confirm realisasion! ".$pesan['result'],
				"note" => $pesan['result'],
				'no_req' => $find[$config['head_no']]
			];
	    }

	    public static function approvalProformaPLG($input){
	    	$getNota = TxHdrNota::find($input['nota_id']);
            if (empty($getNota)) {
            	return ['result' => "Fail, proforma not found!", "Success" => false];
            }
	    	$cekNota = TxHdrNota::where([
            	'nota_id'=>$input['nota_id'],
            	'nota_status'=>'1'
            ])->count();
            if ($cekNota = 0) {
            	return ['result' => "Fail, proforma not waiting approval!", 'nota_no' => $getNota->nota_no, "Success" => false];
            }
            $config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $getNota->nota_group_id)->first();
            $config = json_decode($config->api_set, true);
            $cekIsCanc = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_no', $getNota->nota_req_no)->first();
            $sendInvProforma = null;
            if ($input['approved'] == 'true') {
            	$arr = [
            		'nota' => (array)$getNota['attributes'],
            		'config' => $config,
            		'reqCanc' => (array)$cekIsCanc
            	];
            	$sendInvProforma = PlgEInvo::sendInvProforma($arr);
            	if ($sendInvProforma['Success'] == true) {
	            	$getNota->nota_status = 2;
	            	$getNota->save();
            	}else{
            		return ['Success' => false, 'result' => 'Fail, cant send invoice proforma', 'nota_no' => $getNota->nota_no, 'sendInvProforma' => $sendInvProforma];
            	}
            	$msg='Success, approved!';
            }else if ($input['approved'] == 'false') {
            	$getNota->nota_status = 4;
            	$getNota->save();
            	if (empty($cekIsCanc)) {
					$getReq = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$getNota->nota_req_no)->first();
					$getReq = (array)$getReq;
					if ($getReq[$config['head_paymethod']] == 1) {
						DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$getNota->nota_req_no)->update([$config['head_status'] => 4 ]);
					}else if($getReq[$config['head_paymethod']] == 2) {
						DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$getNota->nota_req_no)->update([$config['head_status'] => 3 ]);
					}
            	}else{
            		DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_no', $getNota->nota_req_no)->update(['cancelled_status'=>4]);
            	}

            	$msg='Success, rejected!';
            }
            return ['result' => $msg, 'nota_no' => $getNota->nota_no, 'sendInvProforma' => $sendInvProforma];
	    }

	    public static function storePaymentPLG($input){
	    	$getNota = TxHdrNota::where([ 'nota_no'=>$input['pay_nota_no'] ])->first();
	    	$config = DB::connection('mdm')->table('TS_NOTA')->where('nota_id', $getNota->nota_group_id)->first();
        	$config = json_decode($config->api_set, true);
            $cekNota = TxHdrNota::where([
            	'nota_no'=>$input['pay_nota_no'],
            	'nota_paid'=>'Y'
            ])->count();
            if ($cekNota > 0) {
            	return ['result' => "Fail, invoice already paid!", "Success" => false, 'nota_no'=>$input['pay_nota_no']];
            }
            $cekNota = TxHdrNota::where([
            	'nota_no'=>$input['pay_nota_no'],
            	'nota_status'=>2
            ])->count();
            if ($cekNota == 0) {
            	return ['result' => "Fail, proforma not approved!", "Success" => false, 'nota_no'=>$input['pay_nota_no']];
            }
			if (empty($input['pay_id'])) {
		    	$store = new TxPayment;
		    	if (empty($input['pay_file']['PATH']) or empty($input['pay_file']['BASE64']) or empty($input['pay_file'])) {
	              return ["Success"=>false, "result" => "Fail, file is required"];
	            }
			}else{
				$store = TxPayment::find($input['pay_id']);
				if (!empty($input['pay_file']['PATH']) and !empty($input['pay_file']['BASE64']) and !empty($input['pay_file'])) {
					if (file_exists($store->pay_file)){
						unlink($store->pay_file);
					}
	            }
			}
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
	    	$store->pay_branch_code = $getNota->nota_branch_code;
	    	$store->pay_account_no = $input['pay_account_no'];
	    	$store->pay_account_name = $input['pay_account_name'];
	    	$store->pay_amount = $input['pay_amount'];
	    	$store->pay_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD HH24:MI')");
	    	$store->pay_note = $input['pay_note'];
	    	$store->pay_create_by = $input['pay_create_by'];
	    	$store->pay_create_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD HH24:MI')");
	    	$store->pay_type = $input['pay_type'];
	    	$store->save();

	    	$pay = TxPayment::find($store->pay_id);
	    	if (!empty($input['pay_file']['PATH']) and !empty($input['pay_file']['BASE64']) and !empty($input['pay_file'])) {
	    		$directory  = 'omuster/TX_PAYMENT/'.date('d-m-Y').'/';
	    		$response   = FileUpload::upload_file($input['pay_file'], $directory, "TX_PAYMENT", $store->pay_id);
	    		if ($response['response'] == true) {
	    			TxPayment::where('pay_id',$store->pay_id)->update([
	    				'pay_file' => $response['link']
	    			]);
	    		}
	    	}
            $cekIsCanc = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_no', $getNota->nota_req_no)->first();
            $cekIsCanc = (array)$cekIsCanc;
	    	$arr = [
	    		'config' => $config,
	    		"nota" => (array)$getNota['attributes'],
	    		"payment" => (array)$pay['attributes'],
	    		'reqCanc' => $cekIsCanc
	    	];
        	$sendInvPay = PlgEInvo::sendInvPay($arr);
        	if (empty($sendInvPay['Success']) or $sendInvPay['Success'] == false) {
        		return [
        			'response' => 'Fail, cant send payment invoice',
        			'no_pay' => $pay->pay_no,
        			'nota_no' => $getNota->nota_no,
        			'no_req' => $pay->pay_req_no,
        			'sendInvPay' => $sendInvPay
        		];
        	}
        	$getNota->nota_status = 3;
        	$getNota->nota_paid_date = \DB::raw("TO_DATE('".$input['pay_date']."', 'YYYY-MM-DD HH24:MI')");
        	$getNota->nota_paid = 'Y';
        	$getNota->save();
        	if (empty($cekIsCanc)) {
	        	$getReq = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$getNota->nota_req_no)->first();
	        	$getReq = (array)$getReq;
	        	$id = $getReq[$config['head_primery']];
	        	$table = $config['head_table'];
        	}else{
        		$id = $cekIsCanc['cancelled_id'];
	        	$table = 'TX_HDR_CANCELLED';
        	}
        	$sendRequestBooking = null;
        	if (
        		(!empty($getReq) and $getReq[$config['head_paymethod']] == 1) or
        		!empty($cekIsCanc)
        	) {
        		$sendRequestBooking = PlgFunctTOS::sendRequestBookingPLG(['id' => $id, 'table' => $table, 'config' => $config]);
        		if (!empty($cekIsCanc)) {
        			DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id', $cekIsCanc['cancelled_id'])->update([
        				'cancelled_status' => 4
        			]);
        		}
        	}
        	return [
        		'result' => "Success, pay proforma!",
        		'no_pay' => $pay->pay_no,
        		'no_nota' => $input['pay_nota_no'],
        		'no_req' => $pay->pay_req_no,
        		'sendInvPay' => $sendInvPay,
        		'sendRequestBooking' => $sendRequestBooking
        	];
	    }

	    public static function storeTsContAndTxHisCont($arr){
	    	// history container
				$storeTsCont = [];
				$findTsCont = [
					'cont_no' => $arr['cont_no'],
					'branch_id' => $arr['branch_id'],
					'branch_code' => $arr['branch_code']
				];
				$storeTsCont = [
					'cont_no' => $arr['cont_no'],
					'branch_id' => $arr['branch_id'],
					'branch_code' => $arr['branch_code'],
					'cont_location' => $arr['cont_location']
				];
				if (!empty($arr['cont_size'])) {
					$storeTsCont['cont_size'] = $arr['cont_size'];
				}
				if (!empty($arr['cont_type'])) {
					$storeTsCont['cont_type'] = $arr['cont_type'];
				}
				if (!empty($arr['cont_counter'])) {
					$storeTsCont['cont_counter'] = $arr['cont_counter'];
				}
				$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
				if (empty($cekTsCont)) {
					DB::connection('omuster')->table('TS_CONTAINER')->insert($storeTsCont);
				}else{
					DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->update($storeTsCont);
				}
				$cekTsCont = DB::connection('omuster')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
				if ($arr["cont_location"] == "GATO" and $cekTsCont->cont_counter == 0) {
					$counter = $cekTsCont->cont_counter+1;
				}else{
					$counter = $cekTsCont->cont_counter;
				}
				$storeTxHisCont = [
					'history_date' => date('Y-m-d h:i:s', strtotime($arr['history_date'])),
					'no_container' => $arr['cont_no'],
					'no_request' => $arr['no_request'],
					'kegiatan' => $arr['kegiatan'],
					'id_user' => $arr['id_user'],
					'status_cont' => $arr['status_cont'],
					'vvd_id' => $arr['vvd_id'],
					'counter' => $counter
					// 'id_yard' => $list[$config['DTL_BL']], ?
					// 'sub_counter' => $list[$config['DTL_BL']], ?
					// 'why' => $list[$config['DTL_BL']], ?
					// 'aktif' => $list[$config['DTL_BL']], ?
				];
				DB::connection('omuster')->table('TX_HISTORY_CONTAINER')->insert($storeTxHisCont);
			// history container
			return ['storeTsCont' => $cekTsCont, 'storeTxHisCont'=>$storeTxHisCont];
	    }
	// PLG
}
