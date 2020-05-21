<?php

namespace App\Helper\Jbi;

use Illuminate\Support\Facades\DB;
use Carbon\Carbon;
use App\Helper\Jbi\ConnectedExternalApps;
use App\Helper\Jbi\Billingeng_ilcsine;
use App\Models\OmCargo\TxHdrUper_ilcs;

class SendTos{

    public static function send_data($nota_no){

        $nota_type = DB::connection('omuster_ilcs')->table('TX_HDR_NOTA A')
            ->join('BILLING_mdm_ilcs.TM_NOTA B', 'A.NOTA_GROUP_ID', '=', 'B.NOTA_ID')
            ->select('B.NOTA_ID','B.NOTA_NAME')
            ->where('A.NOTA_NO', $nota_no)
            ->get();

        $nota_name = $nota_type[0]->nota_name;

        if($nota_name == 'RECEIVING'){
            $response = self::getReveiving($nota_no);
        }elseif($nota_name == 'RECEIVING CARGO'){
            $response =self::getReveivingCargo($nota_no);
        }elseif($nota_name == 'RELOKASI'){
            $response =self::getRelokasi($nota_no);
        }elseif($nota_name == 'DELIVERY'){
            $response =self::getDelivery($nota_no);
        }elseif($nota_name == 'STRIPPING'){
            $response =self::getStripping($nota_no);
        }elseif($nota_name == 'STUFFING'){
            $response =self::getStuffing($nota_no);
        }elseif($nota_name == 'EXTENTION DELIVERY USTER'){
            $response =self::getExtDel($nota_no);
        }elseif($nota_name == 'EXTENTION STUFFING'){
            $response =self::getExtStuff($nota_no);
        }elseif($nota_name == 'EXTENTION STRIPPING'){
            $response =self::getExtStripp($nota_no);
        }elseif($nota_name == 'DELIVERY CARGO'){
            $response =self::getDeliveryCargo($nota_no);
        }elseif($nota_name == 'EXTENTION DELIVERY CARGO'){
            $response =self::getExtDeliveryCargo($nota_no);
        }else{
            $response = false;
        }

        return $response;
    }

    public static function getReveiving($nota_no){
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_REC A')
                  ->join('TX_HDR_NOTA B', 'A.REC_NO', '=', 'B.NOTA_REQ_NO')
                  ->select('A.REC_ID','A.REC_NO','A.REC_DATE','A.REC_PAYMETHOD','A.REC_CUST_ID','A.REC_CUST_NAME','A.REC_CUST_ADDRESS','A.REC_CUST_NPWP','A.REC_CUST_ACCOUNT','A.REC_STACKBY_ID','A.REC_STACKBY_NAME','A.REC_VESSEL_CODE','A.REC_VESSEL_NAME','A.REC_VOYIN','A.REC_VOYOUT','A.REC_VVD_ID','A.REC_VESSEL_POL','A.REC_VESSEL_POD','A.REC_BRANCH_ID','A.REC_NOTA','A.REC_CORRECTION','A.REC_CORRECTION_DATE','A.REC_PRINT_CARD','A.REC_CREATE_BY','A.REC_CREATE_DATE','A.REC_BL','A.REC_DO','A.REC_STATUS','A.REC_VESSEL_AGENT','A.REC_VESSEL_AGENT_NAME','A.REC_CORRECTION_FROM','A.REC_BRANCH_CODE','A.REC_PBM_ID','A.REC_PBM_NAME','A.REC_VESSEL_PKK','A.REC_BTL_FROM','A.REC_BTL_STATUS','A.REC_BTL_FROM_ID','A.REC_MSG','A.APP_ID','A.REC_VESSEL_ETA','A.REC_VESSEL_ETD','A.REC_FROM','B.NOTA_NO','B.NOTA_PAID_DATE','B.NOTA_REQ_DATE'
                )
                  ->where('A.REC_STATUS', '3')
                  ->where('B.NOTA_NO', $nota_no)
                  ->get();
          // print_r($data_head);die;
          foreach ($data_head as $key => $value) {
            $sql_req_id   = "SELECT SEQ_REQ_RECEIVING_HDR.NEXTVAL FROM DUAL";
            $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
            $request_id   = $data_req_id[0]->nextval;

            $head['request_id']             = $request_id;
            $head['request_no']             = $value->rec_no;
            $head['request_consignee_id']   = $value->rec_cust_id;
            $head['request_mark']           = '';
            $head['request_branch_id']      = ($value->rec_branch_id == '99') ? '10' : $value->rec_branch_id;
            $head['request_create_date']    = $value->rec_create_date;
            $head['request_create_by']      = $value->rec_create_by;
            $head['request_nota']           = $value->nota_no;
            $head['request_no_tpk']         = '';
            $head['request_do_no']          = '';
            $head['request_bl_no']          = '';
            $head['request_sppb_no']        = '';
            $head['request_sppb_date']      = '';
            $head['request_receiving_date'] = $value->rec_date;
            $head['request_nota_date']      = $value->nota_req_date;
            $head['request_paid_date']      = $value->nota_paid_date;
            $head['request_from']           = ($value->rec_from == '1') ? 'DEPO' : $value->rec_from;
            $head['request_status']         = '1';
            $head['request_di']             = 'I';
            $head['request_rd']             = 'N';
            $head['request_payment_method'] = $value->rec_paymethod;
            DB::connection('npks')->table('TX_REQ_RECEIVING_HDR')->insert($head);
        }

        $request_hdr_id = $data_head[0]->rec_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_REC A')
                  ->join('TX_HDR_REC B', 'B.REC_ID', '=', 'A.REC_HDR_ID')
                  ->select('A.REC_DTL_ID','A.REC_HDR_ID','A.REC_DTL_CONT','A.REC_DTL_CONT_SIZE','A.REC_DTL_CONT_TYPE','A.REC_DTL_CONT_STATUS','A.REC_DTL_CONT_DANGER','A.REC_DTL_CMDTY_ID','A.REC_DTL_CMDTY_NAME','A.REC_DTL_VIA','A.REC_DTL_DATE_PLAN','A.REC_DTL_OWNER','A.REC_DTL_OWNER_NAME','A.REC_DTL_ISACTIVE','A.REC_DTL_REAL_DATE','A.REC_DTL_VIA_NAME','A.REC_FL_REAL','A.REC_DTL_ISCANCELLED'
                  )
                  ->where('A.REC_HDR_ID',$request_hdr_id)
                  ->get();

        foreach ($data_dtl as $keyx => $valuex) {
            $sql_req_dtl_id  = "SELECT SEQ_REQ_RECEIVING_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

            $dtl['request_dtl_id']              = $request_dtl_id;
            $dtl['request_hdr_id']              = $request_id;
            $dtl['request_dtl_cont']            = $valuex->rec_dtl_cont;
            $dtl['request_dtl_cont_size']       = $valuex->rec_dtl_cont_size;
            $dtl['request_dtl_cont_type']       = $valuex->rec_dtl_cont_type;
            $dtl['request_dtl_commodity']       = $valuex->rec_dtl_cmdty_name;
            $dtl['request_dtl_cont_status']     = $valuex->rec_dtl_cont_status;
            $dtl['request_dtl_danger']          = $valuex->rec_dtl_cont_danger;
            $dtl['request_dtl_voy']             = '';
            $dtl['request_dtl_vessel_name']     = '';
            $dtl['request_dtl__vessel_code']    = '';
            $dtl['request_dtl_call_sign']       = '';
            $dtl['request_dtl_dest_depo']       = '';
            $dtl['request_dtl_status']          = '0';
            $dtl['request_dtl_owner_code']      = $valuex->rec_dtl_owner;
            $dtl['request_dtl_owner_name']      = $valuex->rec_dtl_owner_name;
            $dtl['request_dtl_via']             = $valuex->rec_dtl_via_name;
            $dtl['request_dtl_via_id']          = $valuex->rec_dtl_via;
            $dtl['request_dtl_tl']              = 'N';
            $dtl['request_dtl_cancelled']       = $valuex->rec_dtl_iscancelled;

            DB::connection('npks')->table('TX_REQ_RECEIVING_DTL')->insert($dtl);
        }

        foreach ($data_dtl as $keyz => $valuez) {
            $branch_id = ($data_head[0]->rec_branch_id == '99') ? '10' : $data_head[0]->rec_branch_id;

            $tm_cont['CONTAINER_NO']           = $valuez->rec_dtl_cont;
            $tm_cont['CONTAINER_SIZE']         = $valuez->rec_dtl_cont_size;
            $tm_cont['CONTAINER_BRANCH_ID']    = $branch_id;
            $tm_cont['CONTAINER_COUNTER']      = '1';
            $tm_cont['CONTAINER_TYPE']         = $valuez->rec_dtl_cont_type;
            $tm_cont['CONTAINER_OWNER']        = $data_head[0]->rec_cust_id;
            $tm_cont['CONTAINER_STATUS']       = NULL;
            $tm_cont['CONTAINER_DATE']         = NULL;

            DB::connection('npks')->table('TM_CONTAINER')->insert($tm_cont);
        }

        return 'success';
    }

