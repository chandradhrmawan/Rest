<?php

namespace App\Helper;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Helper\BillingEngine;

class PlgGenerateTariff{
	private static function calculateHours($st,$ed){
		$st = strtotime($st);
		$ed = strtotime($ed);
		$difference = abs($ed - $st)/3600;
		$difference = ceil($difference);
		return $difference;
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

	private static function getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config,$input){
		if (in_array($input['nota_id'], [3,7,17])) {
			$in = [12,14];
		} else if (in_array($input['nota_id'], [4,10,18])){
			$in = [12,13];
		}

		$tglIn 	= DB::connection('omuster')
			->table('TX_HISTORY_CONTAINER')
			->where('NO_CONTAINER', $list[$config['DTL_BL']]);
		if (!in_array($input['nota_id'], [2,16])) {
			$tglIn->whereIn('KEGIATAN', $in);
		}else if (in_array($input['nota_id'], [2,16])) {
			$tglIn->whereIn('KEGIATAN', [12,13,14]);
		}
		$tglIn = $tglIn->orderBy("HISTORY_DATE", "DESC")->first();
		if (empty($tglIn)) {
			return [
				"result_flag"=>"F",
				"result_msg"=>"Not found countainer ".$list[$config['DTL_BL']]."!",
				"no_req"=>$hdr[$config['head_no']],
				"no_cont"=>$list[$config['DTL_BL']],
				"Success"=>false
			];
		}
		return $dateIn = $tglIn->history_date;
	}

