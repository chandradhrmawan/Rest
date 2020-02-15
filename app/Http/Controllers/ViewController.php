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
    return ViewExt::getNota($input);
  }

  function einvoiceLink($input) {
    return ViewExt::einvoiceLink($input);
  }

  function notaNPKS($id) {
    return PrintAndExport::printInvoiceNPKS($id);
  }

  function proformaNPKS($id) {
    return PrintAndExport::printProformaNPKS($id);
  }

}