    public static function getDelivery($nota_no){
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_DEL A')
                  ->join('TX_HDR_NOTA B', 'A.DEL_NO', '=', 'B.NOTA_REQ_NO')
                  ->select('A.DEL_ID','A.DEL_NO','A.DEL_PAYMETHOD','A.DEL_CUST_ID','A.DEL_CUST_NAME','A.DEL_CUST_ADDRESS','A.DEL_CUST_NPWP','A.DEL_CUST_ACCOUNT','A.DEL_STACKBY_ID','A.DEL_STACKBY_NAME','A.DEL_VESSEL_CODE','A.DEL_VESSEL_NAME','A.DEL_VOYIN','A.DEL_VOYOUT','A.DEL_VVD_ID','A.DEL_VESSEL_POL','A.DEL_VESSEL_POD','A.DEL_BRANCH_ID','A.DEL_NOTA','A.DEL_CORRECTION','A.DEL_CORRECTION_DATE','A.DEL_PRINT_CARD','A.DEL_TO','A.DEL_BL','A.DEL_DO','A.DEL_CREATE_DATE','A.DEL_CREATE_BY','A.DEL_STATUS','A.DEL_DATE','A.DEL_VESSEL_AGENT','A.DEL_VESSEL_AGENT_NAME','A.DEL_EXT_FROM','A.DEL_EXT_LOOP','A.DEL_EXT_STATUS','A.DEL_CORRECTION_FROM','A.DEL_BRANCH_CODE','A.DEL_PBM_ID','A.DEL_PBM_NAME','A.DEL_VESSEL_PKK','A.DEL_EXT_FROM_DATE','A.DEL_BTL_FROM','A.DEL_BTL_FROM_ID','A.DEL_BTL_STATUS','A.DEL_MSG','A.APP_ID','A.DEL_VESSEL_ETA','A.DEL_VESSEL_ETD','B.NOTA_NO','B.NOTA_PAID_DATE','B.NOTA_REQ_DATE'
                )
                  ->where('B.NOTA_NO', $nota_no)
                  ->get();

        foreach ($data_head as $key => $value) {
            $sql_req_id   = "SELECT SEQ_REQ_DELIVERY_HDR.NEXTVAL FROM DUAL";
            $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
            $request_id   = $data_req_id[0]->nextval;

            $head['REQ_ID']                 = $request_id;
            $head['REQ_NO']                 = $value->del_no;
            $head['REQ_CONSIGNEE_ID']       = $value->del_cust_id;
            $head['REQ_DELIVERY_DATE']      = $value->del_date;
            $head['REQ_MARK']               = "";
            $head['REQ_CREATE_BY']          = ""; #null dulu biar ga error
            $head['REQ_CREATE_DATE']        = $value->del_create_date;
            $head['REQ_BRANCH_ID']          = ($value->del_branch_id == '99') ? '10' : $value->del_branch_id;
            $head['REQUEST_NOTA_DATE']      = $value->nota_req_date;
            $head['REQUEST_PAID_DATE']      = $value->nota_paid_date;
            $head['REQUEST_TO']             = "DEPO";
            $head['REQUEST_STATUS']         = "1";
            $head['REQUEST_EXTEND_FROM']    = "";
            $head['REQUEST_EXTEND_LOOP']    = "0";
            $head['REQUEST_ALIH_KAPAL']     = "N";
            $head['REQUEST_RD']             = "N";
            $head['REQUEST_PAYMENT_METHOD'] = $value->del_paymethod;
            $head['REQUEST_NOTA']           = $value->nota_no;
            DB::connection('npks')->table('TX_REQ_DELIVERY_HDR')->insert($head);
        }

        $request_dtl_id = $data_head[0]->del_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_DEL A')
                  ->select('A.DEL_DTL_ID','A.DEL_HDR_ID','A.DEL_DTL_CONT','A.DEL_DTL_CONT_SIZE','A.DEL_DTL_CONT_TYPE','A.DEL_DTL_CONT_STATUS','A.DEL_DTL_CONT_DANGER','A.DEL_DTL_CMDTY_ID','A.DEL_DTL_CMDTY_NAME','A.DEL_DTL_VIA','A.DEL_DTL_DATE_PLAN','A.DEL_DTL_OWNER','A.DEL_DTL_OWNER_NAME','A.DEL_DTL_STATUS','A.DEL_DTL_STACK_DATE','A.DEL_DTL_EXT_DATE','A.DEL_DTL_REAL_DATE','A.DEL_DTL_ISACTIVE','A.DEL_DTL_VIA_NAME','A.DEL_DTL_ISCANCELLED','A.DEL_FL_REAL'
                  )
                  ->where('A.DEL_HDR_ID',$request_dtl_id)
                  ->get();

        foreach ($data_dtl as $keyx => $valuex) {
            $sql_req_dtl_id  = "SELECT SEQ_REQ_DELIVERY_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

            $dtl['REQ_DTL_ID']          = $request_dtl_id;
            $dtl['REQ_HDR_ID']          = $request_id;
            $dtl['REQ_DTL_CONT']        = $valuex->del_dtl_cont;
            $dtl['REQ_DTL_CONT_STATUS'] = $valuex->del_dtl_cont_status;
            $dtl['REQ_DTL_CONT_HAZARD'] = $valuex->del_dtl_cont_danger;
            $dtl['REQ_DTL_VIA']         = $valuex->del_dtl_via_name;
            $dtl['REQ_DTL_NO_SEAL']     = "";
            $dtl['REQ_DTL_CONT_TYPE']   = $valuex->del_dtl_cont_type;
            $dtl['REQ_DTL_CONT_SIZE']   = $valuex->del_dtl_cont_size;
            $dtl['REQ_DTL_COMMODITY']   = $valuex->del_dtl_cmdty_name;
            $dtl['REQ_DTL_CONT_WEIGHT'] = "";
            $dtl['REQ_DTL_MARK']        = "";
            $dtl['REQ_DTL_STATUS']      = "0";
            $dtl['REQ_DTL_ACTIVE']      = "Y";
            $dtl['REQ_DTL_DEL_DATE']    = $valuex->del_dtl_date_plan;
            $dtl['REQ_DTL_CANCELLED']   = "N";
            $dtl['REQ_DTL_VIA_ID']      = $valuex->del_dtl_via;
            $dtl['REQ_DTL_TL']          = "N"; #sementara N dulu

            DB::connection('npks')->table('TX_REQ_DELIVERY_DTL')->insert($dtl);
        }

        return 'success';
    }