	private static function getDTL_VIA($config,$list,$hdr,$input){
		if (empty($config['DTL_VIA'])) {
			$DTL_VIA = 'NULL';
		} else if (is_array($config['DTL_VIA'])) {
			if ($input["nota_id"] == 20) {
				if ($list[$config['DTL_VIA']['rec']]+$list[$config['DTL_VIA']['del']] >= 2) {
					$DTL_VIA = 2;
				}else{
					$DTL_VIA = 1;
				}
			} else {
				$DTL_VIA = empty($list[$config['DTL_VIA']['rec']]) ? 'NULL' : $list[$config['DTL_VIA']['rec']];
			}
		} else {
			$DTL_VIA = empty($list[$config['DTL_VIA']]) ? 'NULL' : $list[$config['DTL_VIA']];
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
			if (empty($list[$config['DTL_CHARACTER']]) and $list[$config['DTL_CHARACTER']] != 0) {
				$DTL_CHARACTER = 'NULL';
			}else if ($list[$config['DTL_CHARACTER']] == 'Y'){
				$DTL_CHARACTER = 2;
			}else if ($list[$config['DTL_CHARACTER']] == 'N'){
				$DTL_CHARACTER = 0;
			}else{
				$DTL_CHARACTER = $list[$config['DTL_CHARACTER']];
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
				$funct = $config['DTL_QTY']['func'];
				$DTL_QTY = static::$funct($list[$config['DTL_QTY']['start']],$list[$config['DTL_QTY']['end']]);
			}else{
				$DTL_QTY = 'NULL';
			}
		}else{
			if (empty($list[$config['DTL_QTY']])) {
				$qty = 'NULL';
			}else if(!empty($config['DTL_QTY_CANC'])){
				$qty = $list[$config['DTL_QTY']] - $list[$config['DTL_QTY_CANC']];
			}else{
				$qty = $list[$config['DTL_QTY']];
			}
			$DTL_QTY = $qty;
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
		if ($config['head_table'] == "TX_HDR_DEL") {
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
				$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			} else if (in_array($config['DTL_DATE_IN'], ["TX_HISTORY_CONTAINER"])){
				$dateIn = static::getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config,$input);
				if (is_array($dateIn)) {
					return $dateIn;
				}
				if (!empty($config['DTL_STACK_DATE'])) {
					DB::connection('omuster')->table($config['head_tab_detil'])->where($config['DTL_PRIMARY'],$list[$config['DTL_PRIMARY']])->update([$config['DTL_STACK_DATE']=>$dateIn]);
				}
				$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			} else if (is_array($config['DTL_DATE_IN'])){
				if ($hdr[$config['head_paymethod']] == 2 and $hdr[$config['head_status']] == 3) {
					$paymethod = 'paymethod2';
				}else{
					$paymethod = 'paymethod1';
				}
				$ddiType = $config['DTL_DATE_IN'][$paymethod];
				if (in_array($ddiType, ["TX_HISTORY_CONTAINER"])) {
					$dateIn = static::getLastContFromTX_HISTORY_CONTAINER($list,$hdr,$config,$input);
					if (is_array($dateIn)) {
						return $dateIn;
					}
					$DTL_DATE_IN = 'to_date(\''.\Carbon\Carbon::parse($dateIn)->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
				}else{
					$DTL_DATE_IN = empty($list[$ddiType]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$ddiType])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
				}
			} else {
				$DTL_DATE_IN = empty($list[$config['DTL_DATE_IN']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_IN']])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			}
		}
		return $DTL_DATE_IN;
	}

	private static function getDTL_DATE_OUT($config,$list,$hdr,$input){
		if (empty($config['DTL_DATE_OUT'])) {
			$DTL_DATE_OUT = 'NULL';
		}else{
			if ($config['head_table'] == 'TX_HDR_DEL_CARGO') {
				// Surat Cinta Buat Mas Adam
				// $tglOut = DB::connection('omuster')->table('TX_GATEOUT')->orderBy("GATEOUT_DATE", "DESC")->first();
				// $dateout = $tglOut->gateout_date;
				$dateout 	= $list[$config['DTL_DATE_OUT']['paymethod1']];
				$DTL_DATE_OUT = 'to_date(\''.\Carbon\Carbon::parse($dateout)->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			} else {
				if (is_array($config['DTL_DATE_OUT'])) {
					if ($hdr[$config['head_paymethod']] == 2 and $hdr[$config['head_status']] == 3) {
						$outKey = $config['DTL_DATE_OUT']['paymethod2'];
					}else{
						$outKey = $config['DTL_DATE_OUT']['paymethod1'];
					}
					$dtlOut = $list[$outKey];
					$DTL_DATE_OUT = empty($dtlOut) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($dtlOut)->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
				}else{
					$DTL_DATE_OUT = empty($list[$config['DTL_DATE_OUT']]) ? 'NULL' : 'to_date(\''.\Carbon\Carbon::parse($list[$config['DTL_DATE_OUT']])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
				}
			}
		}

		return $DTL_DATE_OUT;
	}

	private static function getDTL_DATE_OUT_OLD($config,$list,$hdr){
		if (!empty($config['head_ext_status']) and $hdr[$config['head_ext_status']] == 'Y') {
			$getOldIdHdr = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$hdr[$config['head_ext_from']])->first();
			$getOldIdHdr = (array)$getOldIdHdr;
			$getOldIdHdr = $getOldIdHdr[$config['head_primery']];
			$getOldDtDtl = DB::connection('omuster')->table($config['head_tab_detil'])->where([
				$config['head_forigen'] => $getOldIdHdr,
				$config['DTL_BL'] => $list[$config['DTL_BL']]
			])->first();
			$getOldDtDtl = (array)$getOldDtDtl;
			$DTL_DATE_OUT_OLD = 'to_date(\''.\Carbon\Carbon::parse($getOldDtDtl[$config['DTL_DATE_OUT']['paymethod1']])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
		}else{
			$DTL_DATE_OUT_OLD = 'NULL';
		}
		return $DTL_DATE_OUT_OLD;
	}

