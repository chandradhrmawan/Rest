<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use Firebase\JWT\JWT;
use App\Helper\FileUpload;

class ViewExt{

  public static function splitNota($input) {
    $sql = "
    SELECT
      *
    FROM
      BILLING_MDM.TM_COMP_NOTA
    WHERE
      BRANCH_ID = ^$^branch_id
      AND BRANCH_CODE = '^$^branch_code'
      AND COMP_FORM_SHOW = 'Y'
      AND NOTA_ID = ^$^nota_id
      AND GROUP_TARIFF_ID IN
      (
        SELECT DISTINCT GROUP_TARIFF_ID FROM (
          SELECT
            GROUP_TARIFF_ID FROM BILLING_MDM.TM_COMP_NOTA
            WHERE
              NOTA_ID = ^$^nota_id
              AND BRANCH_ID = ^$^branch_id
              AND branch_code = '^$^branch_code'
              AND (COMP_REQUIRED = 'Y' OR GROUP_TARIFF_ID IN (12,13))
          UNION ALL
          SELECT DISTINCT GROUP_TARIFF_ID FROM TS_TARIFF WHERE TARIFF_PROF_HDR_ID IN
            (
            SELECT
              A.TARIFF_ID
            FROM
              TX_PROFILE_TARIFF_HDR A
              LEFT JOIN TS_CUSTOMER_PROFILE B ON B.TARIFF_HDR_ID = A.TARIFF_ID AND B.CUST_PROFILE_STATUS = '3' AND B.CUST_PROFILE_IS_ACTIVE = '1'
            WHERE
              A.BRANCH_ID = ^$^branch_id
              AND A.BRANCH_CODE = '^$^branch_code'
              AND A.TARIFF_TYPE IN ('2', '3')
              AND A.TARIFF_STATUS = '3'
              AND A.TARIFF_IS_ACTIVE = '1'
              AND B.CUST_PROFILE_ID = '^$^cust_profile_id'
            ) AND NOTA_ID = ^$^nota_id
        )
      )
    ";
    $countPBM = DB::connection('mdm')->table('TM_PBM_INTERNAL')->where('PBM_ID',$input['pbm_id'])->where('BRANCH_ID',$input['branch_id'])->where('BRANCH_CODE',$input['branch_code'])->count();
    if ($countPBM == 0) {
      $sql = "SELECT * FROM (".$sql.") WHERE COMP_NOTA_NAME <> 'STEVEDORING'";
    }


    $searchVal = array("^$^branch_id", "^$^branch_code", "^$^nota_id", "^$^cust_profile_id");
    $replaceVal = array($input['branch_id'], $input['branch_code'], $input['nota_id'], $input['cust_profile_id']);
    $sql = str_replace($searchVal, $replaceVal, $sql);
    $result = DB::connection('eng')->select(DB::raw($sql));
    return ["result" => $result, "count" => count($result), "query" => $sql];
  }

  public static function getViewNotaPLB($input) {
    $childs = DB::connection('mdm')->table('TM_NOTA')->where('no_parent_id', $input['nota_id'])->where('service_code', 2)->orderBy('nota_id', 'asc')->get();
    if (count($childs) == 0) {
      return ['Success' => false, 'response' => 'Fail, this nota not have childs', "json" => ""];
    }
    $estjs = [];
    // not use
      // foreach ($childs as $list) {
      //   $setData = [
      //     "branch_id" => $input['branch_id'],
      //     "branch_code" => $input['branch_code'],
      //     "nota_id" => $list->nota_id
      //   ];
      //   $cekAgain = DB::connection('mdm')->table('TS_NOTA')->where($setData)->count();
      //   if($cekAgain == 0){
      //     $checked = false;
      //   }else{
      //     $checked = true;
      //   }
      //   $add = [
      //     "menuId" => $list->nota_id,
      //     "text" => $list->nota_name,
      //     "iconCls" => "",
      //     "checked" => $checked,
      //     "leaf" => true
      //   ];
      //   $estjs[] = $add;
      // }

      // return [
      //   'Success' => true,
      //   'response' => 'Success, get data',
      //   /*'branch_id' => $input['branch_id'],
      //   'branch_code' => $input['branch_code'],*/
      //   "json" => [
      //     "expanded" => true,
      //     "text" => "Nota",
      //     "iconCls" => "",
      //     "children" => $estjs
      //   ]
      // ];
    // not use

    foreach ($childs as $list) {
      $setData = [
        "branch_id" => $input['branch_id'],
        "branch_code" => $input['branch_code'],
        "nota_id" => $list->nota_id
      ];
      $cekAgain = DB::connection('mdm')->table('TS_NOTA')->where($setData)->count();
      if($cekAgain == 0){
        $checked = false;
      }else{
        $checked = true;
      }
      $add = [
        "boxLabel" => $list->nota_name,
        "inputValue" => $list->nota_id,
        "checked" => $checked
      ];
      $estjs[] = $add;
    }

    return [
      'Success' => true,
      'response' => 'Success, get data',
      /*'branch_id' => $input['branch_id'],
      'branch_code' => $input['branch_code'],*/
      "json" => [
        "xtype" => "radiogroup",
        "name" => "nota",
        "fieldLabel" => "Nota",
        "columns" => 1,
        "items" => $estjs
      ]
    ];
  }