    public static function getDeliveryCargo($nota_no){

        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_DEL_CARGO A')
          ->join('TX_HDR_NOTA B', 'A.DEL_CARGO_NO', '=', 'B.NOTA_REQ_NO')
          ->select('A.DEL_CARGO_ID','A.DEL_CARGO_NO','A.DEL_CARGO_DATE','A.DEL_CARGO_PAYMETHOD','A.DEL_CARGO_CUST_ID','A.DEL_CARGO_CUST_NAME','A.DEL_CARGO_CUST_NPWP','A.DEL_CARGO_CUST_ACCOUNT','A.DEL_CARGO_STACKBY_ID','A.DEL_CARGO_STACKBY_NAME','A.DEL_CARGO_VESSEL_CODE','A.DEL_CARGO_VESSEL_NAME','A.DEL_CARGO_VOYIN','A.DEL_CARGO_VOYOUT','A.DEL_CARGO_VVD_ID','A.DEL_CARGO_POL','A.DEL_CARGO_POD','A.DEL_CARGO_BRANCH_ID','A.DEL_CARGO_NOTA','A.DEL_CARGO_CORRECTION','A.DEL_CARGO_CORRECTION_DATE','A.DEL_CARGO_PRINT_CARD','A.DEL_CARGO_TO','A.DEL_CARGO_CREATE_DATE','A.DEL_CARGO_CREATE_BY','A.DEL_CARGO_VESSEL_AGENT','A.DEL_CARGO_VESSEL_AGENT_NAME','A.DEL_CARGO_STATUS','A.DEL_CARGO_CUST_ADDRESS','A.DEL_CARGO_EXT_FROM','A.DEL_CARGO_EXT_LOOP','A.DEL_CARGO_EXT_STATUS','A.DEL_CARGO_CORRECTION_FROM','A.DEL_CARGO_BRANCH_CODE','A.DEL_CARGO_PBM_ID','A.DEL_CARGO_PBM_NAME','A.DEL_CARGO_VESSEL_PKK','A.DEL_CARGO_EXT_FROM_DATE','A.DEL_CARGO_BTL_FROM','A.DEL_CARGO_BTL_FROM_ID','A.DEL_CARGO_BTL_STATUS','A.APP_ID','A.DEL_CARGO_MSG','A.DEL_CARGO_VESSEL_ETA','A.DEL_CARGO_VESSEL_ETD','B.NOTA_NO','B.NOTA_REQ_DATE','B.NOTA_PAID_DATE'
        )
          ->where('B.NOTA_NO', $nota_no)
          ->get();

        foreach ($data_head as $key => $value) {
            $sql_req_id   = "SELECT SEQ_TX_REQ_DELIVERY_BRG_HDR.NEXTVAL FROM DUAL";
            $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
            $request_id   = $data_req_id[0]->nextval;

            $head['REQUEST_ID']             = $request_id;
            $head['REQUEST_NO']             = $value->del_cargo_no;
            $head['REQUEST_CONSIGNEE_ID']   = $value->del_cargo_cust_id;
            $head['REQUEST_MARK']           = "";
            $head['REQUEST_BRANCH_ID']      = $value->del_cargo_branch_id;
            $head['REQUEST_CREATE_DATE']    = $value->del_cargo_create_date;
            $head['REQUEST_CREATE_BY']      = $value->del_cargo_create_by;
            $head['REQUEST_NOTA']           = $value->nota_no;
            $head['REQUEST_NO_TPK']         = "";
            $head['REQUEST_DO_NO']          = "";
            $head['REQUEST_BL_NO']          = "";
            $head['REQUEST_SPPB_NO']        = "";
            $head['REQUEST_SPPB_DATE']      = "";
            $head['REQUEST_DATE']           = $value->del_cargo_date;
            $head['REQUEST_NOTA_DATE']      = $value->nota_req_date;
            $head['REQUEST_PAID_DATE']      = $value->nota_paid_date;
            $head['REQUEST_FROM']           = "DEPO";
            $head['REQUEST_STATUS']         = "0";
            $head['REQUEST_DI']             = "";
            $head['REQUEST_PAYMENT_METHOD'] = "2";

            DB::connection('npks')->table('TX_REQ_DELIVERY_BRG_HDR')->insert($head);
        }

        $request_dtl_id = $data_head[0]->del_cargo_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_DEL_CARGO A')
          ->select(
        'A.DEL_CARGO_DTL_ID','A.DEL_CARGO_HDR_ID','A.DEL_CARGO_DTL_SI_NO','A.DEL_CARGO_DTL_QTY','A.DEL_CARGO_DTL_VIA','A.DEL_CARGO_DTL_PKG_ID','A.DEL_CARGO_DTL_PKG_NAME','A.DEL_CARGO_DTL_UNIT_ID','A.DEL_CARGO_DTL_UNIT_NAME','A.DEL_CARGO_DTL_CMDTY_ID','A.DEL_CARGO_DTL_CMDTY_NAME','A.DEL_CARGO_DTL_CHARACTER_ID','A.DEL_CARGO_DTL_CHARACTER_NAME','A.DEL_CARGO_DTL_DEL_DATE','A.DEL_CARGO_DTL_CREATE_DATE','A.DEL_CARGO_DTL_STACK_DATE','A.DEL_CARGO_DTL_EXT_DATE','A.DEL_CARGO_DTL_VIA_NAME','A.DEL_CARGO_DTL_OWNER','A.DEL_CARGO_DTL_OWNER_NAME','A.DEL_CARGO_DTL_PKG_PARENT_ID','A.DEL_CARGO_DTL_ISCANCELLED','A.DEL_CARGO_DTL_STACK_AREA','A.DEL_CARGO_DTL_STACK_AREA_NAME','A.DEL_CARGO_DTL_REAL_QTY','A.DEL_CARGO_FL_REAL','A.DEL_CARGO_DTL_CANC_QTY','A.DEL_CARGO_DTL_REAL_DATE'
          )
          ->where('A.DEL_CARGO_HDR_ID',$request_dtl_id)
          ->get();

          foreach ($data_dtl as $keyx => $valuex) {
            $sql_req_dtl_id  = "SELECT SEQ_TX_REQ_DELIVERY_BRG_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;


            $dtl['REQUEST_DTL_ID']              = $request_dtl_id;
            $dtl['REQUEST_HDR_ID']              = $request_id;
            $dtl['REQUEST_DTL_SI']              = $valuex->del_cargo_dtl_si_no;
            $dtl['REQUEST_DTL_COMMODITY']       = $valuex->del_cargo_dtl_cmdty_name;
            $dtl['REQUEST_DTL_DANGER']          = $valuex->del_cargo_dtl_character_id;
            $dtl['REQUEST_DTL_VOY']             = "";
            $dtl['REQUEST_DTL_VESSEL_NAME']     = "";
            $dtl['REQUEST_DTL__VESSEL_CODE']    = "";
            $dtl['REQUEST_DTL_CALL_SIGN']       = "";
            $dtl['REQUEST_DTL_DEST_DEPO']       = "";
            $dtl['REQUEST_DTL_STATUS']          = "0";
            $dtl['REQUEST_DTL_OWNER_CODE']      = $valuex->del_cargo_dtl_owner;
            $dtl['REQUEST_DTL_OWNER_NAME']      = $valuex->del_cargo_dtl_owner_name;
            $dtl['REQUEST_DTL_TOTAL']           = $valuex->del_cargo_dtl_qty;
            $dtl['REQUEST_DTL_UNIT']            = $valuex->del_cargo_dtl_unit_name;

            DB::connection('npks')->table('TX_REQ_DELIVERY_BRG_DTL')->insert($dtl);
        }
    return 'success';

    }

