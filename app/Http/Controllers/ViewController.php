<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use Dompdf\Dompdf;

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

    public function view($input, $request) {
      $table  = $input["table"];
      $data   = \DB::connection($input["db"])->table($table)->get();
      return $data;
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
      $html = view('print.getPass');
      // return $html;
      $filename = "Test";
      $dompdf = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function printProforma2($id) {
      $html = view('print.proforma2');
      // return $html;
      $filename = "Test";
      $dompdf = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }

    function printUper2($id) {
      $connection = DB::connection('omcargo');
      $header     = $connection->table("TX_HDR_UPER")->where("UPER_ID", "=", $id)->get();
      $jshead     = json_decode(json_encode($header), TRUE);
      $detail     = $connection->table("TX_DTL_UPER")->where("UPER_HDR_ID", "=", $jshead[0]["uper_id"])->get();
      $html       = view('print.uper2',["header"=>$header, "detail"=>$detail]);
      // return view('print.uper2',["header"=>$header, "detail"=>$detail]);
      $filename   = "Test";
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
      // return $detail;
    }

    function printProformaReceiving($id) {
      $html = view('print.proformaReceiving');
      // return $html;
      $filename = "Test";
      $dompdf = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));
    }
}
