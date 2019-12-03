<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\UperRequest;
use Dompdf\Dompdf;
use App\Helper\ConnectedExternalApps;

class ViewController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function api(Request $request) {
      $input  = $request->input();
      if (isset($input['encode']) and $input['encode'] == 'true') {
        $request = json_decode($input['request'], true);
        $input = json_decode($input['request'], true);
        $input['encode'] = 'true';
      }
      $action = $input["action"];
      $request = $request;
      $response = $this->$action($input, $request);

      if (isset($input['encode']) and $input['encode'] == 'true') {
        return response()->json(['response' => json_encode($response)]);
      }else{
        return response()->json($response);
      }
    }

    function getViewDetilTCA($input, $request){
      return ConnectedExternalApps::getViewDetilTCA($input);
    }

    public function view($input, $request) {
      $table  = $input["table"];
      $data   = \DB::connection($input["db"])->table($table)->get();
      return $data;
    }

    function viewTempUper($input, $request) {
        return UperRequest::viewTempUper($input);
    }

    // BillingEngine
    function viewProfileTariff($input, $request) {
        return BillingEngine::viewProfileTariff($input);
    }

    function viewCustomerProfileTariff($input, $request) {
      return BillingEngine::viewCustomerProfileTariff($input);
    }
    // BillingEngine

    // UserAndRoleManagemnt
    function permissionGet($input, $request) {
      return UserAndRoleManagemnt::permissionGet($input);
    }

    public function menuTree(Request $request, $roll_id) {
      return UserAndRoleManagemnt::menuTree($roll_id);
    }
    // UserAndRoleManagemnt

    function printGetPass($id) {
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

      $html = view('print.getPass', ["data"=>$data]);
      $filename = "Test";
      $dompdf = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));

      // return $data[0]->tca_vessel_nam;
    }

    function printProforma2($id) {
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
                      ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID", "=",$header[0]->nota_group_id]])
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
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->nota_branch_id)->get();
      $terbilang   = $this->terbilang($header[0]->nota_amount);
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
                    		THEN (SELECT TO_CHAR(BM_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MON-YY') FROM TX_HDR_BM WHERE BM_NO = A.NOTA_REQ_NO)
                    	WHEN A.NOTA_GROUP_ID = 14
                    		THEN (SELECT TO_CHAR(REC_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MON-YY') FROM TX_HDR_REC WHERE REC_NO = A.NOTA_REQ_NO)
                    	WHEN A.NOTA_GROUP_ID IN (15,19)
                    		THEN (SELECT TO_CHAR(DEL_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MON-YY') FROM TX_HDR_DEL WHERE DEL_NO = A.NOTA_REQ_NO)
                    	END AS PERIODE
              FROM
              	TX_HDR_NOTA A
              WHERE
                A.NOTA_ID = '$id'
              ";
      $kapal       = DB::connection('omcargo')->select($query);
      $nota       = DB::connection('eng')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();
      $html       = view('print.proforma2',["bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan,"label"=>$nota, "handling"=>$handling, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
      $filename   = $all["header"][0]->nota_no.rand(10,100000);
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function printInvoice($id) {
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
                      ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID", "=",$header[0]->nota_group_id]])
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
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->nota_branch_id)->get();
      $terbilang   = $this->terbilang($header[0]->nota_amount);
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
                    		THEN (SELECT TO_CHAR(BM_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(BM_ETD,'DD-MON-YY') FROM TX_HDR_BM WHERE BM_NO = A.NOTA_REQ_NO)
                    	WHEN A.NOTA_GROUP_ID = 14
                    		THEN (SELECT TO_CHAR(REC_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(REC_ETD,'DD-MON-YY') FROM TX_HDR_REC WHERE REC_NO = A.NOTA_REQ_NO)
                    	WHEN A.NOTA_GROUP_ID IN (15,19)
                    		THEN (SELECT TO_CHAR(DEL_ETA,'DD-MON-YY')|| ' / ' || TO_CHAR(DEL_ETD,'DD-MON-YY') FROM TX_HDR_DEL WHERE DEL_NO = A.NOTA_REQ_NO)
                    	END AS PERIODE
              FROM
              	TX_HDR_NOTA A
              WHERE
                A.NOTA_ID = '$id'
              ";
      $kapal       = DB::connection('omcargo')->select($query);
      $nota       = DB::connection('eng')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_id)->get();
      $html       = view('print.invoice',["bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
      $filename   = "Test";
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function printUperPaid($id) {
      $connect    = DB::connection("omcargo");
      $header     = $connect->table("TX_HDR_UPER")->where('UPER_NO', $id)->get();
      $branch     = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->uper_branch_id)->get();
      $terbilang  = $this->terbilang($header[0]->uper_amount);
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
      $data       = DB::connection('omcargo')->select($query);
      $html       = view('print.uperPaid',["branch"=>$branch,"header"=>$header,"data"=>$data,"terbilang"=>$terbilang]);
      $filename   = $header[0]->uper_no.rand(10,100000);
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function printUper2($id) {
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
                      ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id],["NOTA_ID",'=', $header[0]->uper_nota_id]])
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
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->uper_branch_id)->get();
      $terbilang   = $this->terbilang($header[0]->uper_amount);
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

      $nota       = DB::connection('eng')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->uper_nota_id)->get();
      $html       = view('print.uper2',["bl"=>$bl,"branch"=>$branch,"label"=>$nota,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat,"terbilang"=>$terbilang]);

      $filename   = $all["header"][0]->uper_no.rand(10,100000);
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function penyebut($nilai) {
      $nilai = abs($nilai);
      $huruf = array("", "satu", "dua", "tiga", "empat", "lima", "enam", "tujuh", "delapan", "sembilan", "sepuluh", "sebelas");
      $temp = "";
      if ($nilai < 12) {
          $temp = " ". $huruf[$nilai];
      } else if ($nilai <20) {
          $temp = $this->penyebut($nilai - 10). " belas";
      } else if ($nilai < 100) {
          $temp = $this->penyebut($nilai/10)." puluh". $this->penyebut($nilai % 10);
      } else if ($nilai < 200) {
          $temp = " seratus" . $this->penyebut($nilai - 100);
      } else if ($nilai < 1000) {
          $temp = $this->penyebut($nilai/100) . " ratus" . $this->penyebut($nilai % 100);
      } else if ($nilai < 2000) {
          $temp = " seribu" . $this->penyebut($nilai - 1000);
      } else if ($nilai < 1000000) {
          $temp = $this->penyebut($nilai/1000) . " ribu" . $this->penyebut($nilai % 1000);
      } else if ($nilai < 1000000000) {
          $temp = $this->penyebut($nilai/1000000) . " juta" . $this->penyebut($nilai % 1000000);
      } else if ($nilai < 1000000000000) {
          $temp = $this->penyebut($nilai/1000000000) . " milyar" . $this->penyebut(fmod($nilai,1000000000));
      } else if ($nilai < 1000000000000000) {
          $temp = $this->penyebut($nilai/1000000000000) . " triliun" . $this->penyebut(fmod($nilai,1000000000000));
      }
      return $temp;
    }

    function terbilang($nilai) {
      if($nilai<0) {
        $hasil = "minus ". trim($this->penyebut($nilai));
      } else {
        $hasil = trim($this->penyebut($nilai));
      }
      return $hasil;
    }

}