    public static function getExtDel($nota_no){
        #header
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_DEL B')
            ->join('TX_HDR_NOTA A', 'A.NOTA_REQ_NO', '=', 'B.DEL_NO')
            ->select(
                'B.DEL_ID',
                'B.DEL_NO',
                'B.DEL_CREATE_DATE',
                'B.DEL_EXT_FROM',
                'B.DEL_EXT_LOOP',
                'B.DEL_EXT_FROM_DATE',
                'B.DEL_DATE',
                'A.NOTA_NO',
                'A.NOTA_REQ_DATE',
                'A.NOTA_PAID_DATE'
            )
            ->where('A.NOTA_NO', $nota_no)
            ->first();

        $data_req = DB::connection('npks')
                    ->table('TX_REQ_DELIVERY_HDR')
                    ->select('REQ_ID')
                    ->where('REQ_NO', $data_head->del_ext_from)
                    ->first();

        $affected1 = DB::connection('npks')->table('TX_REQ_DELIVERY_HDR')
              ->where('REQ_ID', $data_req->req_id)
              ->update([
                    'REQ_NO'                => $data_head->del_no,
                    'REQ_DELIVERY_DATE'     => $data_head->del_date,
                    'REQ_CREATE_DATE'       => $data_head->del_create_date,
                    'REQUEST_NOTA_DATE'     => $data_head->nota_req_date,
                    'REQUEST_PAID_DATE'     => $data_head->nota_paid_date,
                    'REQUEST_EXTEND_FROM'   => $data_head->del_ext_from,
                    'REQUEST_EXTEND_LOOP'   => $data_head->del_ext_loop
                ]);

        #detail
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_DEL')
                        ->select('DEL_DTL_DATE_PLAN','DEL_DTL_CONT')
                        ->where('DEL_HDR_ID', $data_head->del_id)
                        ->get();

        foreach ($data_dtl as $key => $value) {
            $affected2 = DB::connection('npks')->table('TX_REQ_DELIVERY_DTL')
              ->where('REQ_HDR_ID', $data_req->req_id)
              ->where('REQ_DTL_CONT', $value->del_dtl_cont)
              ->update([
                    'REQ_DTL_DEL_DATE' => $value->del_dtl_date_plan
                ]);
        }

        return 'success';

    }

    public static function getExtDeliveryCargo($nota_no){

    }

    public static function getExtStuff($nota_no){
        #header
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_STUFF B')
            ->join('TX_HDR_NOTA A', 'A.NOTA_REQ_NO', '=', 'B.STUFF_NO')
            ->select(
                'B.STUFF_ID',
                'B.STUFF_NO',
                'B.STUFF_CREATE_DATE',
                'B.STUFF_EXT_FROM',
                'B.STUFF_EXT_LOOP',
                'B.STUFF_EXT_FROM_DATE',
                'A.NOTA_NO',
                'A.NOTA_REQ_DATE',
                'A.NOTA_PAID_DATE'
            )
            ->where('A.NOTA_NO', $nota_no)
            ->first();

        $data_req = DB::connection('npks')
                    ->table('TX_REQ_STUFF_HDR')
                    ->select('STUFF_ID')
                    ->where('STUFF_NO', $data_head->stuff_ext_from)
                    ->first();

        $affected1 = DB::connection('npks')->table('TX_REQ_STUFF_HDR')
              ->where('REQ_ID', $data_req->stuff_id)
              ->update([
                    'STUFF_NO'            => $data_head->stuff_no,
                    'STUFF_CREATE_DATE'   => $data_head->stuff_create_date,
                    'STUFF_NOTA_DATE'     => $data_head->nota_req_date,
                    'STUFF_PAID_DATE'     => $data_head->nota_paid_date,
                    'STUFF_EXTEND_FROM'   => $data_head->stuff_ext_from,
                    'STUFF_EXTEND_LOOP'   => $data_head->stuff_ext_loop
                ]);

        #detail
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_STUFF')
                        ->select('STUFF_DTL_CONT','STUFF_DTL_START_DATE','STUFF_DTL_END_DATE')
                        ->where('STUFF_HDR_ID', $data_head->stuff_id)
                        ->get();

        foreach ($data_dtl as $key => $value) {
            $affected2 = DB::connection('npks')->table('TX_REQ_STUFF_DTL')
              ->where('STUFF_DTL_HDR_ID', $data_req->stuff_id)
              ->where('STUFF_DTL_CONT', $value->stuff_dtl_cont)
              ->update([
                    'STUFF_DTL_START_STUFF_PLAN' => $value->stuff_dtl_start_date,
                    'STUFF_DTL_END_STUFF_PLAN'   => $value->stuff_dtl_end_date
                ]);
        }
        return 'success';
    }

    public static function getExtStripp($nota_no){
        #header
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_STRIPP B')
            ->join('TX_HDR_NOTA A', 'A.NOTA_REQ_NO', '=', 'B.STRIPP_NO')
            ->select(
                'B.STRIPP_ID',
                'B.STRIPP_NO',
                'B.STRIPP_CREATE_DATE',
                'B.STRIPP_EXT_FROM',
                'B.STRIPP_EXT_LOOP',
                'B.STRIPP_EXT_FROM_DATE',
                'A.NOTA_NO',
                'A.NOTA_REQ_DATE',
                'A.NOTA_PAID_DATE'
            )
            ->where('A.NOTA_NO', $nota_no)
            ->first();

        $data_req = DB::connection('npks')
                    ->table('TX_REQ_STRIP_HDR')
                    ->select('STRIP_ID')
                    ->where('STRIP_NO', $data_head->stripp_ext_from)
                    ->first();

        $affected1 = DB::connection('npks')->table('TX_REQ_STRIP_HDR')
              ->where('STRIP_ID', $data_req->strip_id)
              ->update([
                    'STRIP_NO'            => $data_head->stripp_no,
                    'STRIP_CREATE_DATE'   => $data_head->stripp_create_date,
                    'STRIP_NOTA_DATE'     => $data_head->nota_req_date,
                    'STRIP_PAID_DATE'     => $data_head->nota_paid_date,
                    'STRIP_EXTEND_FROM'   => $data_head->stripp_ext_from,
                    'STRIP_EXTEND_LOOP'   => $data_head->stripp_ext_loop
                ]);

        #detail
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_STRIPP')
                        ->select('STRIPP_DTL_CONT','STRIPP_DTL_START_DATE','STRIPP_DTL_END_DATE')
                        ->where('STRIPP_HDR_ID', $data_head->stripp_id)
                        ->get();

        foreach ($data_dtl as $key => $value) {
            $affected2 = DB::connection('npks')->table('TX_REQ_STRIP_DTL')
              ->where('STRIP_DTL_HDR_ID', $data_req->strip_id)
              ->where('STRIP_DTL_CONT', $value->stripp_dtl_cont)
              ->update([
                    'STRIP_DTL_START_STRIP_PLAN' => $value->stripp_dtl_start_date,
                    'STRIP_DTL_END_STRIP_PLAN'   => $value->stripp_dtl_end_date
                ]);
        }
         return 'success';
    }

