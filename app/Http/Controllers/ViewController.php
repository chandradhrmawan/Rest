<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;
use App\Helper\Testing;

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
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input, $request);
    }

    public function view($input) {
      $table  = $input["table"];
      $data   = \DB::connection('order')->table($table)->get();
      return response()->json($data);
    }

    function testing($input, $request)
    {
      // return response()->json('asd');
      return Testing::testing($input);
    }

    function storeProfileTariff($input, $request) {

      foreach ($detil as $list) {
        if (!empty($list['ALAT'])) {
          $each       = explode('/', $list['ALAT']);
          $subisocode = \DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where([
            "EQUIPMENT_ID" => $each[0],
            "EQUIPMENT_UNIT" => $each[1]
          ])->get();
          if (count($subisocode) == 0) {
            return response()->json(["result" => "Fail, iso code not found"]);
          }
        }

        if (!empty($list['BARANG'])) {
          $each       = explode('/', $list['BARANG']);
          $isocode    = \DB::connection('mdm')->table('TM_ISO_COMMODITY')->where([
            "PACKAGE_ID"        => $each[0],
            "COMMODITY_ID"      => $each[1],
            "COMMODITY_UNIT_ID" => $each[2]
          ])->get();
          if (count($subisocode) == 0) {
            return response()->json(["result" => "Fail, iso code not found"]);
          }
        }

        elseif (!empty($list['KONTAINER'])) {
          $each           = explode('/', $list['KONTAINER']);
          $isocode        = \DB::connection('mdm')->table('TM_ISO_CONT')->where([
            "CONT_SIZE"   => $each[0],
            "CONT_TYPE"   => $each[1],
            "CONT_STATUS" => $each[2]
          ])->get();
          if (count($subisocode) == 0) {
            return response()->json(["result" => "Fail, iso code not found"]);
          }
        }
      }

      $head       = $input['header_set'];
      $detil      = $input['detil'];
      $datenow    = Carbon::now()->format('m/d/Y');

      // store head

        if(empty($head['TARIFF_ID'])){
          $headS    = new TxProfileTariffHdr;
        }else{
          $headS    = TxProfileTariffHdr::find($head['TARIFF_ID']);
        }

        $headS->tariff_type   = $head['TARIFF_TYPE'];
        $headS->tariff_start  = \DB::raw("TO_DATE('".$head['TARIFF_START']."', 'DD/MM/YYYY')");
        $headS->tariff_end    = \DB::raw("TO_DATE('".$head['TARIFF_END']."', 'DD/MM/YYYY')");
        $headS->tariff_no     = $head['TARIFF_NO'];
        $headS->tariff_status = $head['TARIFF_STATUS'];
        $headS->service_code  = $head['SERVICE_CODE'];
        $headS->branch_id     = 10; // SESSION LOGIN
        $headS->created_by    = 1; // SESSION LOGIN
        $headS->created_date  = \DB::raw("TO_DATE('".$datenow."', 'MM/DD/YYYY')");
        $headS->save();
      // store head

      // store detil
        TsTariff::where('TARIFF_PROF_HDR_ID',$headS->tariff_id)->delete();
        foreach ($detil as $list) {
          $isocode    = "";
          $subisocode = "";
          if (!empty($list['ALAT'])) {
            // Get Data with / separater
            $each       = explode('/', $list['ALAT']);
            $subisocode = \DB::connection('mdm')->table('TM_ISO_EQUIPMENT')->where([
              "EQUIPMENT_ID" => $each[0],
              "EQUIPMENT_UNIT" => $each[1]
            ])->get();
            $subisocode = $subisocode[0]->iso_code;
          }

          if (!empty($list['BARANG'])) {
            $each       = explode('/', $list['BARANG']);
            $isocode    = \DB::connection('mdm')->table('TM_ISO_COMMODITY')->where([
              "PACKAGE_ID"        => $each[0],
              "COMMODITY_ID"      => $each[1],
              "COMMODITY_UNIT_ID" => $each[2]
            ])->get();
            $isocode    = $isocode[0]->iso_code;
          }

          elseif (!empty($list['KONTAINER'])) {
            $each           = explode('/', $list['KONTAINER']);
            $isocode        = \DB::connection('mdm')->table('TM_ISO_CONT')->where([
              "CONT_SIZE"   => $each[0],
              "CONT_TYPE"   => $each[1],
              "CONT_STATUS" => $each[2]
            ])->get();
            $isocode = $isocode[0]->iso_code;
          }

          // Detail
          $detilS                     = new TsTariff;
          $detilS->tariff_prof_hdr_id = $headS->tariff_id;
          $detilS->service_code       = $headS->service_code;
          $detilS->sub_iso_code       = $subisocode;
          $detilS->iso_code           = $isocode;
          $detilS->branch_id          = 10; // SESSION LOGIN

          $detilS->nota_id            = $list['LAYANAN'];
          $detilS->tariff_object      = $list['OBJECT_TARIFF'];
          $detilS->group_tariff_id    = $list['GROUP_TARIFF'];
          $detilS->tariff             = $list['TARIFF'];
          // $detilS->tariff_STATUS = $list['TARIFF_STATUS'];
          $detilS->tariff_status      = $headS->tariff_status;
          // $detilS->tariff_DI = $list['TARIFF_DI'];
          // $detilS->SUB_TARIFF = $list['SUB_TARIFF'];
          $detilS->save();
        }
      // store detil

      if (!empty($head['FILE']['PATH'])) {
        if (!empty($headS->tariff_file) and file_exists($headS->tariff_file)) {
          unlink($headS->tariff_file);
        }

        $directory  = 'billing/profile_tariff/'.$headS->tariff_id.'/';
        $response   = $this->upload_file($head['FILE'], $directory);
        if ($response['response'] == true) {
          TxProfileTariffHdr::where('tariff_id',$headS->tariff_id)->update([
            'tariff_name' => $head['TARIFF_NAME'],
            'tariff_file' => $response['link']
          ]);
        }
      }

      return response()->json([
        "result" => "Success, store profile tariff data",
      ]);
    }

    // upload file
      function upload_file($file, $directory){
        if (!file_exists($directory)){
            mkdir($directory, 0777);
        }

        $decoded_file = base64_decode($file['BASE64']); // decode the file
        $file = explode('/', $file['PATH']);
        $file = $file[count($file)-1];
        $file_dir = $directory.$file;
        try {
          file_put_contents($file_dir, $decoded_file);
          $response = true;
        } catch (Exception $e) {
          $response = $e->getMessage();
        }

        return ["response"=>$response, "link"=>$file_dir];
      }
    // upload file
}
