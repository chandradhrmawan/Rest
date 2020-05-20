<?php

namespace App\Helper\Jbi;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class JbiContHist{
	public static function saveHisCont($find,$list,$config,$input,$confKgt){
		$findTsCont = [
			'cont_no' => $list[$config['DTL_BL']],
			'branch_id' => $find[$config['head_branch']],
			'branch_code' => $find[$config['head_branch_code']]
		];
		$cekTsCont = DB::connection('omuster_ilcs')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
		if (empty($cekTsCont)) {
			$cont_counter = 0;
			$cont_location = 'GATO';
		}else{
			$cont_counter = $cekTsCont->cont_counter;
			$cont_location = $cekTsCont->cont_location;
		}
		$arrStoreTsContAndTxHisCont = [
			'history_date' => $list[$config['DTL_DATE_HIS_CONT']],
			'cont_no' => $list[$config['DTL_BL']],
			'branch_id' => $find[$config['head_branch']],
			'branch_code' => $find[$config['head_branch_code']],
			'cont_location' => $cont_location,
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

	public static function storeTsContAndTxHisCont($arr){
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
		$cekTsCont = DB::connection('omuster_ilcs')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
		if (empty($cekTsCont)) {
			DB::connection('omuster_ilcs')->table('TS_CONTAINER')->insert($storeTsCont);
		}else{
			DB::connection('omuster_ilcs')->table('TS_CONTAINER')->where($findTsCont)->update($storeTsCont);
		}
		$cekTsCont = DB::connection('omuster_ilcs')->table('TS_CONTAINER')->where($findTsCont)->orderBy('cont_counter', 'desc')->first();
		if ($arr["cont_location"] == "GATO" and $arr['kegiatan'] == 1) {
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
		];
		DB::connection('omuster_ilcs')->table('TX_HISTORY_CONTAINER')->insert($storeTxHisCont);
		return ['storeTsCont' => $cekTsCont, 'storeTxHisCont'=>$storeTxHisCont];
	}
}
