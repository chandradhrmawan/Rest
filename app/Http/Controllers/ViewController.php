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
use Illuminate\Support\Facades\Input;

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
      $user = $request['user'];
      return PlgConnectedExternalApps::getVesselNpks($input,$user);
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


  function printGetPass($id) {
    return PrintAndExport::printGetPass($id);
  }

  function printRDCardNPKS($branchCode, $notaId, $id) {
    return PrintAndExport::printRDCardNPKS($branchCode, $notaId, $id);
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

  function getTrafikProduksi($input) {
    return ViewExt::getTrafikProduksi($input);
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

  function ExportTrafikProduksi($a,$b,$c,$d) {
    return PrintAndExport::ExportTrafikProduksi($a,$b,$c,$d);
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

  function postApi() {
    $id     = Input::get('id');
    return $this->apiSet($id);
  }

  function updateTsNota() {
    $set        = Input::get('set');
    $notaId     = Input::get('notaId');
    $branchId   = Input::get('branchId');

    $findNota   = [
      "NOTA_ID"   => $notaId,
      "BRANCH_ID" => $branchId
    ];

    $update     = [
      "API_SET" => $set
    ];

    $update     = DB::connection('mdm')->table('TS_NOTA')->where($findNota)->update($update);
    return $this->apiSet($notaId);
  }

  function apiSet($notaId) {
    $search        = DB::connection('mdm')->table('TM_NOTA')->where('SERVICE_CODE', '2')->select(DB::raw('DISTINCT(NOTA_NAME), NOTA_ID'))->get();
    $connect       = DB::connection('mdm')->table('TS_NOTA')->join('TM_NOTA', 'TM_NOTA.NOTA_ID', '=', 'TS_NOTA.NOTA_ID');
    if (!empty($notaId)) {
      $findNota   = [
        "TS_NOTA.NOTA_ID" => $notaId,
        "TS_NOTA.BRANCH_ID" => 4
      ];

      $connect->where($findNota);
    }
    $data   = $connect->get();
    $label  = DB::connection('mdm')->table('TM_NOTA')->where('NOTA_ID', $notaId)->first();
    return view('print.apiSet',["label"=>$label->nota_label,"id"=>$notaId,"data"=>$data,"search"=>$search]);

  }

  function cekBayarNota($input) {
    $custId       = $input["custId"];

    if (empty($custId)) $custId = "";

    $date = date('Y-m-d H:i:s', strtotime('-1 week'));

    $findNota = [
      "NOTA_PAID" => "N",
      "NOTA_CUST_ID" => $custId
    ];

    $nota     = DB::connection('omuster')->table("TX_HDR_NOTA")->whereDate('NOTA_DATE', '<', $date)->where('NOTA_PAID', 'N')->where("NOTA_CUST_ID", $custId)->get();
    $count = count($nota);

    return ["count" => $count, "result" => $nota];
  }

  function listRdSpps($input) {

  }

}
