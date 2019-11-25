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

    function printUper($id) {
      $html = view('print.uper');
      // return $html;
      $filename = "Test";
      $dompdf = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

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
                      ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id]])
                      ->get();
        foreach ($componen as $listS) {
          foreach ($listS as $key => $value) {
                  $newDt[$key] = $value;
          }
        }

        // $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
        }
      }

      $all = ["header"=>$header]+$det;
      $dpp         = DB::connection('omcargo')->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID',$id)->sum("dtl_amount");
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->nota_branch_id)->get();
      $ppn         = $dpp*10/100;
      $terbilang   = $this->terbilang($dpp+$ppn);
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
      $html       = view('print.proforma2',["bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat,"dpp"=>$dpp,"ppn"=>$ppn,"terbilang"=>$terbilang]);
      $filename   = "Test";
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
                      ->where([['GROUP_TARIFF_ID','=', $list->dtl_group_tariff_id]])
                      ->get();
        foreach ($componen as $listS) {
          foreach ($listS as $key => $value) {
                  $newDt[$key] = $value;
          }
        }

        // $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
        }
      }

      $all = ["header"=>$header]+$det;
      $dpp         = DB::connection('omcargo')->table("V_TX_DTL_NOTA")->where('NOTA_HDR_ID',$id)->sum("dtl_amount");
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->nota_branch_id)->get();
      $ppn         = $dpp*10/100;
      $terbilang   = $this->terbilang($dpp+$ppn);
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
      $html       = view('print.invoice',["bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat,"dpp"=>$dpp,"ppn"=>$ppn,"terbilang"=>$terbilang]);
      $filename   = "Test";
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
        }

        // $det[]=$newDt;
        if ($newDt["comp_nota_view"] == "1") {
          $det["penumpukan"][]=$newDt;
        } if ($newDt["comp_nota_view"] == "2") {
          $det["handling"][]=$newDt;
        }  if ($newDt["comp_nota_view"] == "3") {
          $det["alat"][]=$newDt;
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

      $html       = view('print.uper2',["bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "alat"=>$alat,"terbilang"=>$terbilang]);
      $filename   = "uper";
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
