<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;

class PlgCanclHelper{
	public static function canceledReqPrepareGD($input,$config){
		$cnclHdr = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->first();
		if (empty($cnclHdr)) {
			return ['Success' => false, 'result_msg' => 'canceled request not found'];
		}
		$reqsHdr = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$cnclHdr->cancelled_req_no)->first();
		if (empty($reqsHdr)) {
			return ['Success' => false, 'result_msg' => 'canceled request not found'];
		}
		$reqsHdr = (array)$reqsHdr;
		$pluck = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id',$input['id'])->pluck('cancl_cont');
		if (empty($pluck) or empty($pluck[0])) {
			$pluck = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id',$input['id'])->pluck('cancl_si');
			if (empty($pluck) or empty($pluck[0])){
				return ['Success' => false, 'result_msg' => 'dtl canceled is null'];
			}
		}
		$cekStart = DB::connection('omuster')->table($config['head_tab_detil'])
			->where($config['head_forigen'],$reqsHdr[$config['head_primery']])
			->whereIn($config['DTL_BL'],$pluck)
			->get();

		return [
			'Success' => true,
			'cnclHdr' => $cnclHdr,
			'reqsHdr' => $reqsHdr,
			'cekStart' => $cekStart
		];
	}

	public static function canceledReqPrepare($input, $config, $up){
		$canceledReqPrepareGD = static::canceledReqPrepareGD($input,$config);
		if ($canceledReqPrepareGD['Success'] == false) {
			return $canceledReqPrepareGD;
		}

		$cnclHdr = $canceledReqPrepareGD['cnclHdr'];
		$reqsHdr = $canceledReqPrepareGD['reqsHdr'];
		$cekStart = $canceledReqPrepareGD['cekStart'];

		if ($up == false) {
			return ['Success' => true, 'find' => $reqsHdr, 'canc' => (array)$cnclHdr];
		}

		foreach ($cekStart as $cek) {
			$cek = (array)$cek;
			if ($cek[$config['DTL_FL_REAL']] != 1) {
				return [
					'Success' => false,
					'no_item' => $cek[$config['DTL_BL']],
					'result_msg' => 'Fail, '.$cek[$config['DTL_BL']].' telah masuk tahap realisasi'
				];
			}
		}
		$cnclDtl = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id',$cnclHdr->cancelled_id)->get();
		foreach ($cnclDtl as $list) {
			$noDtl = $list->cancl_cont.$list->cancl_si;
			$reqDtl = DB::connection('omuster')->table($config['head_tab_detil'])->where([
				$config['head_forigen'] => $reqsHdr[$config['head_primery']],
				$config['DTL_BL'] => $noDtl
			])->first();
			if (empty($reqDtl)) {
				return [
					'Success' => false,
					'no_item' => $noDtl,
					'result_msg' => 'Fail, '.$noDtl.' tidak ditemukan'
				];
			}
			$reqDtl = (array)$reqDtl;
			if ($reqDtl[$config['DTL_FL_REAL']] != 1) {
				return [
					'Success' => false,
					'no_item' => $reqDtl[$config['DTL_BL']],
					'result_msg' => 'Fail, '.$reqDtl[$config['DTL_BL']].' sudah melakukan realisasi'
				];
			}
			if ($config['DTL_QTY'] == 1 or $config['kegiatan_batal'] == 21) {
				$reqDtlQty = 1;
			}else{
				$reqDtlQty = $reqDtl[$config['DTL_QTY']];
			}
			if ($list->cancl_qty > $reqDtlQty) {
				return [
					'Success' => false,
					'no_item' => $reqDtl[$config['DTL_BL']],
					'result_msg' => 'Fail, '.$reqDtl[$config['DTL_BL']].' qty yang dibatalkan melebihi data request'
				];
			}
		}
		static::canceledReqPrepareContainerOrBarang($config,$reqsHdr,$cnclHdr,$cnclDtl);
		return ['Success' => true, 'find' => $reqsHdr, 'canc' => (array)$cnclHdr];
	}

	public static function canceledReqPrepareContainerOrBarang($config,$reqsHdr,$cnclHdr,$cnclDtl){
		foreach ($cnclDtl as $list) {
			$noDtl = $list->cancl_cont.$list->cancl_si;
			if (!empty($config['DTL_IS_CANCEL'])){
				$upd = [
					$config['DTL_IS_ACTIVE'] => 'N',
					$config['DTL_IS_CANCEL'] => 'Y'
				];
			}else{
				$upd = [
					$config['DTL_IS_CANCEL'] => 'Y',
					$config['DTL_QTY_CANC'] => $list->cancl_qty
				];
			}
			DB::connection('omuster')->table($config['head_tab_detil'])->where([
				$config['head_forigen'] => $reqsHdr[$config['head_primery']],
				$config['DTL_BL'] => $noDtl
			])->update($upd);
		}

		// Tambahan Change Header Flag
		if ($config['CANCELLED_STATUS'] == 21 || $config['CANCELLED_STATUS'] == 22) {
			
		} else {
			$dtlIsActive = DB::connection('omuster')->table($config['head_tab_detil'])->where([
			$config['head_forigen'] => $reqsHdr[$config['head_primery']],
			$config['DTL_IS_ACTIVE'] => 'Y',
			$config['DTL_IS_CANCEL'] => 'N'
			])->get();

			if (count($dtlIsActive) == 0) {
				$updateHdrFlagCancel = DB::connection('omuster')
				->table($config['head_table'])
				->where($config['head_primery'], $reqsHdr[$config['head_primery']])
				->update([$config['head_status'] => 9]);
			}
		}
	}

	public static function cekReqOrCanc($input,$config){
		$migrateTariff = true;
		$findCanc = null;
		if (!empty($input['canceled']) and $input['canceled'] == 'true') {
			$findCanc = DB::connection('omuster')->table('TX_HDR_CANCELLED')->where('cancelled_id',$input['id'])->first();
			$migrateTariff = false;
		}

		if (empty($findCanc)) {
			$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_primery'],$input['id'])->get();
			if (empty($find)) {
				return ['result' => "Fail, requst not found!", "Success" => false];
			}
			$find = (array)$find[0];
			$retHeadNo = $find[$config['head_no']];
		}else{
			$find = DB::connection('omuster')->table($config['head_table'])->where($config['head_no'],$findCanc->cancelled_req_no)->get();
			if (empty($find)) {
				return ['result' => "Fail, requst not found!", "Success" => false];
			}
			$find = (array)$find[0];
			$retHeadNo = $findCanc->cancelled_no;
		}
		$canceledReqPrepare = static::canceledReqPrepare($input, $config, true);
		return [
			"Success" => true,
			"migrateTariff" => $migrateTariff,
			'findCanc' => $findCanc,
			'find' => $find,
			'retHeadNo' => $retHeadNo
		];
	}

	public static function undoCanclSet($input,$config,$findCanc,$findReq){
		$canclDtl = DB::connection('omuster')->table('TX_DTL_CANCELLED')->where('cancl_hdr_id',$findCanc['cancelled_id'])->get();
		foreach ($canclDtl as $lcd) {
			$cndtn = [
				$config['head_forigen'] => $findReq[$config['head_primery']]
			];
			if (!empty($lcd->cancl_cont)) {
				$cndtn[$config['DTL_BL']] = $lcd->cancl_cont;
				$up = [
					$config['DTL_IS_ACTIVE'] => 'Y',
					$config['DTL_IS_CANCEL'] => 'N'
				];
			}else if (!empty($lcd->cancl_si)) {
				$cndtn[$config['DTL_BL']] = $lcd->cancl_si;
				$up = [
					$config['DTL_IS_CANCEL'] => 'Y'
				];
				$oldDtl = DB::connection('omuster')->table($config['head_tab_detil'])->where($cndtn)->first();
				$oldDtl = (array)$oldDtl;
				$undoCancQty = $oldDtl[$config['DTL_QTY_CANC']]-$lcd->cancl_qty;
				if ($undoCancQty == 0) {
					$up[$config['DTL_IS_CANCEL']] = 'N';
				}
				$up[$config['DTL_QTY_CANC']] = $undoCancQty;
			}
			DB::connection('omuster')->table($config['head_tab_detil'])->where($cndtn)->update($up);
		}
	}
}