    public static function getReveivingCargo($nota_no){
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_REC_CARGO A')
            ->join('TX_HDR_NOTA B', 'A.REC_CARGO_NO', '=', 'B.NOTA_REQ_NO')
            ->select(
                'A.REC_CARGO_ID',
                'A.REC_CARGO_NO',
                'A.REC_CARGO_DATE',
                'A.REC_CARGO_PAYMETHOD',
                'A.REC_CARGO_CUST_ID',
                'A.REC_CARGO_CUST_NAME',
                'A.REC_CARGO_CUST_NPWP',
                'A.REC_CARGO_CUST_ACCOUNT',
                'A.REC_CARGO_STACKBY_ID',
                'A.REC_CARGO_STACKBY_NAME',
                'A.REC_CARGO_VESSEL_CODE',
                'A.REC_CARGO_VESSEL_NAME',
                'A.REC_CARGO_VOYIN',
                'A.REC_CARGO_VOYOUT',
                'A.REC_CARGO_VVD_ID',
                'A.REC_CARGO_POL',
                'A.REC_CARGO_POD',
                'A.REC_CARGO_BRANCH_ID',
                'A.REC_CARGO_NOTA',
                'A.REC_CARGO_CORRECTION',
                'A.REC_CARGO_CORRECTION_DATE',
                'A.REC_CARGO_PRINT_CARD',
                'A.REC_CARGO_FROM',
                'A.REC_CARGO_CREATE_BY',
                'A.REC_CARGO_CREATE_DATE',
                'A.REC_CARGO_STATUS',
                'A.REC_CARGO_VESSEL_AGENT',
                'A.REC_CARGO_VESSEL_AGENT_NAME',
                'A.REC_CARGO_CUST_ADDRESS',
                'A.REC_CARGO_CORRECTION_FROM',
                'A.REC_CARGO_BRANCH_CODE',
                'A.REC_CARGO_PBM_ID',
                'A.REC_CARGO_PBM_NAME',
                'A.REC_CARGO_VESSEL_PKK',
                'A.REC_CARGO_BTL_STATUS',
                'A.REC_CARGO_BTL_FROM',
                'A.REC_CARGO_BTL_FROM_ID',
                'A.REC_CARGO_MSG',
                'A.REC_CARGO_VESSEL_ETA',
                'A.REC_CARGO_VESSEL_ETD',
                'A.APP_ID',
                'B.NOTA_NO',
                'B.NOTA_REQ_DATE',
                'B.NOTA_PAID_DATE'
            )
            ->where('B.NOTA_NO', $nota_no)
            ->get();

            foreach ($data_head as $key => $value) {
                $sql_req_id   = "SELECT SEQ_REQ_REC_BRG_HDR.NEXTVAL FROM DUAL";
                $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
                $request_id   = $data_req_id[0]->nextval;

                $head['request_id']             = $request_id;
                $head['request_no']             = $value->rec_cargo_no;
                $head['request_consignee_id']   = $value->rec_cargo_cust_id;
                $head['request_mark']           = '';
                $head['request_branch_id']      = ($value->rec_cargo_branch_id == '99') ? '10' : $value->rec_cargo_branch_id;
                $head['request_create_date']    = $value->rec_cargo_create_date;
                $head['request_create_by']      = $value->rec_cargo_create_by;
                $head['request_nota']           = $nota_no;
                $head['request_no_tpk']         = '';
                $head['request_do_no']          = '';
                $head['request_bl_no']          = '';
                $head['request_sppb_no']        = '';
                $head['request_sppb_date']      = '';
                $head['request_receiving_date'] = $value->rec_cargo_date;
                $head['request_nota_date']      = $value->nota_req_date;
                $head['request_paid_date']      = $value->nota_paid_date;
                $head['request_from']           = ($value->rec_cargo_from == '1') ? 'DEPO' : $value->rec_cargo_from;
                $head['request_status']         = '1';
                $head['request_di']             = '';
                $head['request_payment_method'] = $value->rec_cargo_paymethod;
                DB::connection('npks')->table('TX_REQ_RECEIVING_BRG_HDR')->insert($head);
        }

        $request_hdr_id = $data_head[0]->rec_cargo_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_REC_CARGO A')
                    ->select('A.REC_CARGO_DTL_ID',
                            'A.REC_CARGO_HDR_ID',
                            'A.REC_CARGO_DTL_SI_NO',
                            'A.REC_CARGO_DTL_QTY',
                            'A.REC_CARGO_DTL_VIA',
                            'A.REC_CARGO_DTL_PKG_ID',
                            'A.REC_CARGO_DTL_PKG_NAME',
                            'A.REC_CARGO_DTL_UNIT_ID',
                            'A.REC_CARGO_DTL_UNIT_NAME',
                            'A.REC_CARGO_DTL_CMDTY_ID',
                            'A.REC_CARGO_DTL_CMDTY_NAME',
                            'A.REC_CARGO_DTL_CHARACTER_ID',
                            'A.REC_CARGO_DTL_CHARACTER_NAME',
                            'A.REC_CARGO_DTL_REC_DATE',
                            'A.REC_CARGO_DTL_CREATE_DATE',
                            'A.REC_CARGO_DTL_VIA_NAME',
                            'A.REC_CARGO_DTL_OWNER',
                            'A.REC_CARGO_DTL_OWNER_NAME',
                            'A.REC_CARGO_DTL_PKG_PARENT_ID',
                            'A.REC_CARGO_DTL_ISCANCELLED',
                            'A.REC_CARGO_DTL_STACK_AREA',
                            'A.REC_CARGO_DTL_STACK_AREA_NAME',
                            'A.REC_CARGO_DTL_REAL_QTY',
                            'A.REC_CARGO_FL_REAL',
                            'A.REC_CARGO_REMAINING_QTY',
                            'A.REC_CARGO_DTL_CANC_QTY'
                    )
                    ->where('A.REC_CARGO_HDR_ID',$request_hdr_id)
                    ->get();

        foreach ($data_dtl as $keyx => $valuex) {
            $sql_req_dtl_id  = "SELECT SEQ_REQ_REC_BRG_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

            $dtl['request_dtl_id']              = $request_dtl_id;
            $dtl['request_hdr_id']              = $request_id;
            $dtl['request_dtl_si']              = $valuex->rec_cargo_dtl_si_no;
            $dtl['request_dtl_commodity']       = $valuex->rec_cargo_dtl_cmdty_name;
            $dtl['request_dtl_danger']          = '0';
            $dtl['request_dtl_voy']             = '';
            $dtl['request_dtl_vessel_name']     = '';
            $dtl['request_dtl__vessel_code']    = '';
            $dtl['request_dtl_call_sign']       = '';
            $dtl['request_dtl_dest_depo']       = '';
            $dtl['request_dtl_status']          = '0';
            $dtl['request_dtl_owner_code']      = $valuex->rec_cargo_dtl_owner;
            $dtl['request_dtl_owner_name']      = $valuex->rec_cargo_dtl_owner_name;
            $dtl['request_dtl_total']           = $valuex->rec_cargo_dtl_qty;
            $dtl['request_dtl_unit']            = $valuex->rec_cargo_dtl_unit_name;

            DB::connection('npks')->table('TX_REQ_RECEIVING_BRG_DTL')->insert($dtl);
        }
        return 'success';
    }

