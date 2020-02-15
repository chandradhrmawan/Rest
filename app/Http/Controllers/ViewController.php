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
    return ViewExt::getDebitur($input);
  }

  function getRekonsilasi($input, $request) {
    return ViewExt::getRekonsilasi($input);
  }

  function getRptDtlPendapatan($input, $request){
    return ViewExt::getRptDtlPendapatan($input);
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
