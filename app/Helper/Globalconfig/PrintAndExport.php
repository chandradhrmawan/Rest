<?php

namespace App\Helper\Globalconfig;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Dompdf\Dompdf;
use Firebase\JWT\JWT;
use App\Helper\Globalconfig\FileUpload;

class PrintAndExport{

  public static function printGetPass($id) {
    $data = DB::connection("omcargo")
              ->table("TX_HDR_TCA")
              ->join("TX_DTL_TCA","TX_DTL_TCA.TCA_HDR_ID","=","TX_HDR_TCA.TCA_ID")
              ->join("TM_REFF","TM_REFF.REFF_ID","=","TX_HDR_TCA.TCA_STATUS")
              ->where([
                ["TM_REFF.REFF_TR_ID", "=", "8"],
                ["TX_HDR_TCA.TCA_BRANCH_ID", "=", "12"],
                ["TX_DTL_TCA.DTL_ID", "=", $id]
                ])
              ->orderBy("TX_HDR_TCA.TCA_ID", "desc")
              ->get();
    $request = substr($data[0]->tca_req_no,0,3);

    if ($request == "DEL") {
      $title = "DELIVERY";
    } else if ($request == "REC") {
      $title = "RECEIVING";
    } else {
      $req     = DB::connection('omcargo')->table("TX_HDR_BM")->where('BM_NO', $data[0]->tca_req_no)->get();
      $hdrid   = $req[0]->bm_id;
      $req_det = DB::connection('omcargo')->table("TX_DTL_BM")->where('HDR_BM_ID', $hdrid)->get();
      $tl      = $req_det[0]->dtl_bm_tl;
      $type    = $req_det[0]->dtl_bm_type;
      if ($tl == "Y" && $type == "Bongkar") {
        $title = "DELIVERY";
      } else {
        $title = "RECEIVING";
      }
    }

    $html = view('print.getPass', ["data"=>$data, "title" => $title]);
    $filename = "Test";
    $dompdf = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printRDCardNPKS($branchCode, $notaId, $id) {
    $findConfig       =  [
      "NOTA_ID"       => $notaId,
      "BRANCH_CODE"   => $branchCode
    ];

    $tmNota           = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $notaId)->get();
    $notaData         = DB::connection('mdm')->table('TS_NOTA')->where($findConfig)->first();
    $config           = json_decode($notaData->api_set, true);
    $title            = $tmNota[0]->nota_name;

    $findRequest      = [
        $config["head_primery"] => $id
    ];

    $findDetail       = [
      $config["head_forigen"] => $id,
      $config["DTL_IS_CANCEL"] => 'N'
    ];


    $hdrRequest       = DB::connection('omuster')->table($config["head_table"])->where($findRequest)->first();
    $hdrRequest       = json_decode(json_encode($hdrRequest), TRUE);
    $dtlRequest       = DB::connection('omuster')->table($config["head_tab_detil"])->where($findDetail)->get();
    $dtlRequest       = json_decode(json_encode($dtlRequest), TRUE);

    $page             = count($dtlRequest);
    // return $notaId;

    $html             = view('print.rdCardNPKS', ["nota_id"=>$notaId, "title"=>$title, "page"=>$page, "header"=>$hdrRequest, "detail" => $dtlRequest, "config"=>$config]);
    $filename         = $hdrRequest[$config["head_primery"]];
    $dompdf           = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printUperPaidNPK($id) {
    $connect    = DB::connection("omcargo");
    $header     = $connect->table("TX_HDR_UPER")->where('UPER_NO', $id)->get();
    $branch     = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->uper_branch_id)->where('BRANCH_CODE', $header[0]->uper_branch_code)->get();
    $terbilang  = static::terbilang($header[0]->uper_amount);
    $query      = "
                  SELECT
                  A.UPER_CUST_NAME,
                  A.UPER_VESSEL_NAME,
                  CASE
                  WHEN A.UPER_NOTA_ID = 13
                    THEN (SELECT TO_CHAR(BM_ETA,'DD MONTH YY')|| ' / ' || TO_CHAR(BM_ETD,'DD MONTH YY') FROM TX_HDR_BM WHERE BM_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID = 14
                    THEN (SELECT TO_CHAR(REC_ETA,'DD MONTH YY')|| ' / ' || TO_CHAR(REC_ETD,'DD MONTH YY') FROM TX_HDR_REC WHERE REC_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID IN (15,19)
                    THEN (SELECT TO_CHAR(DEL_ETA,'DD MONTH YY')|| ' / ' || TO_CHAR(DEL_ETD,'DD MONTH YY') FROM TX_HDR_DEL WHERE DEL_NO = A.UPER_REQ_NO)
                  END AS PERIODE,
                  A.UPER_NO,
                  A.UPER_TRADE_TYPE,
                  A.UPER_AMOUNT,
                  B.PAY_AMOUNT,
                  B.PAY_ACCOUNT_NAME,
                  TO_CHAR(B.PAY_DATE,'DD MONTH YY') PAY_DATE,
                  B.PAY_NOTE,
                  B.PAY_CUST_ID
                FROM
                  TX_HDR_UPER A,
                  TX_PAYMENT B
                WHERE
                  A.UPER_NO = B.PAY_NO
                  AND B.PAY_TYPE = 1
                  AND A.UPER_PAID = 'Y'
                  AND A.UPER_NO = '$id'";
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
                         "tipe":"uper",
                         "nota":"'.$header[0]->uper_no.'"
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
    $results = json_decode($res->getBody()->getContents(), true);

    $qrcode = $results['getDataCetakResponse']['esbBody']['url'];
    $data       = DB::connection('omcargo')->select($query);
    $html       = view('print.uperPaid',["qrcode"=>$qrcode, "branch"=>$branch,"header"=>$header,"data"=>$data,"terbilang"=>$terbilang]);
    $filename   = $header[0]->uper_no.rand(10,100000);
    $dompdf     = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printProformaNPK($id) {
    $connect        = DB::connection('omcargo');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $data["header"] = $header;
    $detail  = $connect->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID', $id)->get();
    foreach ($detail as $list) {
      $newDt = [];
      foreach ($list as $key => $value) {
              $newDt[$key] = $value;
      }

      $componen  = DB::connection("mdm")
                    ->table("TM_COMP_NOTA")
                    ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID", "=",$header[0]->nota_group_id],["BRANCH_CODE", $header[0]->nota_branch_code]])
                    ->get();
      foreach ($componen as $listS) {
        foreach ($listS as $key => $value) {
                $newDt[$key] = $value;
        }
        $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
        }
      }

    }

    $all = ["header"=>$header]+$det;
    $branch      = DB::connection('mdm')->table("TM_BRANCH")->where([['BRANCH_ID', $header[0]->nota_branch_id], ["BRANCH_CODE", $header[0]->nota_branch_code]])->get();
    if (!array_key_exists("alat",$all)) {
      $alat = 0;
    } else {
      $alat = $all["alat"];
    }

    if (!array_key_exists("handling",$all)) {
      $handling   = 0;
      $bl = 0;
    } else {
      $hand = $all["handling"];
      $bl = [];
      for ($i=0; $i < count($hand); $i++) {
        if (!in_array($hand[$i]["dtl_bl"],$bl)) {
          $bl[] = $hand[$i]["dtl_bl"];
        }
      }
      for ($i=0; $i < count($bl); $i++) {
        $data       = DB::connection('omcargo')->table("V_TX_DTL_NOTA")
                    ->where([['NOTA_HDR_ID ', '=', $id],["dtl_bl", "=", $bl[$i]],["dtl_group_tariff_id", "!=", "10"]])
                    ->get();
        $handling[$bl[$i]] = json_decode(json_encode($data),TRUE);
      }
    }

    if (!array_key_exists("penumpukan",$all)) {
      $penumpukan   = 0;
    } else {
      $penumpukan = $all["penumpukan"];
    }

    $query = "
              SELECT
              A.*,
              CASE
              WHEN A.NOTA_GROUP_ID = 13
              THEN
                (SELECT Y.BM_KADE FROM TX_HDR_BM Y WHERE Y.BM_NO = A.NOTA_REQ_NO)
              WHEN A.NOTA_GROUP_ID = 14
              THEN
                (SELECT X.REC_KADE FROM TX_HDR_REC X WHERE X.REC_NO = A.NOTA_REQ_NO )
              WHEN A.NOTA_GROUP_ID = 15
              THEN
                (SELECT Z.DEL_KADE FROM TX_HDR_DEL Z WHERE Z.DEL_NO = A.NOTA_REQ_NO)
              END AS KADE,
              CASE
              WHEN A.NOTA_GROUP_ID = 13
              THEN
                (SELECT Y.BM_PBM_NAME FROM TX_HDR_BM Y WHERE Y.BM_NO = A.NOTA_REQ_NO)
              END AS PBM_NAME,
              CASE
                    WHEN A.NOTA_GROUP_ID = 13
                      THEN (SELECT TO_CHAR(BM_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MONTH-YYYY') FROM TX_HDR_BM WHERE BM_NO = A.NOTA_REQ_NO)
                    WHEN A.NOTA_GROUP_ID = 14
                      THEN (SELECT TO_CHAR(REC_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MONTH-YYYY') FROM TX_HDR_REC WHERE REC_NO = A.NOTA_REQ_NO)
                    WHEN A.NOTA_GROUP_ID IN (15,19)
                      THEN (SELECT TO_CHAR(DEL_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MONTH-YYYY') FROM TX_HDR_DEL WHERE DEL_NO = A.NOTA_REQ_NO)
                    END AS PERIODE
            FROM
              TX_HDR_NOTA A
            WHERE
              A.NOTA_ID = '$id'
            ";
    $kapal       = DB::connection('omcargo')->select($query);
    $nota        = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();

    // Data Uper And Payment
    $query      = "
                  SELECT
                  A.UPER_CUST_NAME,
                  A.UPER_VESSEL_NAME,
                  CASE
                  WHEN A.UPER_NOTA_ID = 13
                    THEN (SELECT TO_CHAR(BM_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MONTH-YYYY') FROM TX_HDR_BM WHERE BM_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID = 14
                    THEN (SELECT TO_CHAR(REC_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MONTH-YYYY') FROM TX_HDR_REC WHERE REC_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID IN (15,19)
                    THEN (SELECT TO_CHAR(DEL_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MONTH-YYYY') FROM TX_HDR_DEL WHERE DEL_NO = A.UPER_REQ_NO)
                  END AS PERIODE,
                  A.UPER_NO,
                  A.UPER_TRADE_TYPE,
                  A.UPER_AMOUNT,
                  B.PAY_AMOUNT,
                  B.PAY_ACCOUNT_NAME,
                  TO_CHAR(B.PAY_DATE,'DD-MONTH-YYYY') PAY_DATE,
                  B.PAY_NOTE,
                  B.PAY_CUST_ID
                FROM
                  TX_HDR_UPER A,
                  TX_PAYMENT B
                WHERE
                  A.UPER_NO = B.PAY_NO
                  AND B.PAY_TYPE = 1
                  AND A.UPER_PAID = 'Y'
                  AND A.UPER_REQ_NO = '".$header[0]->nota_req_no."'";
    $uper        = DB::connection('omcargo')->select($query);
    $notaAmount  = $header[0]->nota_amount;
    if (empty($uper)) {
      $payAmount   = 0;
      $uper        = 0;
    } else {
      $payAmount   = $uper[0]->pay_amount;
      $uper        = $uper[0]->pay_amount;
    }
    $total       = $notaAmount - $payAmount;
    $terbilang   = static::terbilang($total);
    if(empty($total)) {
      $terbilang  = "Nol";
    }

    $sign        = DB::connection('mdm')->table("TM_SIGNATURE")->where('SIGN_TYPE', "3")->where('SIGN_BRANCH_ID', $branch[0]->branch_id)->where('SIGN_BRANCH_CODE', $branch[0]->branch_code)->get();
    $html        = view('print.proforma2',["sign"=>$sign, "total"=>$total,"uper"=>$uper, "bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan,"label"=>$nota, "handling"=>$handling, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
    $filename    = $all["header"][0]->nota_no.rand(10,100000);
    $dompdf      = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printUperNPK($id) {
    $connect = DB::connection("omcargo");
    $det     = [];
    $header  = $connect->table("TX_HDR_UPER")->where('UPER_ID', $id)->get();
    $data["header"] = $header;
    $detail  = $connect->table("V_TX_DTL_UPER")->where('UPER_HDR_ID', $header[0]->uper_id)->get();
    foreach ($detail as $list) {
      $newDt = [];
      foreach ($list as $key => $value) {
              $newDt[$key] = $value;
      }

      $componen  = DB::connection("mdm")
                    ->table("TM_COMP_NOTA")
                    ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID",'=', $header[0]->uper_nota_id],["BRANCH_CODE", "=",$header[0]->uper_branch_code]])
                    ->get();
      foreach ($componen as $listS) {
        foreach ($listS as $key => $value) {
                $newDt[$key] = $value;
        }
        $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
        }
      }
    }

    $all = ["header"=>$header]+$det;
    $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->uper_branch_id)->where('BRANCH_CODE', $header[0]->uper_branch_code)->get();
    $terbilang   = static::terbilang($header[0]->uper_amount);
    if (!array_key_exists("alat",$all)) {
      $alat = 0;
    } else {
      $alat = $all["alat"];
    }

    if (!array_key_exists("handling",$all)) {
      $handling   = 0;
      $bl = 0;
    } else {
      $hand = $all["handling"];
      $bl = [];
      for ($i=0; $i < count($hand); $i++) {
        if (!in_array($hand[$i]["dtl_bl"],$bl)) {
          $bl[] = $hand[$i]["dtl_bl"];
        }
      }
      for ($i=0; $i < count($bl); $i++) {
        $data       = DB::connection('omcargo')->table("V_TX_DTL_UPER")
                    ->where([['UPER_HDR_ID ', '=', $id],["dtl_bl", "=", $bl[$i]],["dtl_group_tariff_id", "!=", "10"]])
                    ->get();
        $handling[$bl[$i]] = json_decode(json_encode($data),TRUE);
      }
    }

    if (!array_key_exists("penumpukan",$all)) {
      $penumpukan   = 0;
    } else {
      $penumpukan = $all["penumpukan"];
    }

    $nota       = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->uper_nota_id)->get();
    $html       = view('print.uper2',["bl"=>$bl,"branch"=>$branch,"label"=>$nota,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat,"terbilang"=>$terbilang]);

    $filename   = $all["header"][0]->uper_no.rand(10,100000);
    $dompdf     = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printBprpNPK($id) {
    $all               = [];
    $header            = DB::connection('omcargo')->table('TX_HDR_BPRP')->where("BPRP_NO","=",$id)->get();
    $detail            = DB::connection('omcargo')->table('TX_DTL_BPRP')->where("HDR_BPRP_ID","=",$header[0]->bprp_id)->get();
    $all["header"]     = $header;
    $all["detail"]     = $detail;
    $querya            = "
                          SELECT
                          B.*
                          FROM
                          TX_HDR_BPRP A
                          LEFT JOIN TX_HDR_REC B ON A.BPRP_REQ_NO = B.REC_NO
                          LEFT JOIN TX_HDR_DEL C ON A.BPRP_REQ_NO = C.DEL_NO
                          WHERE
                          A.BPRP_NO = '$id'
                         ";
    $queryb            = "
                         SELECT
                         C.*
                         FROM
                         TX_HDR_BPRP A
                         LEFT JOIN TX_HDR_REC B ON A.BPRP_REQ_NO = B.REC_NO
                         LEFT JOIN TX_HDR_DEL C ON A.BPRP_REQ_NO = C.DEL_NO
                         WHERE
                         A.BPRP_NO = '$id'
                        ";
    $a                 = DB::connection('omcargo')->select($querya);
    $b                 = DB::connection('omcargo')->select($queryb);
    if (!empty($a[0]->rec_id)) {
      $data            = json_encode($a);
      $change          = str_replace("rec", "req", $data);
      $a               = json_decode($change);
      $all["request"]  = $a;
      $branch          = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $a[0]->req_branch_id)->where('BRANCH_CODE', $a[0]->req_branch_code)->get();
      $sign            = DB::connection('mdm')->table("TM_SIGNATURE")->where('SIGN_TYPE', "1")->where('SIGN_BRANCH_ID', $a[0]->req_branch_id)->where('SIGN_BRANCH_CODE', $a[0]->req_branch_code)->get();
      $html            = view('print.bprp',["sign"=>$sign, "branch"=>$branch,"header"=>$all["header"],"detail"=>$all["detail"], "request"=>$all["request"]]);

    } else if (!empty($b[0]->del_id)) {
      $data            = json_encode($b);
      $change          = str_replace("del", "req", $data);
      $b               = json_decode($change);
      $all["request"]  = $b;
      $branch          = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $b[0]->req_branch_id)->where('BRANCH_CODE', $b[0]->req_branch_code)->get();
      $sign            = DB::connection('mdm')->table("TM_SIGNATURE")->where('SIGN_TYPE', "1")->where('SIGN_BRANCH_ID', $b[0]->req_branch_id)->where('SIGN_BRANCH_CODE', $b[0]->req_branch_code)->get();
      $html            = view('print.bprp',["sign"=>$sign, "branch"=>$branch,"header"=>$all["header"],"detail"=>$all["detail"], "request"=>$all["request"]]);

    }

    $filename   = $all["header"][0]->bprp_id.rand(10,100000);
    $dompdf     = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printRealisasiNPK($id) {
    $connect        = DB::connection('omcargo');
    $det            = [];
    $header         = $connect->table("TX_HDR_REALISASI")->where("REAL_ID", "=", $id)->join("TX_HDR_BM","TX_HDR_REALISASI.REAL_REQ_NO","=","TX_HDR_BM.BM_NO")->get();
    $data["header"] = $header;
    $nota           = $connect->table("TX_HDR_NOTA")->where('NOTA_REAL_NO', $header[0]->real_no)->get();
    $detail         = $connect->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID', $nota[0]->nota_id)->get();
    foreach ($detail as $list) {
      $newDt = [];
      foreach ($list as $key => $value) {
              $newDt[$key] = $value;
      }

        if (!empty($newDt["dtl_bl"])) {
          $det["handling"][]=$newDt;
        } else {
          $det["alat"][]=$newDt;
        }
    }

    if (!array_key_exists("alat",$det)) {
      $det["alat"]=0;
    }

    if (!array_key_exists("handling",$det)) {
      $handling   = 0;
      $bl = 0;
    } else {
      $hand = $det["handling"];
      $bl = [];
      for ($i=0; $i < count($hand); $i++) {
        if (!in_array($hand[$i]["dtl_bl"],$bl)) {
          $bl[] = $hand[$i]["dtl_bl"];
        }
      }
      for ($i=0; $i < count($bl); $i++) {
        $data       = DB::connection('omcargo')->table("V_TX_DTL_NOTA")
                    ->where([['NOTA_HDR_ID ', '=', $nota[0]->nota_id],["dtl_bl", "=", $bl[$i]],["dtl_group_tariff_id", "!=", "10"]])
                    ->get();
        $handling[$bl[$i]] = json_decode(json_encode($data),TRUE);
      }
    }
    $data["detail"] = $det;
    $branch         = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->bm_branch_id)->where('BRANCH_CODE', $header[0]->bm_branch_code)->get();
    $html           =  view("print.realisasi",["header"=>$header, "bl"=>$bl, "handling"=>$handling, "alat"=>$det["alat"],"branch"=>$branch]);
    $filename       = $header[0]->real_no."_".rand(10,100000);
    $dompdf         = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printInvoiceNPK($id) {
    $connect        = DB::connection('omcargo');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $data["header"] = $header;
    $detail  = $connect->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID', $id)->get();
    foreach ($detail as $list) {
      $newDt = [];
      foreach ($list as $key => $value) {
              $newDt[$key] = $value;
      }

      $componen  = DB::connection("mdm")
                    ->table("TM_COMP_NOTA")
                    ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID", "=",$header[0]->nota_group_id],["BRANCH_CODE", "=",$header[0]->nota_branch_code]])
                    ->get();

      foreach ($componen as $listS) {
        foreach ($listS as $key => $value) {
                $newDt[$key] = $value;
        }
        $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
        }
      }
    }


    $all = ["header"=>$header]+$det;
    $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->nota_branch_id)->where('BRANCH_CODE', $header[0]->nota_branch_code)->get();
    $terbilang   = static::terbilang($header[0]->nota_amount);
    if (!array_key_exists("alat",$all)) {
      $alat = 0;
    } else {
      $alat = $all["alat"];
    }

    if (!array_key_exists("handling",$all)) {
      $handling   = 0;
      $bl = 0;
    } else {
      $hand = $all["handling"];
      $bl = [];
      for ($i=0; $i < count($hand); $i++) {
        if (!in_array($hand[$i]["dtl_bl"],$bl)) {
          $bl[] = $hand[$i]["dtl_bl"];
        }
      }
      for ($i=0; $i < count($bl); $i++) {
        $data       = DB::connection('omcargo')->table("TX_DTL_NOTA")
                    ->where([['NOTA_HDR_ID ', '=', $id],["dtl_bl", "=", $bl[$i]],["dtl_group_tariff_id", "!=", "10"]])
                    ->get();
        $handling[$bl[$i]] = json_decode(json_encode($data),TRUE);
      }
    }

    if (!array_key_exists("penumpukan",$all)) {
      $penumpukan   = 0;
    } else {
      $penumpukan = $all["penumpukan"];
    }
    $query = "
              SELECT
              A.*,
              CASE
              WHEN A.NOTA_GROUP_ID = 13
              THEN
                (SELECT Y.BM_KADE FROM TX_HDR_BM Y WHERE Y.BM_NO = A.NOTA_REQ_NO)
              WHEN A.NOTA_GROUP_ID = 14
              THEN
                (SELECT X.REC_KADE FROM TX_HDR_REC X WHERE X.REC_NO = A.NOTA_REQ_NO )
              WHEN A.NOTA_GROUP_ID = 15
              THEN
                (SELECT Z.DEL_KADE FROM TX_HDR_DEL Z WHERE Z.DEL_NO = A.NOTA_REQ_NO)
              END AS KADE,
              CASE
                    WHEN A.NOTA_GROUP_ID = 13
                      THEN (SELECT TO_CHAR(BM_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MONTH-YYYY') FROM TX_HDR_BM WHERE BM_NO = A.NOTA_REQ_NO)
                    WHEN A.NOTA_GROUP_ID = 14
                      THEN (SELECT TO_CHAR(REC_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MONTH-YYYY') FROM TX_HDR_REC WHERE REC_NO = A.NOTA_REQ_NO)
                    WHEN A.NOTA_GROUP_ID IN (15,19)
                      THEN (SELECT TO_CHAR(DEL_ETA,'DD-MONTH-YYYY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MONTH-YYYY') FROM TX_HDR_DEL WHERE DEL_NO = A.NOTA_REQ_NO)
                    END AS PERIODE
            FROM
              TX_HDR_NOTA A
            WHERE
              A.NOTA_ID = '$id'
            ";

    $endpoint_url="http://10.88.56.40:5556/restv2/inquiryData/getDataCetak";
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
                         "nota":"'.$all["header"][0]->nota_no.'"
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
    $qrcode   = $results['getDataCetakResponse']['esbBody']['url']; //dari esb
    $qrcode = "https://eservice.indonesiaport.co.id/index.php/eservice/api/getdatacetak?kode=billingedii&tipe=nota&no=".$header[0]->nota_no; //optional
    $kapal    = DB::connection('omcargo')->select($query);
    $nota     = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();
    $handa     = $connect->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID','=', $id)->get();
    foreach ($handa as $list) {
      $newAt = [];
      foreach ($list as $key => $value) {
              $newAt[$key] = $value;
      }

      $componena  = DB::connection("mdm")
                    ->table("TM_COMP_NOTA")
                    ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID", "=",$header[0]->nota_group_id],["BRANCH_CODE", "=",$header[0]->nota_branch_code]])
                    ->get();
      foreach ($componena as $listS) {
        foreach ($listS as $key => $value) {
                $newAt[$key] = $value;
        }
        $dat[]=$newAt;
        if ($newAt["comp_nota_view"] == "1") {
          $dat["penumpukan"][]=$newAt;
        } if ($newAt["comp_nota_view"] == "2" || $newAt["comp_nota_view"] == "4") {
          $dat["handling"][]=$newAt;
        }  if ($newAt["comp_nota_view"] == "3") {
          $dat["alat"][]=$newAt;
        }
      }
    }

    // return $results;
    $sign        = DB::connection('mdm')->table("TM_SIGNATURE")->where('SIGN_TYPE', "4")->where('SIGN_BRANCH_ID', $branch[0]->branch_id)->where('SIGN_BRANCH_CODE', $branch[0]->branch_code)->get();
    if (!empty($dat["handling"])) {
      $handlingbm  = $dat["handling"];
      $html       = view('print.invoice',["sign"=>$sign,"label"=>$nota,"qrcode"=>$qrcode,"bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handlingbm, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
    } else {
      $html       = view('print.invoice',["sign"=>$sign,"label"=>$nota,"qrcode"=>$qrcode,"bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
    }
    $filename   = $header[0]->nota_no;
    $dompdf     = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printInvoiceNPKS($id) {

    $connect        = DB::connection('omuster');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $detail         = DB::connection('eng')->table("V_TX_TEMP_TARIFF_DTL_NPKS")->where('BOOKING_NUMBER', $header[0]->nota_req_no)->where("GROUP_TARIFF_ID", "!=", "10")->get();
    $nota           = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $header[0]->nota_group_id)->get();
    $branch         = DB::connection('mdm')->table("TM_BRANCH")->where([['BRANCH_ID', $header[0]->nota_branch_id], ["BRANCH_CODE", $header[0]->nota_branch_code]])->get();
    $penumpukan     = DB::connection('eng')->table("V_TX_TEMP_TARIFF_DTL_NPKS")->where('BOOKING_NUMBER', $header[0]->nota_req_no)->where("GROUP_TARIFF_ID", "=", "10")->get();



    // Data Uper And Payment
    $payment     = DB::connection('omuster')->table("TX_PAYMENT")->where('PAY_REQ_NO', $header[0]->nota_req_no)->first();
    if (!empty($payment)) {
      $uper      = $payment->pay_amount;
    } else {
      $uper      = 0;
    }
    $notaAmount  = ceil($header[0]->nota_amount);
    $payAmount   = $uper;
    $total       = $notaAmount - $payAmount;
    $terbilang   = static::terbilang($notaAmount);
    // if($terbilang == 0) {
    //   $terbilang  = "Nol";
    // }
    if (empty($penumpukan)) {
      $penumpukan = 0;
    }

    $html        = view('print.notaNpks',
                        [
                          "total"     => $total,
                          "uper"      => $uper,
                          "bayar"     => $payAmount,
                          "branch"    => $branch,
                          "header"    => $header,
                          "label"     => $nota,
                          "detail"    => $detail,
                          "penumpukan"=> $penumpukan,
                          "terbilang" => $terbilang,
                          "qrcode"    => "0"
                        ]);

    $filename    = $header[0]->nota_no.rand(10,100000);
    $dompdf      = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function printProformaNPKS($id) {
    $connect        = DB::connection('omuster');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $detail         = DB::connection('eng')->table("V_TX_TEMP_TARIFF_DTL_NPKS")->where('BOOKING_NUMBER', $header[0]->nota_req_no)->where("GROUP_TARIFF_ID", "!=", "10")->get();
    $nota           = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $header[0]->nota_group_id)->get();
    $branch         = DB::connection('mdm')->table("TM_BRANCH")->where([['BRANCH_ID', $header[0]->nota_branch_id], ["BRANCH_CODE", $header[0]->nota_branch_code]])->get();
    $penumpukan     = DB::connection('eng')->table("V_TX_TEMP_TARIFF_DTL_NPKS")->where('BOOKING_NUMBER', $header[0]->nota_req_no)->where("GROUP_TARIFF_ID", "=", "10")->get();

    // Data Uper And Payment
    $uper        = 0;
    $notaAmount  = $header[0]->nota_amount;
    $payAmount   = 0;
    $total       = $notaAmount - $payAmount;
    $terbilang   = static::terbilang($total);

    if (empty($penumpukan)) {
      $penumpukan = 0;
    }

    // return $detail;

    $html        = view('print.proformaNpks',
                        [
                          "total"     =>$total,
                          "uper"      =>$uper,
                          "branch"    =>$branch,
                          "header"    =>$header,
                          "penumpukan"=>$penumpukan,
                          "detail"    =>$detail,
                          "label"     =>$nota,
                          "terbilang" =>$terbilang
                        ]);

    $filename    = $header[0]->nota_no.rand(10,100000);
    $dompdf      = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  public static function ExportDebiturNPK($a,$b,$c,$d,$e,$f,$g) {
    $branchId    = $a;
    $notaNo      = $b;
    $custName    = $c;
    $layanan     = $d;
    $start       = $e;
    $end         = $f;
    $branchCode  = $g;

    $startDate   = date("Y-m-d", strtotime($start));
    $endDate     = date("Y-m-d", strtotime($end));

    $getRpt = DB::connection('omcargo')->table('V_RPT_DEBITUR');
    if (!empty($branchId)) {
      $getRpt->where('NOTA_BRANCH_ID',$branchId);
    }
    if (!empty($branchCode)) {
      $getRpt->where('BRANCH_CODE',$branchCode);
    }
    if (!empty($notaNo)) {
      $getRpt->where('NOTA_NO',$notaNo);
    }
    if (!empty($custName)) {
      $getRpt->where('NOTA_CUST_NAME',$custName);
    }
    if (!empty($layanan)) {
      $getRpt->where('LAYANAN',$layanan);
    }
    if (!empty($start) AND !empty($end)) {
      $getRpt->whereBetween('TGL_NOTA',[$startDate,$endDate]);
    } else if (!empty($start) AND empty($end)) {
      $getRpt->where('TGL_NOTA', '>', $startDate);
    } else if (empty($start) AND !empty($end)) {
      $getRpt->where('TGL_NOTA', '<', $endDate);
    }

    $count  = $getRpt->count();
    $result = $getRpt->get();
    return view('print.debitur',[
                "result"=>$result,
                "start"=>$start,
                "end"=>$end
              ]);
  }

  public static function ExportRekonsilasiNPK($a,$b,$c,$d,$e,$f,$g) {
    $branchId    = $a;
    $vessel      = $b;
    $ukk         = $c;
    $nota        = $d;
    $start       = str_replace("%20"," ",$e);
    $end         = str_replace("%20"," ",$f);
    $branchCode  = $g;

    $startDate = date("Y-m-d h:i:s", strtotime($start));
    $endDate = date("Y-m-d h:i:s", strtotime($end));

    $getRpt = DB::connection('omcargo')->table('V_RPT_REKONSILASI_NOTA');
    if (!empty($branchId)) {
      $getRpt->where('BRANCH_ID',$branchId);
    }
    if (!empty($branchCode)) {
      $getRpt->where('BRANCH_CODE',$branchCode);
    }
    if (!empty($vessel)) {
      $getRpt->where('VESSEL',$vessel);
    }
    if (!empty($ukk)) {
      $getRpt->where('UKK',$ukk);
    }
    if (!empty($nota)) {
      $getRpt->where('NOTA_NO',$nota);
    }

    if (!empty($start) AND !empty($end)) {
      $getRpt->whereBetween('NOTA_DATE',[$startDate,$endDate]);
    } else if (!empty($start) AND empty($end)) {
      $getRpt->where('NOTA_DATE', '>', $startDate);
    } else if (empty($start) AND !empty($end)) {
      $getRpt->where('NOTA_DATE', '<', $endDate);
    }

    $result = $getRpt->get();

    return view('print.rekonsilasi',[
                "result"=>$result,
                "start"=>$start,
                "end"=>$end
              ]);
  }

  public static function ExportPendapatanNPK($a,$b,$c,$d,$e,$f,$g) {
    $branchId    = $a;
    $kemasan     = $b;
    $komoditi    = $c;
    $satuan      = $d;
    $start       = $e;
    $end         = $f;
    $branchCode  = $g;

    $getRpt = DB::connection('omcargo')->table('V_RPT_DTL_PENDAPATAN')->where('DT', 'D');
    if (!empty($branchId)) {
      $getRpt->where('BRANCH_ID',$branchId);
    }
    if (!empty($branchCode)) {
      $getRpt->where('BRANCH_CODE',$branchCode);
    }
    if (!empty($kemasan)) {
      $getRpt->where('DTL_PACKAGE',$kemasan);
    }
    if (!empty($komoditi)) {
      $getRpt->where('DTL_COMMODITY',$komoditi);
    }
    if (!empty($satuan)) {
      $getRpt->where('DTL_UNIT_NAME',$satuan);
    }
    if (!empty($start) AND !empty($end)) {
      $getRpt->whereBetween('TAHUN',[$start,$end]);
    } else if (!empty($start) AND empty($end)) {
      $getRpt->where('TAHUN', '>', $start);
    } else if (empty($start) AND !empty($end)) {
      $getRpt->where('TAHUN', '<', $end);
    }

    $raw    = $getRpt;
    $result = $getRpt->get();

    $kemasan = [];
    for ($i=0; $i < count($result); $i++) {
      if (!in_array($result[$i]->dtl_package,$kemasan)) {
        $kemasan[] = $result[$i]->dtl_package;
      }
    }

    $newDt = [];
    foreach ($result as $key => $value) {
      $newDt[$value->dtl_package][] = $value;
    }

    $data = $newDt;

    return view('print.pendapatan',[
      "data"=>$data,
      "kemasan" =>$kemasan,
      "start"=>$start,
      "end"=>$end
    ]);
  }

  public static function ExportTrafikProduksi($a, $b, $c, $d) {
    $branchId    = $a;
    $start       = $b;
    $end         = $c;
    $branchCode  = $d;

    $startDate = date("Y-m-d", strtotime($start));
    $endDate = date("Y-m-d", strtotime($end));

    $getRpt = DB::connection('omcargo')->table('V_RPT_TRAFIK_DAN_PROD');
    if (!empty($branchId)) {
      $getRpt->where('BRANCH_ID',$branchId);
    }
    if (!empty($branchCode)) {
      $getRpt->where('BRANCH_CODE',$branchCode);
    }
    if (!empty($start) AND !empty($end)) {
      $getRpt->whereBetween('NOTA_DATE',[$startDate,$endDate]);
    } else if (!empty($start) AND empty($end)) {
      $getRpt->where('NOTA_DATE', '>', $startDate);
    } else if (empty($start) AND !empty($end)) {
      $getRpt->where('NOTA_DATE', '<', $endDate);
    }

    $raw    = $getRpt;
    $result = $getRpt->get();

    return view('print.trafik',["data"=>$result]);
  }

  public static function penyebut($nilai) {
    $nilai = abs($nilai);
    $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
    $temp = "";
    if ($nilai < 12) {
        $temp = " ". $huruf[$nilai];
    } else if ($nilai <20) {
        $temp = static::penyebut($nilai - 10). " belas";
    } else if ($nilai < 100) {
        $temp = static::penyebut($nilai/10)." puluh". static::penyebut($nilai % 10);
    } else if ($nilai < 200) {
        $temp = " seratus" . static::penyebut($nilai - 100);
    } else if ($nilai < 1000) {
        $temp = static::penyebut($nilai/100) . " ratus" . static::penyebut($nilai % 100);
    } else if ($nilai < 2000) {
        $temp = " seribu" . static::penyebut($nilai - 1000);
    } else if ($nilai < 1000000) {
        $temp = static::penyebut($nilai/1000) . " ribu" . static::penyebut($nilai % 1000);
    } else if ($nilai < 1000000000) {
        $temp = static::penyebut($nilai/1000000) . " juta" . static::penyebut($nilai % 1000000);
    } else if ($nilai < 1000000000000) {
        $temp = static::penyebut($nilai/1000000000) . " milyar" . static::penyebut(fmod($nilai,1000000000));
    } else if ($nilai < 1000000000000000) {
        $temp = static::penyebut($nilai/1000000000000) . " triliun" . static::penyebut(fmod($nilai,1000000000000));
    }
    return $temp;
  }

  public static function terbilang($nilai) {
    if($nilai<0) {
      $hasil = "minus ". trim(static::penyebut($nilai));
    } else {
      $hasil = trim(static::penyebut($nilai));
    }
    return $hasil;
  }
}
?>