    public static function getRelokasi($nota_no){
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_RELOKASI RLC')
            ->join('TX_HDR_NOTA NOTA', 'RLC.RELOKASI_NO', '=', 'NOTA.NOTA_REQ_NO')
            ->select(
                'RLC.APP_ID',
                'RLC.ID_RELOKASI_TYPE',
                'RLC.RELOKASI_ACCOUNT',
                'RLC.RELOKASI_BRANCH_CODE',
                'RLC.RELOKASI_BRANCH_ID',
                'RLC.RELOKASI_BTL_FROM',
                'RLC.RELOKASI_BTL_FROM_ID',
                'RLC.RELOKASI_BTL_STATUS',
                'RLC.RELOKASI_CORRECTION',
                'RLC.RELOKASI_CORRECTION_DATE',
                'RLC.RELOKASI_CORRECTION_FROM',
                'RLC.RELOKASI_CREATE_BY',
                'RLC.RELOKASI_CREATE_DATE',
                'RLC.RELOKASI_CUST_ACCOUNT',
                'RLC.RELOKASI_CUST_ADDRESS',
                'RLC.RELOKASI_CUST_ID',
                'RLC.RELOKASI_CUST_NAME',
                'RLC.RELOKASI_CUST_NPWP',
                'RLC.RELOKASI_DATE',
                'RLC.RELOKASI_FL_REAL',
                'RLC.RELOKASI_FROM',
                'RLC.RELOKASI_ID',
                'RLC.RELOKASI_MSG',
                'RLC.RELOKASI_NO',
                'RLC.RELOKASI_NOTA',
                'RLC.RELOKASI_PAYMETHOD',
                'RLC.RELOKASI_PBM_ID',
                'RLC.RELOKASI_PBM_NAME',
                'RLC.RELOKASI_STATUS',
                'RLC.RELOKASI_VESSEL_AGENT',
                'RLC.RELOKASI_VESSEL_AGENT_NAME',
                'RLC.RELOKASI_VESSEL_CODE',
                'RLC.RELOKASI_VESSEL_ETA',
                'RLC.RELOKASI_VESSEL_ETD',
                'RLC.RELOKASI_VESSEL_NAME',
                'RLC.RELOKASI_VESSEL_PKK',
                'RLC.RELOKASI_VESSEL_POD',
                'RLC.RELOKASI_VESSEL_POL',
                'RLC.RELOKASI_VOYIN',
                'RLC.RELOKASI_VOYOUT',
                'RLC.RELOKASI_VVD_ID',
                'NOTA.NOTA_NO',
                'NOTA.NOTA_PAID_DATE',
                'NOTA.NOTA_REQ_DATE'
            )
            ->where('RLC.RELOKASI_STATUS', '3')
            ->where('NOTA.NOTA_NO', $nota_no)
            ->get();
        //print_r($data_head);
        foreach ($data_head as $key => $value) {
            $sql_req_id   = "SELECT SEQ_TX_REQ_RELOKASI_HDR.NEXTVAL FROM DUAL";
            $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
            $request_id   = $data_req_id[0]->nextval;

            $head['request_id']             = $request_id;
            $head['request_no']             = $value->relokasi_no;
            $head['request_consignee_id']   = $value->relokasi_cust_id;
            $head['request_relokasi_date']  = $value->relokasi_date;
            $head['request_create_by']      = $value->relokasi_create_by;
            $head['request_create_date']    = $value->relokasi_create_date;
            $head['request_branch_id']      = $value->relokasi_branch_id;
            $head['request_nota_date']      = $value->nota_req_date;
            $head['request_paid_date']      = $value->nota_paid_date;
            $head['request_status']         = '1';
            $head['request_rd']             = 'N';
            $head['request_payment_method'] = $value->relokasi_paymethod;
            $head['id_relokasi_type']       = $value->id_relokasi_type;
            $head['request_nota']           = $value->nota_no;

            DB::connection('npks')->table('TX_REQ_RELOKASI_HDR')->insert($head);
        }

        $request_hdr_id = $data_head[0]->relokasi_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_RELOKASI DTL')
            ->join('TX_HDR_RELOKASI HDR', 'HDR.RELOKASI_ID', '=', 'DTL.RELOKASI_HDR_ID')
            ->select(
                'DTL.RELOKASI_DTL_ID',
                'DTL.RELOKASI_HDR_ID',
                'DTL.RELOKASI_DTL_CONT',
                'DTL.RELOKASI_DTL_CONT_SIZE',
                'DTL.RELOKASI_DTL_CONT_TYPE',
                'DTL.RELOKASI_DTL_CONT_STATUS',
                'DTL.RELOKASI_DTL_CONT_DANGER',
                'DTL.RELOKASI_DTL_CMDTY_ID',
                'DTL.RELOKASI_DTL_CMDTY_NAME',
                'DTL.RELOKASI_DTL_OWNER',
                'DTL.RELOKASI_DTL_OWNER_NAME',
                'DTL.RELOKASI_DTL_ISACTIVE',
                'DTL.RELOKASI_DTL_ACTIVITY_DATE',
                'DTL.RELOKASI_DTL_TYPE',
                'DTL.RELOKASI_DTL_TYPE_NAME',
                'DTL.RELOKASI_FL_REAL',
                'DTL.RELOKASI_DTL_ISCANCELLED',
                'DTL.RELOKASI_DTL_START_DATE',
                'DTL.RELOKASI_DTL_END_DATE'
            )
            ->where('DTL.RELOKASI_HDR_ID',$request_hdr_id)
            ->get();

            //print_r($data_dtl);
        foreach ($data_dtl as $key => $value) {
            $sql_req_dtl_id  = "SELECT SEQ_TX_REQ_RELOKASI_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

            $dtl['request_dtl_id']          = $request_dtl_id;
            $dtl['request_hdr_id']          = $request_id;
            $dtl['request_dtl_cont']        = $value->relokasi_dtl_cont;
            $dtl['request_dtl_cont_status'] = $value->relokasi_dtl_cont_status;
            $dtl['request_dtl_cont_type']   = $value->relokasi_dtl_cont_type;
            $dtl['request_dtl_cont_size']   = $value->relokasi_dtl_cont_size;
            $dtl['request_dtl_commodity']   = $value->relokasi_dtl_cmdty_name;
            $dtl['request_dtl_tl']          = 'N';
            $dtl['request_dtl_status']      = 0;
            $dtl['request_dtl_cancelled']   = $value->relokasi_dtl_iscancelled;
            $dtl['request_dtl_active']      = $value->relokasi_dtl_isactive;
            $dtl['plan_start_date']         = $value->relokasi_dtl_start_date;
            $dtl['plan_end_date']           = $value->relokasi_dtl_end_date;

            DB::connection('npks')->table('TX_REQ_RELOKASI_DTL')->insert($dtl);
        }

        return 'success';
    }

