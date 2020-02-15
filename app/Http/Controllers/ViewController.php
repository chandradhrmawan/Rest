<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use GuzzleHttp\Client;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\UperRequest;
use App\Helper\ViewExt;
use App\Helper\PrintAndExport;
use Dompdf\Dompdf;
use App\Helper\ConnectedExternalApps;
use App\Helper\PlgConnectedExternalApps;
use App\Helper\PlgRequestBooking;

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

    function getVesselNpks($input, $request){
      return PlgConnectedExternalApps::getVesselNpks($input);
    }

    function splitNota($input, $request){
      return ViewExt::splitNota($input);
    }

    function getViewDetilTCA($input, $request){
      return ConnectedExternalApps::getViewDetilTCA($input);
    }

    public function getViewNotaPLB($input, $request) {
      return ViewExt::getViewNotaPLB($input);
    }

    function getViewNotaPLB_old($input, $request){
      return ViewExt::getViewNotaPLB_old($input);
    }

    function viewCancelCargo($input) {
      return ViewExt::viewCancelCargo($input);
    }

    function viewTempUper($input, $request) {
        return UperRequest::viewTempUper($input);
    }

    function viewTempTariffPLG($input, $request) {
        return PlgRequestBooking::viewTempTariffPLG($input);
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


  // Belum Ke PIndah Ke Helper
  function printGetPass($id) {
    return PrintAndExport::printGetPass($id);
    }

  function printProforma2($id) {
    return PrintAndExport::printProformaNPK($id);
  }

  function printUperPaid($id) {
    return PrintAndExport::printUperPaidNPK($id);
  }

  function printUper2($id) {
    return PrintAndExport::printUperNPK($id);
  }

  function printBprp($id) {
    return PrintAndExport::printBprpNPK($id);
  }

  function printRealisasi($id) {
    return PrintAndExport::printRealisasiNPK($id);
  }

  function printInvoice($id) {
    return PrintAndExport::printInvoiceNPK($id);
  }

  function getDebitur($input, $request) {
    $startDate = date("Y-m-d", strtotime($input["startDate"]));
    $endDate = date("Y-m-d", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_DEBITUR');
    if (!empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('NOTA_BRANCH_ID',$input["condition"]["NOTA_BRANCH_ID"]);
    }else if (empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('NOTA_BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["BRANCH_CODE"])) {
      $getRpt->where('BRANCH_CODE',$input["condition"]["BRANCH_CODE"]);
    }else if (empty($input["condition"]["BRANCH_CODE"])) {
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

  function getRekonsilasi($input, $request) {
    $startDate = date("Y-m-d", strtotime($input["startDate"]));
    $endDate = date("Y-m-d", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_REKONSILASI_NOTA');
    if (!empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('NOTA_BRANCH_ID',$input["condition"]["NOTA_BRANCH_ID"]);
    }else if (empty($input["condition"]["NOTA_BRANCH_ID"])) {
      $getRpt->where('NOTA_BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["NOTA_BRANCH_CODE"])) {
      $getRpt->where('NOTA_BRANCH_CODE',$input["condition"]["NOTA_BRANCH_CODE"]);
    }else if (empty($input["condition"]["NOTA_BRANCH_CODE"])) {
      $getRpt->where('NOTA_BRANCH_CODE',$input['user']->user_branch_code);
    }
    if (!empty($input["condition"]["VESSEL"])) {
      $getRpt->where('VESSEL',$input["condition"]["VESSEL"]);
    }
    if (!empty($input["condition"]["UKK"])) {
      $getRpt->where('UKK',$input["condition"]["UKK"]);
    }
    if (!empty($input["condition"]["NOTA"])) {
      $getRpt->where('NOTA',$input["condition"]["NOTA"]);
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

  function getRptDtlPendapatan($input, $request){
    $startDate = date("Y-m-d", strtotime($input["startDate"]));
    $endDate = date("Y-m-d", strtotime($input["endDate"]));

    $getRpt = DB::connection('omcargo')->table('V_RPT_DTL_PENDAPATAN');
    if (!empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('REAL_BRANCH_ID',$input["condition"]["REAL_BRANCH_ID"]);
    }else if (empty($input["condition"]["REAL_BRANCH_ID"])) {
      $getRpt->where('REAL_BRANCH_ID',$input['user']->user_branch_id);
    }
    if (!empty($input["condition"]["REAL_BRANCH_CODE"])) {
      $getRpt->where('REAL_BRANCH_CODE',$input["condition"]["REAL_BRANCH_CODE"]);
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

  function ExportDebitur($a,$b,$c,$d,$e,$f,$g) {
    return PrintAndExport::ExportDebiturNPK($a,$b,$c,$d,$e,$f,$g);
  }

  function ExportRekonsilasi($a,$b,$c,$d,$e,$f,$g) {
    return PrintAndExport::ExportRekonsilasiNPK($a,$b,$c,$d,$e,$f,$g);
  }

  function ExportPendapatan($a,$b,$c,$d,$e,$f,$g) {
    return PrintAndExport::ExportPendapatanNPK($a,$b,$c,$d,$e,$f,$g);
  }

  function getNota($input) {
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

  function einvoiceLink($input) {
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

    return ["link" => $qrcode];
  }

  function notaNPKS($id) {
    $connect        = DB::connection('omuster');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $data["header"] = $header;
    $detail  = $connect->table("TX_DTL_NOTA")->where('NOTA_HDR_ID', $id)->get();
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
        $data       = DB::connection('omuster')->table("TX_DTL_NOTA")
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
    // $qrcode   = $results['getDataCetakResponse']['esbBody']['url'];
    $qrcode = "0";
    $kapal    = "-";
    $nota     = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();
    $handa     = $connect->table("TX_DTL_NOTA")->where('NOTA_HDR_ID','=', $id)->get();
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

    $nota        = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();
    if (!empty($dat["handling"])) {
      $handlingbm  = $dat["handling"];
      $html       = view('print.notaNpks',["label"=>$nota,"qrcode"=>$qrcode,"bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handlingbm,"label"=>$nota, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
    } else {
      $html       = view('print.notaNpks',["label"=>$nota,"qrcode"=>$qrcode,"bl"=>$bl,"branch"=>$branch,"header"=>$header,"penumpukan"=>$penumpukan, "handling"=>$handling, "label"=>$nota, "alat"=>$alat, "kapal"=>$kapal,"terbilang"=>$terbilang]);
    }
    $filename   = "Test";
    $dompdf     = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

  function proformaNPKS($id) {
    $connect        = DB::connection('omuster');
    $det            = [];
    $header         = $connect->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
    $data["header"] = $header;
    $detail  = $connect->table("TX_DTL_NOTA")->where('NOTA_HDR_ID', $id)->get();
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
        $data       = DB::connection('omuster')->table("TX_DTL_NOTA")
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

    $kapal       = "Kapal";
    $nota        = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $all['header'][0]->nota_group_id)->get();

    // Data Uper And Payment
    $uper        = 10000;
    $notaAmount  = $header[0]->nota_amount;
    $payAmount   = 100000;
    $total       = $notaAmount - $payAmount;
    $terbilang   = $this->terbilang($total);

    // return $penumpukan;

    $html        = view('print.proformaNpks',
                        [
                          "total"     =>$total,
                          "uper"      =>$uper,
                          "bl"        =>$bl,
                          "branch"    =>$branch,
                          "header"    =>$header,
                          "penumpukan"=>$penumpukan,
                          "label"     =>$nota,
                          "handling"  =>$handling,
                          "alat"      =>$alat,
                          "terbilang" =>$terbilang
                        ]);

    $filename    = $all["header"][0]->nota_no.rand(10,100000);
    $dompdf      = new Dompdf();
    $dompdf->set_option('isRemoteEnabled', true);
    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'potrait');
    $dompdf->render();
    $dompdf->stream($filename, array("Attachment" => false));
  }

}
