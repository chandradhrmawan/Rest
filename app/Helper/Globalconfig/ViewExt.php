<?php

namespace App\Helper\Globalconfig;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Firebase\JWT\JWT;
use App\Helper\Globalconfig\FileUpload;

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

    $newDt["HEADER"] = $cancel;

    foreach ($cancel as $value) {
      if ($method == "view") {
        if ($type == "rec") {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_CANCELLED A")
          ->leftJoin("TX_DTL_".strtoupper($type)."_CARGO B", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->rec_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->get();
        } else {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_CANCELLED A")
          ->leftJoin("TX_DTL_".strtoupper($type)."_CARGO B", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->del_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->get();
        }
      } else {
        if ($type == "rec") {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_".strtoupper($type)."_CARGO B")
          ->leftJoin("TX_DTL_CANCELLED A", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->rec_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->get();
        } else {
          $detail  = DB::connection("omuster")
          ->table("TX_DTL_".strtoupper($type)."_CARGO B")
          ->leftJoin("TX_DTL_CANCELLED A", "B.".strtoupper($type)."_CARGO_DTL_SI_NO", "=", "A.CANCL_SI")
          ->where("B.".strtoupper($type)."_CARGO_HDR_ID", $value->del_cargo_id)
          ->where("A.CANCL_HDR_ID ", $input["cancelled_id"])
          ->get();
        }
      }
      $newDt["DETAIL"]  = $detail ;
    }

    if ($type == "rec") {
      $newDt["FILE"]    = DB::connection("omuster")
                        ->table("TX_DOCUMENT")
                        ->where("REQ_NO", $cancel[0]->rec_cargo_no)
                        ->get();
    } else {
      $newDt["FILE"]    = DB::connection("omuster")
                        ->table("TX_DOCUMENT")
                        ->where("REQ_NO", $cancel[0]->del_cargo_no)
                        ->get();
    }

    return $newDt;
  }

  public static function getNota($input) {
    $selectraw = "
                  A.COMP_NOTA_ID,
                  A.NOTA_ID,
                  B.NOTA_NAME,
                  A.BRANCH_ID,
                  A.BRANCH_CODE,
                  C.BRANCH_NAME,
                  A.GROUP_TARIFF_ID,
                  A.COMP_NOTA_NAME GROUP_TARIFF_NAME,
                  A.COMP_NOTA_ORDER,
                  A.COMP_NOTA_VIA,
                  D.REFF_NAME COMP_NOTA_VIA_NAME,
                  A.COMP_NOTA_TL,
                  E.REFF_NAME COMP_NOTA_TL_NAME,
                  A.COMP_NOTA_VIEW,
                  A.COMP_FORM_ORDER,
                  A.COMP_REQUIRED,
                  A.PROC_NAME,
                  A.COMP_FORM_SHOW,
                  F.REFF_NAME COMP_NOTA_VIEW_NAME
                 ";

    $grupbyraw = "
                  A.COMP_NOTA_ID,
                  A.NOTA_ID,
                  B.NOTA_NAME,
                  A.BRANCH_ID,
                  A.BRANCH_CODE,
                  C.BRANCH_NAME,
                  A.GROUP_TARIFF_ID,
                  A.COMP_NOTA_NAME,
                  A.COMP_NOTA_ORDER,
                  A.COMP_NOTA_VIA,
                  D.REFF_NAME,
                  A.COMP_NOTA_TL,
                  E.REFF_NAME,
                  A.COMP_NOTA_VIEW,
                  A.COMP_FORM_ORDER,
                  A.COMP_REQUIRED,
                  A.PROC_NAME,
                  A.COMP_FORM_SHOW,
                  A.COMP_NOTA_VIEW,
                  F.REFF_NAME
                  ";

    $tableraw  = "
                  TM_COMP_NOTA A
                  LEFT JOIN
                  TM_REFF D ON A.COMP_NOTA_VIA = D.REFF_ID AND D.REFF_TR_ID = '4'
                  LEFT JOIN
                  TM_REFF E ON A.COMP_NOTA_TL = E.REFF_ID AND E.REFF_TR_ID = '9'
                  LEFT JOIN
                  TM_REFF F ON A.COMP_NOTA_VIEW = F.REFF_ID AND F.REFF_TR_ID = '10'
                 ";

    $data = DB::connection('mdm')
                ->table(DB::raw($tableraw))
                ->selectraw($selectraw)
                ->join('TM_NOTA B', 'A.NOTA_ID', '=', 'B.NOTA_ID')
                ->join('TM_BRANCH C', 'A.BRANCH_CODE', '=', 'C.BRANCH_CODE')
                ->orderBy('A.COMP_NOTA_ORDER', "ASC")
                ->groupBy($grupbyraw);

    if(!empty($input["where"][0])) {
      $data->where($input["where"]);
    }

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $data->skip($input['start'])->take($input['limit']);
      }
    }

    $result   = $data->get();
    $count    = count($result);

    return ["result"=>$result, "count"=>$count];
  }

  public static function einvoiceLink($input) {
    $endpoint_url="http://10.88.48.57:5555/restv2/inquiryData/getDataCetak";
    $string_json = '{
                   "getDataCetakRequest":{
                      "esbHeader":{
                         "internalId":"",
                         "externalId":"EDI-2910201921570203666",
                         "timestamp":"2019-10-29 21:57:020.36665400",
                         "responseTimestamp":"",
                         "responseCode":"",
                         "responseMessage":""
                      },
                      "esbBody":{
                         "kode":"billingedii",
                         "tipe":"nota",
                         "nota":"'.$input["nota_no"].'"
                      }
                   }
                }';

    $username="billing";
    $password ="b1Llin9";
    $client = new Client();
    $options= array(
      'auth' => [
        $username,
        $password
      ],
      'headers'  => ['content-type' => 'application/json', 'Accept' => 'application/json'],
      'body' => $string_json,
      "debug" => false
    );
    try {
      $res = $client->post($endpoint_url, $options);
    } catch (ClientException $e) {
      return $e->getResponse();
    }
    $results  = json_decode($res->getBody()->getContents(), true);
    $qrcode   = $results['getDataCetakResponse']['esbBody']['url'];
    $qrcode = "https://eservice.indonesiaport.co.id/index.php/eservice/api/getdatacetak?kode=billingedii&tipe=nota&no=".$input["nota_no"]; //optional

    return ["link" => $qrcode];
  }

  function getDebitur($input, $request) {
    $startDate = date("Y-m-d", strtotime($input["startDate"]));
    $endDate = date("Y-m-d", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_DEBITUR');
    if (!empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input["condition"]["NOTA_BRANCH_ID"]);
    }else if (empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["NOTA_BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input["condition"]["NOTA_BRANCH_CODE"]);
    }else if (empty($input["condition"]["NOTA_BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input['user']->user_branch_code);
    }
    if (!empty($input["condition"]["NOTA_NO"])) {
      $getRpt->where('NOTA_NO',$input["condition"]["NOTA_NO"]);
    }
    if (!empty($input["condition"]["NOTA_CUST_NAME"])) {
      $getRpt->where('NOTA_CUST_NAME',$input["condition"]["NOTA_CUST_NAME"]);
    }
    if (!empty($input["condition"]["LAYANAN"])) {
      $getRpt->where('LAYANAN',$input["condition"]["LAYANAN"]);
    }
    if (!empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->whereBetween('TGL_NOTA',[$startDate,$endDate]);
    } else if (!empty($input["startDate"]) AND empty($input["endDate"])) {
      $getRpt->where('TGL_NOTA', '>', $startDate);
    } else if (empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->where('TGL_NOTA', '<', $endDate);
    }

    $count  = $getRpt->count();

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $getRpt->skip($input['start'])->take($input['limit']);
      }
    }

    if (isset($input["sort"])) {
      $sort = json_decode($input["sort"]);
      foreach ($sort as $sort) {
      $property = $sort->property;
      $direction = $sort->direction;
      $getRpt->orderby(strtoupper($property), $direction);
        }
      }
    $result = $getRpt->get();
    return ["result"=>$result, "count"=>$count];
  }

  public static function getRekonsilasi($input) {
    $startDate = date("Y-m-d h:i:s", strtotime($input["startDate"]));
    $endDate = date("Y-m-d h:i:s", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_REKONSILASI_NOTA');
    if (!empty($input["condition"]["BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input["condition"]["BRANCH_ID"]);
    }else if (empty($input["condition"]["BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input["condition"]["BRANCH_CODE"]);
    }else if (empty($input["condition"]["BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input['user']->user_branch_code);
    }
    if (!empty($input["condition"]["VESSEL"])) {
      $getRpt->where('VESSEL',$input["condition"]["VESSEL"]);
    }
    if (!empty($input["condition"]["UKK"])) {
      $getRpt->where('UKK',$input["condition"]["UKK"]);
    }
    if (!empty($input["condition"]["NOTA"])) {
      $getRpt->where('NOTA_NO',$input["condition"]["NOTA"]);
    }
    if (!empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->whereBetween('NOTA_DATE',[$startDate,$endDate]);
    } else if (!empty($input["startDate"]) AND empty($input["endDate"])) {
      $getRpt->where('NOTA_DATE', '>', $startDate);
    } else if (empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->where('NOTA_DATE', '<', $endDate);
    }

    $count  = $getRpt->count();

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $getRpt->skip($input['start'])->take($input['limit']);
      }
    }

    if (isset($input["sort"])) {
      $sort = json_decode($input["sort"]);
      foreach ($sort as $sort) {
      $property = $sort->property;
      $direction = $sort->direction;
      $getRpt->orderby(strtoupper($property), $direction);
        }
      }
    $result = $getRpt->get();

    return ["result"=>$result, "count"=>$count];
  }

  public static function getRptDtlPendapatan($input) {
    $startDate = $input["startYear"];
    $endDate = $input["endYear"];

    $getRpt = DB::connection('omcargo')->table('V_RPT_DTL_PENDAPATAN')->where('DT', 'D');
    if (!empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input["condition"]["REAL_BRANCH_ID"]);
    }else if (empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('REAL_BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["REAL_BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input["condition"]["REAL_BRANCH_CODE"]);
    }else if (empty($input["condition"]["REAL_BRANCH_CODE"])) {
      $getRpt->where('REAL_BRANCH_CODE',$input['user']->user_branch_code);
    }
    if (!empty($input["condition"]["KEMASAN"])) {
      $getRpt->where('KEMASAN',$input["condition"]["KEMASAN"]);
    }
    if (!empty($input["condition"]["KOMODITI"])) {
      $getRpt->where('KOMODITI',$input["condition"]["KOMODITI"]);
    }
    if (!empty($input["condition"]["SATUAN"])) {
      $getRpt->where('SATUAN',$input["condition"]["SATUAN"]);
    }
    if (!empty($input["startYear"]) AND !empty($input["endYear"])) {
      $getRpt->whereBetween('TAHUN',[$startDate,$endDate]);
    } else if (!empty($input["startYear"]) AND empty($input["endYear"])) {
      $getRpt->where('TAHUN', '>', $startDate);
    } else if (empty($input["startYear"]) AND !empty($input["endYear"])) {
      $getRpt->where('TAHUN', '<', $endDate);
    }

    $count  = $getRpt->count();

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $getRpt->skip($input['start'])->take($input['limit']);
      }
    }

    if (isset($input["sort"])) {
      $sort = json_decode($input["sort"]);
      foreach ($sort as $sort) {
      $property = $sort->property;
      $direction = $sort->direction;
      $getRpt->orderby(strtoupper($property), $direction);
        }
      }
    $result = $getRpt->get();

    return ["result"=>$result, "total"=>$count];
  }

  public static function getTrafikProduksi($input) {
    $startDate = date("Y-m-d", strtotime($input["startDate"]));
    $endDate = date("Y-m-d", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_TRAFIK_DAN_PROD');
    if (!empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input["condition"]["REAL_BRANCH_ID"]);
    }else if (empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["REAL_BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input["condition"]["REAL_BRANCH_CODE"]);
    }else if (empty($input["condition"]["REAL_BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input['user']->user_branch_code);
    }
    if (!empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->whereBetween('NOTA_DATE',[$startDate,$endDate]);
    } else if (!empty($input["startDate"]) AND empty($input["endDate"])) {
      $getRpt->where('NOTA_DATE', '>', $startDate);
    } else if (empty($input["startDate"]) AND !empty($input["endDate"])) {
      $getRpt->where('NOTA_DATE', '<', $endDate);
    }

    $count  = $getRpt->count();

    if (!empty($input['start']) || $input["start"] == '0') {
      if (!empty($input['limit'])) {
        $getRpt->skip($input['start'])->take($input['limit']);
      }
    }

    if (isset($input["sort"])) {
      $sort = json_decode($input["sort"]);
      foreach ($sort as $sort) {
      $property = $sort->property;
      $direction = $sort->direction;
      $getRpt->orderby(strtoupper($property), $direction);
        }
      }
    $result = $getRpt->get();

    return ["result"=>$result, "total"=>$count];
  }

  public function readExcelImport(Request $request)
  {
    $json_request = $request->json()->get('TCARequest');
    $encoded_string = $json_request['esbBody']['base64'];

    $target_dir = 'temp_truck/';
    if (!file_exists($target_dir)){
      mkdir($target_dir, 0777);
    }

    $decoded_file = base64_decode($encoded_string); // decode the file
    $mime_type = finfo_buffer(finfo_open(), $decoded_file, FILEINFO_MIME_TYPE); // extract mime type
    $extension = $this->mime2ext($mime_type); // extract extension from mime type
    $name = Carbon::now()->format('mdY_h_i_s');
    $file = $name.'.'.$extension; // rename file as a unique name
    $file_dir = $target_dir.$name.'.'.$extension;
    try {
      file_put_contents($file_dir, $decoded_file);
      $response = true;
    } catch (Exception $e) {
      $response = $e->getMessage();
    }

    $objPHPExcel = PHPExcel_IOFactory::load($file_dir);
    $sheet = $objPHPExcel->getSheet(0);
    $highestRow = $sheet->getHighestRow();
    // $highestColumn = $sheet->getHighestColumn();
    $responseData = [];
    for ($row = 2; $row <= $highestRow; $row++){
      // $rowData = $sheet->rangeToArray('A' . $row . ':' . $highestColumn . $row, NULL, TRUE, FALSE);
      $responseData[] = ["no_polisi" => $sheet->getCell('A'.$row)->getValue()];
    }

    unlink($file_dir);
    return response()->json([
      'TCAResponse' => [
        'esbHeader' => [
          'internalId' => '537750c2-3912-4cbb-a5ed-ed98f7d312f9',
          'responseTimestamp' => '20190717 09:54:02.173',
          'responseCode' => '0',
          'responseMessage' => 'Success'
        ],
        'esbBody' => [
          'results' => [
            'response' => $responseData,
            'status' => 'success',
            'message' => 'Successfully, Payment']
        ]
      ]
    ]);
  }

}
?>