    public static function getStripping($nota_no)
    {
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_STRIPP STR')
            ->join('TX_HDR_NOTA NOTA', 'STR.STRIPP_NO', '=', 'NOTA.NOTA_REQ_NO')
            ->select(
                'STR.STRIPP_ID',
                'STR.STRIPP_NO',
                'STR.STRIPP_DATE',
                'STR.STRIPP_PAYMETHOD',
                'STR.STRIPP_CUST_ID',
                'STR.STRIPP_CUST_NAME',
                'STR.STRIPP_CUST_NPWP',
                'STR.STRIPP_CUST_ADDRESS',
                'STR.STRIPP_CUST_ACCOUNT',
                'STR.STRIPP_STACKBY_ID',
                'STR.STRIPP_STACKBY_NAME',
                'STR.STRIPP_VESSEL_CODE',
                'STR.STRIPP_VESSEL_NAME',
                'STR.STRIPP_VOYIN',
                'STR.STRIPP_VOYOUT',
                'STR.STRIPP_VVD_ID',
                'STR.STRIPP_VESSEL_POL',
                'STR.STRIPP_VESSEL_POD',
                'STR.STRIPP_BRANCH_ID',
                'STR.STRIPP_NOTA',
                'STR.STRIPP_CORRECTION',
                'STR.STRIPP_CORRECTION_DATE',
                'STR.STRIPP_PRINT_CARD',
                'STR.STRIPP_FROM',
                'STR.STRIPP_BL',
                'STR.STRIPP_DO',
                'STR.STRIPP_VESSEL_AGENT_NAME',
                'STR.STRIPP_VESSEL_AGENT',
                'STR.STRIPP_CREATE_BY',
                'STR.STRIPP_CREATE_DATE',
                'STR.STRIPP_STATUS',
                'STR.STRIPP_REC_NO',
                'STR.STRIPP_EXT_FROM',
                'STR.STRIPP_EXT_LOOP',
                'STR.STRIPP_EXT_STATUS',
                'STR.STRIPP_CORRECTION_FROM',
                'STR.STRIPP_SETTING',
                'STR.STRIPP_BRANCH_CODE',
                'STR.STRIPP_PBM_ID',
                'STR.STRIPP_PBM_NAME',
                'STR.STRIPP_VESSEL_PKK',
                'STR.STRIPP_BTL_FROM_ID',
                'STR.STRIPP_BTL_FROM',
                'STR.STRIPP_BTL_STATUS',
                'STR.STRIPP_EXT_FROM_DATE',
                'STR.STRIPP_MSG',
                'STR.STRIPP_VESSEL_ETA',
                'STR.STRIPP_VESSEL_ETD',
                'STR.APP_ID',
                'NOTA.NOTA_NO',
                'NOTA.NOTA_PAID_DATE',
                'NOTA.NOTA_REQ_DATE'
            )
            ->where('STR.STRIPP_STATUS', '3')
            ->where('NOTA.NOTA_NO', $nota_no)
            ->first();

        $sql_req_id   = "SELECT SEQ_REQ_STRIP_HDR.NEXTVAL FROM DUAL";
        $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
        $request_id   = $data_req_id[0]->nextval;

        $head['STRIP_BL']                         = $data_head->stripp_bl;
        $head['STRIP_BRANCH_ID']                  = $data_head->stripp_branch_id;
        $head['STRIP_CONSIGNEE_ID']               = $data_head->stripp_cust_id;
        $head['STRIP_CREATE_BY']                  = $data_head->stripp_create_by;
        $head['STRIP_CREATE_DATE']                = $data_head->stripp_create_date;
        $head['STRIP_DO']                         = $data_head->stripp_do;
        $head['STRIP_EXTEND_FROM']                = $data_head->stripp_ext_from;
        $head['STRIP_EXTEND_LOOP']                = $data_head->stripp_ext_loop;
        $head['STRIP_ID']                         = $request_id;
        $head['STRIP_MARK']                       = '';
        $head['STRIP_NO']                         = $data_head->stripp_no;
        $head['STRIP_NOREQ_RECEIVING']            = $data_head->stripp_rec_no;
        $head['STRIP_NOTA_DATE']                  = $data_head->nota_req_date;
        $head['STRIP_NOTA_NO']                    = $data_head->nota_no;
        $head['STRIP_ORIGIN']                     = $data_head->stripp_from;
        $head['STRIP_PAID_DATE']                  = $data_head->nota_paid_date;
        $head['STRIP_PAYMENT_METHOD']             = $data_head->stripp_paymethod;
        $head['STRIP_SPPB']                       = '';
        $head['STRIP_SPPB_DATE']                  = '';
        $head['STRIP_STATUS']                     = $data_head->stripp_status;
        $head['STRIP_TYPE']                       = '';
        DB::connection('npks')->table('TX_REQ_STRIP_HDR')->insert($head);

        $request_hdr_id = $data_head->stripp_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_STRIPP A')
            ->join('TX_HDR_STRIPP B', 'B.STRIPP_ID', '=', 'A.STRIPP_HDR_ID')
            ->select(
                'A.STRIPP_DTL_CMDTY_ID',
                'A.STRIPP_DTL_CMDTY_NAME',
                'A.STRIPP_DTL_CONT',
                'A.STRIPP_DTL_CONT_DANGER',
                'A.STRIPP_DTL_CONT_SIZE',
                'A.STRIPP_DTL_CONT_STATUS',
                'A.STRIPP_DTL_CONT_TO',
                'A.STRIPP_DTL_CONT_TYPE',
                'A.STRIPP_DTL_CMDTY_NAME',
                'A.STRIPP_DTL_DEL_DATE',
                'A.STRIPP_DTL_DEL_VIA',
                'A.STRIPP_DTL_DEL_VIA_NAME',
                'A.STRIPP_DTL_END_DATE',
                'A.STRIPP_DTL_ID',
                'A.STRIPP_DTL_ISACTIVE',
                'A.STRIPP_DTL_ISCANCELLED',
                'A.STRIPP_DTL_OWNER',
                'A.STRIPP_DTL_OWNER_NAME',
                'A.STRIPP_DTL_REAL_DATE',
                'A.STRIPP_DTL_REC_DATE',
                'A.STRIPP_DTL_SEAL_NO',
                'A.STRIPP_DTL_SP2',
                'A.STRIPP_DTL_STACK_DATE',
                'A.STRIPP_DTL_STACKING_AREA',
                'A.STRIPP_DTL_START_DATE',
                'A.STRIPP_DTL_VIA',
                'A.STRIPP_DTL_VIA_NAME',
                'A.STRIPP_EXT_DATE',
                'A.STRIPP_FL_REAL',
                'A.STRIPP_HDR_ID'
            )
            ->where('A.STRIPP_HDR_ID', $request_hdr_id)
            ->get();

        $branch_id = $data_head->stripp_branch_id;
        foreach ($data_dtl as $data) {
            $sql_req_dtl_id  = "SELECT SEQ_REQ_STRIP_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

            $dtl = [
                'STRIP_DTL_ID'                => $request_dtl_id,
                'STRIP_DTL_HDR_ID'            => $request_id,
                'STRIP_DTL_CONT'              => $data->stripp_dtl_cont,
                'STRIP_DTL_LOCATION'          => '',
                'STRIP_DTL_CONT_STATUS'       => $data->stripp_dtl_cont_status,
                'STRIP_DTL_CONT_SIZE'         => $data->stripp_dtl_cont_size,
                'STRIP_DTL_CONT_TYPE'         => $data->stripp_dtl_cont_type,
                'STRIP_DTL_COMMODITY'         => $data->stripp_dtl_cmdty_name,
                'STRIP_DTL_DANGER'            => $data->stripp_dtl_cont_danger,
                'STRIP_DTL_VOY'               => '',
                'STRIP_DTL_VESSEL_NAME'       => $data_head->stripp_vessel_name,
                'STRIP_DTL_VESSEL_CODE'       => $data_head->stripp_vessel_code,
                'STRIP_DTL_CALL_SIGN'         => '',
                'STRIP_DTL_DEST_DEPO'         => '',
                'STRIP_DTL_UNLOADING_DATE'    => '',
                'STRIP_DTL_DEST_AFTER_STRIP'  => '',
                'STRIP_DTL_START_STRIP_PLAN'  => $data->stripp_dtl_start_date,
                'STRIP_DTL_END_STRIP_PLAN'    => $data->stripp_dtl_end_date,
                'STRIP_DTL_STATUS'            => 0,
                'STRIP_DTL_ACTIVE'            => $data->stripp_dtl_isactive,
                'STRIP_DTL_ORIGIN'            => '',
                'STRIP_DTL_CANCELLED'         => $data->stripp_dtl_iscancelled,
                'STRIP_DTL_COUNTER'           => ''
            ];

            DB::connection('npks')->table('TX_REQ_STRIP_DTL')->insert($dtl);
        }

        return 'success';
    }

