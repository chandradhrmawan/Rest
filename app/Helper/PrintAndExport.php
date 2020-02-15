<?php

namespace App\Helper;

use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Dompdf\Dompdf;
use Firebase\JWT\JWT;
use App\Helper\FileUpload;

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

  public static function printUperPaid($id) {
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
                    THEN (SELECT TO_CHAR(BM_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MON-YY') FROM TX_HDR_BM WHERE BM_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID = 14
                    THEN (SELECT TO_CHAR(REC_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MON-YY') FROM TX_HDR_REC WHERE REC_NO = A.UPER_REQ_NO)
                  WHEN A.UPER_NOTA_ID IN (15,19)
                    THEN (SELECT TO_CHAR(DEL_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MON-YY') FROM TX_HDR_DEL WHERE DEL_NO = A.UPER_REQ_NO)
                  END AS PERIODE,
                  A.UPER_NO,
                  A.UPER_TRADE_TYPE,
                  A.UPER_AMOUNT,
                  B.PAY_AMOUNT,
                  B.PAY_ACCOUNT_NAME,
                  TO_CHAR(B.PAY_DATE,'DD-MON-YY') PAY_DATE,
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

  // public static function 

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