  public static function getViewNotaPLB_old($input) {
    $parent = DB::connection('mdm')->table('TM_NOTA')->whereNull('no_parent_id')->where('service_code', 2)->orderBy('nota_id', 'asc')->get();

    $estjs = [];
    foreach ($parent as $list) {
      $childs = DB::connection('mdm')->table('TM_NOTA')->where('no_parent_id', $list->nota_id)->where('service_code', 2)->orderBy('nota_id', 'asc')->get();
      if (count($childs) > 0) {
        $addAg = [];
        foreach ($childs as $listSc) {
          $addAgAg = [];
          $add_th_k = "leaf";
          $add_th_v = true;
          $setData = [
            "branch_id" => $input['branch_id'],
            "branch_code" => $input['branch_code'],
            "nota_id" => $listSc->nota_id
          ];
          $cekAgain = DB::connection('mdm')->table('TS_NOTA')->where($setData)->count();
          if($cekAgain == 0){
            $checked = false;
          }else{
            $checked = true;
          }
          $newSet = [
            "menuId" => $listSc->nota_id,
            "text" => $listSc->nota_name,
            "iconCls" => "",
            "checked" => $checked
          ];
          $newSet[$add_th_k] = $add_th_v;

          if (count($addAgAg) > 0) {
            $newSet['children'] = $addAgAg;
          }
          $addAg[] = $newSet;
        }
        $add_sc_k = "expanded";
        $add_sc_v =  true;
      }else{
        $addAg = [];
        $add_sc_k = "leaf";
        $add_sc_v =  true;
      }

      $setData = [
        "branch_id" => $input['branch_id'],
        "branch_code" => $input['branch_code'],
        "nota_id" => $list->nota_id
      ];
      $cekAgain = DB::connection('mdm')->table('TS_NOTA')->where($setData)->count();
      if($cekAgain == 0){
        $checked = false;
      }else{
        $checked = true;
      }

      $add = [
        "menuId" => $list->nota_id,
        "text" => $list->nota_name,
        "iconCls" => "",
        "checked" => $checked
      ];

      $add[$add_sc_k] = $add_sc_v;

      if (count($addAg) > 0) {
        $add['children'] = $addAg;
      }

      $estjs[] = $add;
    }

    return [
      'Success' => true,
      'response' => 'Success, get data',
      "json" => [
        "expanded" => true,
        "text" => "Nota",
        "iconCls" => "",
        "children" => $estjs
      ]
    ];
  }

  public static function viewCancelCargo($input) {
    $type        = $input["type"];
    $method      = $input["method"];
    $cancel_type = $input["cancelled_type"];
    $cancel      = DB::connection("omuster")
                  ->table("TX_HDR_CANCELLED A")
                  ->join("TX_HDR_".strtoupper($type)."_CARGO B", "B.".strtoupper($type)."_CARGO_NO", "=", "A.CANCELLED_REQ_NO")
                  ->where("A.CANCELLED_TYPE",$cancel_type)
                  ->where("A.CANCELLED_ID", $input["cancelled_id"])
                  ->get();

    $newDt["header"] = $cancel;

    foreach ($cancel as $value) {
      if ($method == "view") {
        if ($type == "rec") {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_CANCELLED A")
          ->leftJoin("TX_DTL_".strtoupper($type)."_CARGO B", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->rec_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->select(DB::raw('(SELECT SUM(C.CANCL_QTY) FROM TX_DTL_CANCELLED C WHERE C.CANCL_SI = A.CANCL_SI) AS jumlah_batal, A.*, B.*'))
          ->get();
        } else {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_CANCELLED A")
          ->leftJoin("TX_DTL_".strtoupper($type)."_CARGO B", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->del_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->select(DB::raw('(SELECT SUM(C.CANCL_QTY) FROM TX_DTL_CANCELLED C WHERE C.CANCL_SI = A.CANCL_SI) AS jumlah_batal, A.*, B.*'))
          ->get();
        }
      } else {
        if ($type == "rec") {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_".strtoupper($type)."_CARGO B")
          ->leftJoin("TX_DTL_CANCELLED A", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->rec_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->select(DB::raw('(SELECT SUM(C.CANCL_QTY) FROM TX_DTL_CANCELLED C WHERE C.CANCL_SI = A.CANCL_SI) AS jumlah_batal, A.*, B.*'))
          ->get();
        } else {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_".strtoupper($type)."_CARGO B")
          ->leftJoin("TX_DTL_CANCELLED A", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->del_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->select(DB::raw('(SELECT SUM(C.CANCL_QTY) FROM TX_DTL_CANCELLED C WHERE C.CANCL_SI = A.CANCL_SI) AS jumlah_batal, A.*, B.*'))
          ->get();
        }
      }
      $newDt["detail"][]  = $detail ;
    }

    if ($type == "rec") {
      $newDt["file"]    = DB::connection("omuster")
                        ->table("TX_DOCUMENT")
                        ->where("REQ_NO", $cancel[0]->rec_cargo_no)
                        ->get();
    } else {
      $newDt["file"]    = DB::connection("omuster")
                        ->table("TX_DOCUMENT")
                        ->where("REQ_NO", $cancel[0]->del_cargo_no)
                        ->get();
    }

    return $newDt;
  }

}
?>