	public static function calculateTariffBuild($find, $input, $config, $canceledReqPrepare){
		// build head
		$setH = static::calculateTariffBuildHead($find, $input, $config, $canceledReqPrepare);
		// build detil
		$setD = [];
		$detil = DB::connection('omuster')->table($config['head_tab_detil'])->where($config['head_forigen'], $find[$config['head_primery']]);
		//tambahan dari chalid
		if(!empty($input["canceled"])) {
			$detil->where($config['DTL_IS_CANCEL'], 'Y');
		} else
		//

		if (!empty($config['DTL_IS_CANCEL']) and !in_array($input['nota_id'], [21,22,23])) {
			$detil->where($config['DTL_IS_CANCEL'], 'N');
		}
		$detil = $detil->get();

		if (
			(in_array($config['kegiatan'], [8]) and $find[$config['head_status']] == 1) or
			(!empty($canceledReqPrepare) and $find[$config['head_paymethod']] == 2 )
		) {
			return [
				"detil_data"=>$detil,
				"result_flag"=>"S",
				"result_msg"=>"Success",
				"no_req"=>$find[$config['head_no']],
				"Success"=>true
			];
		}
		foreach ($detil as $list) {
			$list = (array)$list;
			$dtl = static::calculateTariffBuildDetail($find, $list, $input, $config);
			if (isset($dtl['Success']) and $dtl['Success'] == false) {
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
		if (!in_array($config['kegiatan'], [10,11])){
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
	$result['P_RESTITUTION'] = 'N';
		}else{
			$result['P_BOOKING_NUMBER'] = $canceledReqPrepare['canc']['cancelled_no'];
	$result['P_RESTITUTION'] = 'Y';
		}
		$result['P_REALIZATION'] = 'N';
		if (!empty($config['nota_id_ext'])) {
			$result['P_EXTENTION'] = 'Y';
			$result['P_EXT_NOTA_ID'] = $config['nota_id_ext'];
		}else{
			$result['P_EXTENTION'] = 'N';
			$result['P_EXT_NOTA_ID'] = 'NULL';
		}

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
		$dateIn = static::getDTL_DATE_IN($config,$list,$hdr,$input);
		if (is_array($dateIn)) {
			return $dateIn;
		}else{
			$newD['DTL_DATE_IN'] = $dateIn;
		}
		$newD['DTL_DATE_OUT'] = static::getDTL_DATE_OUT($config,$list,$hdr,$input);
		$newD['DTL_DATE_OUT_OLD'] = static::getDTL_DATE_OUT_OLD($config,$list,$hdr,$input);
		return $newD;
	}

	public static function showTempTariff($query, $config, $find){
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
    		$uper_req_date = null;
    		$uper_vessel_name = null;
    		if (!empty($config)) {
    			$uper_req_date = $find[$config['head_date']];
    			$uper_vessel_name = $find[$config['head_date']];
    		}
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
    			'uper_service_code' => $getH->nota_service_code,
    			'uper_branch_account' => $getH->branch_account,
    			'uper_context' => $getH->nota_context,
    			'uper_sub_context' => $getH->nota_sub_context,
    			'uper_branch_id' => $getH->branch_id,
    			'uper_branch_code' => $getH->branch_code,
    			'uper_faktur_no' => '-',
    			'uper_trade_type' => $getH->trade_type,
    			'uper_req_no' => $getH->booking_number,
    			'uper_ppn' => $getH->ppn_uper,
    			'uper_percent' => $getH->percent_uper,
    			'uper_dpp' => $getH->dpp_uper,
    			'uper_nota_id' => $getH->nota_id,
    			'uper_req_date' =>  $uper_req_date,
    			'uper_vessel_name' => $uper_vessel_name
    		];
    		if (!empty($config)) {
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
    		}
              // if ($config['head_terminal_name'] != null) {
              //     $head['uper_terminal_name'] = $find[$config['head_terminal_name']];
              // }
    		$head['nota_view'] = $nota_view;
          // build head

    		$result[] = $head;
    	}

    	return $result;
	}

