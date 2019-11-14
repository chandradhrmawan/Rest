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
      $connection = DB::connection('omcargo');
      $header     = $connection->table("TX_HDR_NOTA")->where("NOTA_ID", "=", $id)->get();
      $html       = view('print.proforma2',["header"=>$header]);
      $jshead     = json_decode(json_encode($header), TRUE);
      // $stev       = $connection->table("TX_DTL_UPER")
      //               ->join('TX_HDR_UPER ', 'TX_DTL_UPER.UPER_HDR_ID', '=', 'TX_HDR_UPER.UPER_ID')
      //               ->join('TX_HDR_BM', 'TX_HDR_UPER.UPER_REQ_NO', '=', 'TX_HDR_BM.BM_NO')
      //               ->join('TX_DTL_BM', 'TX_HDR_BM.BM_ID', '=', 'TX_DTL_BM.HDR_BM_ID')
      //               ->where([['UPER_HDR_ID ', '=', $id],["dtl_service_type", "like", "%STEVEDORING%"]])
      //               ->get();
      // $cargo      = $connection->table("TX_DTL_UPER")
      //               ->join('TX_HDR_UPER ', 'TX_DTL_UPER.UPER_HDR_ID', '=', 'TX_HDR_UPER.UPER_ID')
      //               ->join('TX_HDR_BM', 'TX_HDR_UPER.UPER_REQ_NO', '=', 'TX_HDR_BM.BM_NO')
      //               ->join('TX_DTL_BM', 'TX_HDR_BM.BM_ID', '=', 'TX_DTL_BM.HDR_BM_ID')
      //               ->where([['UPER_HDR_ID ', '=', $id],["dtl_service_type", "like", "%CARGODORING%"]])
      //               ->get();
      // $angkutan    = $connection->table("TX_DTL_UPER")
      //               ->join('TX_HDR_UPER ', 'TX_DTL_UPER.UPER_HDR_ID', '=', 'TX_HDR_UPER.UPER_ID')
      //               ->join('TX_HDR_BM', 'TX_HDR_UPER.UPER_REQ_NO', '=', 'TX_HDR_BM.BM_NO')
      //               ->join('TX_DTL_BM', 'TX_HDR_BM.BM_ID', '=', 'TX_DTL_BM.HDR_BM_ID')
      //               ->where([['UPER_HDR_ID ', '=', $id],["dtl_service_type", "like","ANGKUTAN LANGSUNG"]])
      //               ->get();
      // $sewa        = $connection->table("TX_DTL_UPER")
      //               ->join('TX_HDR_UPER ', 'TX_DTL_UPER.UPER_HDR_ID', '=', 'TX_HDR_UPER.UPER_ID')
      //               ->join('TX_HDR_BM', 'TX_HDR_UPER.UPER_REQ_NO', '=', 'TX_HDR_BM.BM_NO')
      //               ->join('TX_DTL_BM', 'TX_HDR_BM.BM_ID', '=', 'TX_DTL_BM.HDR_BM_ID')
      //               ->where([['UPER_HDR_ID', '=', $id],["dtl_service_type", "like","SEWA ALAT"]])
      //               ->get();

        return $stev;
      // return $header;
      // $filename = "Test";
      // $dompdf = new Dompdf();
      // $dompdf->set_option('isRemoteEnabled', true);
      // $dompdf->loadHtml($html);
      // $dompdf->setPaper('A4', 'potrait');
      // $dompdf->render();
      // $dompdf->stream($filename, array("Attachment" => false));
    }

    function printUper2($id) {
      $connection = DB::connection('omcargo');
      $header     = $connection->table("TX_HDR_UPER")->where("UPER_ID", "=", $id)->get();
      $jshead     = json_decode(json_encode($header), TRUE);
      $detail     = [];
      $type       = DB::connection("eng")->table("TM_NOTA")->where('NOTA_ID', $header[0]->uper_nota_id)->get();
      if ($type[0]->nota_service_code == "BM") {
        $bl         = $connection->table("V_TX_DTL_UPER")
                      ->where([['UPER_HDR_ID ', '=', $id],["dtl_group_tariff_name", "!=", "SEWA ALAT"],["dtl_group_tariff_name", "!=", "RETRIBUSI ALAT"]])
                      ->select("dtl_bl")
                      ->distinct()
                      ->get();
        for ($i=0; $i < count($bl); $i++) {
          $data       = $connection->table("V_TX_DTL_UPER")
                      ->where([['UPER_HDR_ID ', '=', $id],["dtl_bl", "=", $bl[$i]->dtl_bl],["dtl_group_tariff_name", "!=", "SEWA ALAT"],["dtl_group_tariff_name", "!=", "RETRIBUSI ALAT"]])
                      ->get();
          $detail[$bl[$i]->dtl_bl] = $data;
        }
      } else {
        $detail       = $connection->table("V_TX_DTL_UPER")
                    ->where([['UPER_HDR_ID ', '=', $id],["dtl_group_tariff_name", "!=", "SEWA ALAT"],["dtl_group_tariff_name", "!=", "RETRIBUSI ALAT"]])
                    ->get();
      }
      $sewa        = $connection->table("V_TX_DTL_UPER")
                    ->where([['UPER_HDR_ID', '=', $id],["dtl_group_tariff_name", "like","SEWA ALAT"]])
                    ->get();
      $retribusi   = $connection->table("V_TX_DTL_UPER")
                    ->where([['UPER_HDR_ID', '=', $id],["dtl_group_tariff_name", "like","RETRIBUSI ALAT"]])
                    ->get();
      $dpp         = $connection->table("V_TX_DTL_UPER")->where('UPER_HDR_ID',$id)->sum("dtl_amount");
      $branch      = DB::connection('mdm')->table("TM_BRANCH")->where('BRANCH_ID', $header[0]->uper_branch_id)->get();
      $ppn         = $dpp*10/100;
      $terbilang   = $this->terbilang($dpp+$ppn);
      if (!$sewa) $sewa   = 0;
      if (!$retribusi) $retribusi = 0;
      if ($type[0]->nota_service_code == "BM") {
        $html       = view('print.uper2',["type"=>$type, "bl"=>$bl, "branch"=>$branch, "header"=>$header, "detail"=>$detail, "sewa"=>$sewa,"retribusi"=>$retribusi, "dpp"=>$dpp,"ppn"=>$ppn,"terbilang"=>$terbilang]);
      } else {
        $html       = view('print.uper2',["type"=>$type, "branch"=>$branch, "header"=>$header, "detail"=>$detail, "sewa"=>$sewa, "retribusi"=>$retribusi, "dpp"=>$dpp,"ppn"=>$ppn,"terbilang"=>$terbilang]);

      }
      $filename   = "Test";
      $dompdf     = new Dompdf();
      $dompdf->set_option('isRemoteEnabled', true);
      $dompdf->loadHtml($html);
      $dompdf->setPaper('A4', 'potrait');
      $dompdf->render();
      $dompdf->stream($filename, array("Attachment" => false));

      // return $detail;
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

    // Example Join MDM to Cargo
    // function detailJoin($input) {
    //     $id       = 40;
    //     $detail   = [];
    //     $data_a   = DB::connection("omcargo")->table('TS_LUMPSUM_AREA')->join('TM_REFF', 'TM_REFF.REFF_ID', '=', 'TS_LUMPSUM_AREA.LUMPSUM_STACKING_TYPE')->where("LUMPSUM_ID", "=", $id)->get();
    //     foreach ($data_a as $list) {
    //       $newDt = [];
    //       foreach ($list as $key => $value) {
    //         $newDt[$key] = $value;
    //       }
    //
    //     $data_b = DB::connection("mdm")->table('VIEW_STACKING_AREA')->where("code", $list->lumpsum_area_code)->select("name","branch")->get();
    //     foreach ($data_b as $listS) {
    //       foreach ($listS as $key => $value) {
    //         $newDt[$key] = $value;
    //       }
    //     }
    //     $detail[] = $newDt;
    //   }
    //   return ["result"=>$detail];
    // }
}