    public static function getStuffing($nota_no){
        $data_head = DB::connection('omuster_ilcs')->table('TX_HDR_STUFF STF')
            ->join('TX_HDR_NOTA NOTA', 'STF.STUFF_NO', '=', 'NOTA.NOTA_REQ_NO')
            ->select(
                'STF.STUFF_ID',
                'STF.STUFF_NO',
                'STF.STUFF_DATE',
                'STF.STUFF_PAYMETHOD',
                'STF.STUFF_CUST_ID',
                'STF.STUFF_CUST_NAME',
                'STF.STUFF_CUST_NPWP',
                'STF.STUFF_CUST_ACCOUNT',
                'STF.STUFF_STACKBY_ID',
                'STF.STUFF_STACKBY_NAME',
                'STF.STUFF_VESSEL_CODE',
                'STF.STUFF_VESSEL_NAME',
                'STF.STUFF_VOYIN',
                'STF.STUFF_VOYOUT',
                'STF.STUFF_VVD_ID',
                'STF.STUFF_VESSEL_POL',
                'STF.STUFF_VESSEL_POD',
                'STF.STUFF_BRANCH_ID',
                'STF.STUFF_NOTA',
                'STF.STUFF_CORRECTION',
                'STF.STUFF_CORRECTION_DATE',
                'STF.STUFF_PRINT_CARD',
                'STF.STUFF_FROM',
                'STF.STUFF_CREATE_BY',
                'STF.STUFF_CREATE_DATE',
                'STF.STUFF_BL',
                'STF.STUFF_DO',
                'STF.STUFF_STATUS',
                'STF.STUFF_EXT_FROM',
                'STF.STUFF_VESSEL_AGENT',
                'STF.STUFF_VESSEL_AGENT_NAME',
                'STF.STUFF_CUST_ADDRESS',
                'STF.STUFF_REC_NO',
                'STF.STUFF_EXT_LOOP',
                'STF.STUFF_EXT_STATUS',
                'STF.STUFF_CORRECTION_FROM',
                'STF.STUFF_SETTING',
                'STF.STUFF_BRANCH_CODE',
                'STF.STUFF_PBM_ID',
                'STF.STUFF_PBM_NAME',
                'STF.STUFF_VESSEL_PKK',
                'STF.STUFF_EXT_FROM_DATE',
                'STF.STUFF_BTL_FROM_ID',
                'STF.STUFF_BTL_FROM',
                'STF.STUFF_BTL_STATUS',
                'STF.STUFF_MSG',
                'STF.STUFF_VESSEL_ETA',
                'STF.STUFF_VESSEL_ETD',
                'STF.APP_ID',
                'NOTA.NOTA_NO',
                'NOTA.NOTA_PAID_DATE',
                'NOTA.NOTA_REQ_DATE'
            )
            ->where('STF.STUFF_STATUS', '3')
            ->where('NOTA.NOTA_NO', $nota_no)
            ->get();

          // dd($data_head);
          foreach ($data_head as $key => $value) {
            $sql_req_id   = "SELECT SEQ_REQ_STUFF_HDR.NEXTVAL FROM DUAL";
            $data_req_id  = DB::connection('npks')->select(DB::raw($sql_req_id));
            $request_id   = $data_req_id[0]->nextval;

            $head['STUFF_ID']                         = $request_id;
            $head['STUFF_NO']                         = $value->stuff_no;
            $head['STUFF_CONSIGNEE_ID']               = $value->stuff_cust_id;
            $head['STUFF_BRANCH_ID']                  = $value->stuff_branch_id;
            $head['STUFF_CREATE_BY']                  = $value->stuff_create_by;
            $head['STUFF_CREATE_DATE']                = $value->stuff_create_date;
            $head['STUFF_STATUS']                     = $value->stuff_status;
            $head['STUFF_NOTA_NO']                    = $value->nota_no;
            $head['STUFF_NOTA_DATE']                  = $value->nota_req_date;
            $head['STUFF_PAID_DATE']                  = $value->nota_paid_date;
            $head['STUFF_NO_BOOKING']                 = '';
            $head['STUFF_NO_UKK']                     = $value->stuff_vessel_pkk;
            $head['STUFF_NOREQ_RECEIVING']            = '';
            $head['STUFF_EXTEND_FROM']                = $value->stuff_ext_from;
            $head['STUFF_EXTEND_LOOP']                = $value->stuff_ext_loop;
            $head['STUFF_ORIGIN']                     = 'INTERNAL';
            $head['STUFF_ALIH_KAPAL']                 = 'T';
            $head['STUFF_MARK']                       = '';
            $head['STUFF_PAYMENT_METHOD']             = $value->stuff_paymethod;
            DB::connection('npks')->table('TX_REQ_STUFF_HDR')->insert($head);
        }

        $request_hdr_id = $data_head[0]->stuff_id;
        $data_dtl = DB::connection('omuster_ilcs')->table('TX_DTL_STUFF A')
                  ->join('TX_HDR_STUFF B', 'B.STUFF_ID', '=', 'A.STUFF_HDR_ID')
                  ->select(
                        'A.STUFF_DTL_ID',
                        'A.STUFF_HDR_ID',
                        'A.STUFF_DTL_CONT',
                        'A.STUFF_DTL_CONT_SIZE',
                        'A.STUFF_DTL_CONT_TYPE',
                        'A.STUFF_DTL_CONT_STATUS',
                        'A.STUFF_DTL_CONT_DANGER',
                        'A.STUFF_DTL_CMDTY_ID',
                        'A.STUFF_DTL_CMDTY_NAME',
                        'A.STUFF_DTL_VIA',
                        'A.STUFF_DTL_SP2',
                        'A.STUFF_DTL_STACK_DATE',
                        'A.STUFF_DTL_STUFF_DATE',
                        'A.STUFF_DTL_CONT_FROM',
                        'A.STUFF_DTL_SEAL_NO',
                        'A.STUFF_DTL_OWNER',
                        'A.STUFF_DTL_OWNER_NAME',
                        'A.STUFF_DTL_EXT_DATE',
                        'A.STUFF_DTL_REAL_DATE',
                        'A.STUFF_DTL_ISACTIVE',
                        'A.STUFF_DTL_VIA_NAME',
                        'A.STUFF_DTL_DEL_VIA',
                        'A.STUFF_DTL_DEL_VIA_NAME',
                        'A.STUFF_DTL_DEL_DATE',
                        'A.STUFF_DTL_REC_DATE',
                        'A.STUFF_DTL_START_DATE',
                        'A.STUFF_DTL_END_DATE',
                        'A.STUFF_DTL_ISCANCELLED',
                        'A.STUFF_DTL_STACKING_AREA',
                        'A.STUFF_FL_REAL'
                  )
                  ->where('A.STUFF_HDR_ID', $request_hdr_id)
                  ->get();

        // dd($data_dtl);
        foreach ($data_dtl as $keyx => $valuex) {
            $sql_req_dtl_id  = "SELECT SEQ_REQ_STUFF_DTL.NEXTVAL FROM DUAL";
            $data_req_dtl_id = DB::connection('npks')->select(DB::raw($sql_req_dtl_id));
            $request_dtl_id  = $data_req_dtl_id[0]->nextval;

                $dtl['STUFF_DTL_ID']                = $request_dtl_id;
                $dtl['STUFF_DTL_HDR_ID']            = $request_id;
                $dtl['STUFF_DTL_CONT']              = $valuex->stuff_dtl_cont;
                $dtl['STUFF_DTL_CONT_SIZE']         = $valuex->stuff_dtl_cont_size;
                $dtl['STUFF_DTL_CONT_TYPE']         = $valuex->stuff_dtl_cont_type;
                $dtl['STUFF_DTL_CONT_STATUS']       = $valuex->stuff_dtl_cont_status;
                $dtl['STUFF_DTL_STATUS']            = 0;
                $dtl['STUFF_DTL_COMMODITY']         = $valuex->stuff_dtl_cmdty_name;
                $dtl['STUFF_DTL_CONT_HAZARD']       = $valuex->stuff_dtl_cont_danger;
                $dtl['STUFF_DTL_REMARK_SP2']        = '';
                $dtl['STUFF_DTL_ACTIVE']            = $valuex->stuff_dtl_isactive;
                $dtl['STUFF_DTL_ORIGIN']            = 'DEPO';
                $dtl['STUFF_DTL_END_STUFF_PLAN']    = $valuex->stuff_dtl_end_date;
                $dtl['STUFF_DTL_START_STUFF_PLAN']  = $valuex->stuff_dtl_start_date;
                $dtl['STUFF_DTL_CANCELLED']         = $valuex->stuff_dtl_iscancelled;
                $dtl['STUFF_DTL_COUNTER']           = 1;
                $dtl['STUFF_DTL_VIA']               = $valuex->stuff_dtl_via_name;
            DB::connection('npks')->table('TX_REQ_STUFF_DTL')->insert($dtl);
        }

        return 'success';
    }
    // end getStuffing

}