	public static function simulationTariffPLG($input){
		$setH = [];
		// head
			$setH['P_SOURCE_ID'] = "NPKS_BILLING";
			$setH['P_NOTA_ID'] = $input['HDR']['P_NOTA_ID'];
			$setH['P_BRANCH_ID'] = $input['HDR']['P_BRANCH_ID'];
			$setH['P_BRANCH_CODE'] = $input['HDR']['P_BRANCH_CODE'];
			$setH['P_CUSTOMER_ID'] = $input['HDR']['P_CUSTOMER_ID'];
			$setH['P_PBM_INTERNAL'] = $input['HDR']['P_PBM_INTERNAL'];
			$setH['P_BOOKING_NUMBER'] = $input['HDR']['P_BOOKING_NUMBER'];
			$setH['P_REALIZATION'] = $input['HDR']['P_REALIZATION'];
			$setH['P_RESTITUTION'] = $input['HDR']['P_RESTITUTION'];
			$setH['P_EXTENTION'] = "N";
			$setH['P_EXT_NOTA_ID'] = "NULL";
			$setH['P_TRADE'] = $input['HDR']['P_TRADE'];
			$setH['P_USER_ID'] = $input['HDR']['P_USER_ID'];
		// head

		$setD = [];
		foreach ($input['DTL'] as $list) {
			if ($list['DTL_PFS'] == 'NULL' or $list['DTL_PFS'] == NULL or empty($list['DTL_PFS'])) {
				$pfs = 'N';
			}else{
				$pfs = $list['DTL_PFS'];
			}
			if ($list['DTL_DATE_IN'] == 'NULL' or $list['DTL_DATE_IN'] == NULL or empty($list['DTL_DATE_IN'])) {
				$dateIn = 'NULL';
			}else{
				$dateIn = 'to_date(\''.\Carbon\Carbon::parse($list['DTL_DATE_IN'])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			}
			if ($list['DTL_DATE_OUT'] == 'NULL' or $list['DTL_DATE_OUT'] == NULL or empty($list['DTL_DATE_OUT'])) {
				$dateOt = 'NULL';
			}else{
				$dateOt = 'to_date(\''.\Carbon\Carbon::parse($list['DTL_DATE_OUT'])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			}
			if ($list['DTL_DATE_OUT_OLD'] == 'NULL' or $list['DTL_DATE_OUT_OLD'] == NULL or empty($list['DTL_DATE_OUT_OLD'])) {
				$dateOO = 'NULL';
			}else{
				$dateOO = 'to_date(\''.\Carbon\Carbon::parse($list['DTL_DATE_OUT_OLD'])->format('Y-m-d H:i:s').'\',\'YYYY-MM-DD HH24:MI:SS\')';
			}

			$newD = [];
			$newD['DTL_VIA'] = $list['DTL_VIA'];
			$newD['DTL_BL'] = $list['DTL_BL'];
			$newD['DTL_FUMI_TYPE'] = $list['DTL_FUMI_TYPE'];
			$newD['DTL_PKG_ID'] = $list['DTL_PKG_ID'];
			$newD['DTL_CMDTY_ID'] = $list['DTL_CMDTY_ID'];
			$newD['DTL_CHARACTER'] = $list['DTL_CHARACTER'];
			$newD['DTL_CONT_SIZE'] = $list['DTL_CONT_SIZE'];
			$newD['DTL_CONT_TYPE'] = $list['DTL_CONT_TYPE'];
			$newD['DTL_CONT_STATUS'] = $list['DTL_CONT_STATUS'];
			$newD['DTL_UNIT_ID'] = $list['DTL_UNIT_ID'];
			$newD['DTL_QTY'] = $list['DTL_QTY'];
			$newD['DTL_PFS'] = $pfs;
			$newD['DTL_BM_TYPE'] = $list['DTL_BM_TYPE'];
			$newD['DTL_STACK_AREA'] = $list['DTL_STACK_AREA'];
			$newD['DTL_TL'] = strtoupper($list['DTL_TL']);
			$newD['DTL_DATE_IN'] = $dateIn;
			$newD['DTL_DATE_OUT'] = $dateOt;
			$newD['DTL_DATE_OUT_OLD'] = $dateOO;
			$setD[] = $newD;
		}

		$set_data = [
			'head' => $setH,
			'detil' => $setD,
			'eqpt' => [],
			'paysplit' => []
		];
		$tariffResp = BillingEngine::calculateTariff($set_data);
		if (empty($tariffResp['result_flag']) or $tariffResp['result_flag'] != 'S') {
			return $tariffResp;
		}
		$query = "SELECT * FROM V_PAY_SPLIT WHERE booking_number= '".$input['HDR']['P_BOOKING_NUMBER']."'";
		$result = static::showTempTariff($query,null,null);
		return [ "Success" => true, "result" => $result, "tariffResp" => $tariffResp ];
	}
}
