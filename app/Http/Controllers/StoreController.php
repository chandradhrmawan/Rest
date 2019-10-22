<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Models\Billing\TxProfileTariffHdr;
use App\Models\Billing\TsTariff;
use Carbon\Carbon;

class StoreController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request) {
        $this->request = $request;
    }

    public function api(Request $request) {
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input, $request);
    }

    function validasi($action, $request) {
      $latest   = DB::connection("mdm")->table('JS_VALIDATION')->where('action', 'like', $action."%")->select(["field", "mandatori"])->get();
      $decode   = json_decode(json_encode($latest), true);
      $s        = array();
      foreach ($decode as $data) {
      $s[$data["field"]] = $data["mandatori"];
      }
      $this->validate($request, $s);
      return response($latest);
    }

    function index($input, $request) {
      $this->validasi($input["action"], $request);
      $connect  = \DB::connection($input["db"])->table($input["table"]);

      if ($input['start'] != '' && $input['limit'] != '')
        $connect->skip($input['start'])->take($input['limit']);

      $result   = $connect->get();
      $count    = $connect->count();

      return response()->json(["result"=>$result, "count"=>$count]);
    }

    function save($input, $request) {
      $parameter   = $input['parameter'];
      $connect    = \DB::connection($input["db"])->table($input["table"]);
      foreach ($parameter as $value) $connect->insert($parameter);
      return response(["result"=>$parameter, "count"=>count($parameter)]);
    }

    // schema billing_engine
      function storeProfileTariff($input, $request) {
        $datenow    = Carbon::now()->format('m/d/Y');

        $head       = $input['header_set'];
        $detil      = $input['detil'];

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
        // $headS->tariff_STATUS = 0;

        $headS->branch_id     = 12; // SESSION LOGIN
        $headS->created_by    = 1; // SESSION LOGIN
        $headS->created_date  = \DB::raw("TO_DATE('".$datenow."', 'MM/DD/YYYY')");
        $headS->save();

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
          $detilS->branch_id          = 12; // SESSION LOGIN

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

        if (!empty($head['FILE']['PATH'])) {
          if (!empty($headS->tariff_file)) {
            unlink($headS->tariff_file);
          }

          $directory  = 'billing/profile_tariff/'.$headS->tariff_id.'/';
          $response   = $this->upload_file($head['FILE'], $directory);
          if ($response['response'] == true) {
            TxProfileTariffHdr::where('tariff_id',$headS->tariff_id)->update([
              'tariff_name' => $head['TARIFF_NAME'],
              'tariff_file' => $response['link']
            ]);
            // return response()->json($fileS);
          }
        }

        return response()->json([
          "result" => "Success, store profile tariff data",
        ]);
      }
      function storeCustomerProfileTariffAndUper($input, $request){
        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->where('CUST_PROFILE_ID', $input['CUST_PROFILE_ID'])->delete();
        $setS = [];
        foreach ($input['TARIFF'] as $list) {
          $setS[] = [ 'CUST_PROFILE_ID' => $input['CUST_PROFILE_ID'], 'TARIFF_HDR_ID' => $list['TARIFF_HDR_ID']];
        }
        DB::connection('eng')->table('TS_CUSTOMER_PROFILE')->insert($setS);
        
        DB::connection('eng')->table('TS_UPER')->where('UPER_CUST_ID', $input['CUST_PROFILE_ID'])->delete();
        $setS = [];
        foreach ($input['UPER'] as $list) {
          $setS[] = [ 
            'UPER_CUST_ID' => $input['CUST_PROFILE_ID'], 
            'UPER_NOTA' => $list['UPER_NOTA'],
            'UPER_PRESENTASE' => $list['UPER_PRESENTASE'],
            'BRANCH_ID' => 12
          ];
        }
        DB::connection('eng')->table('TS_UPER')->insert($setS);

        return response()->json([
          "result" => "Success, store and set profile tariff and uper customer",
        ]);
      }
    // schema billing_engine

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
