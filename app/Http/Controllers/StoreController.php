<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use GuzzleHttp\Client;
use Carbon\Carbon;
use App\Helper\FileUpload;
use App\Helper\BillingEngine;
use App\Helper\UserAndRoleManagemnt;
use App\Helper\RequestBooking;
use App\Helper\UperRequest;
use App\Models\OmCargo\TxHdrBm;
use App\Models\OmCargo\TxHdrRec;
use App\Helper\GlobalHelper;
use App\Helper\ConnectedExternalApps;
use App\Helper\RealisasiHelper;
use App\Models\Mdm\TmTruckCompany;

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
        if (isset($response['Success']) and $response['Success'] == false) {
          return response()->json($response, 401);
        }else{
          return response()->json($response);
        }
      }
    }

    public function testlain($input, $request){
      return ConnectedExternalApps::sendRequestBooking($input['uper_req_no']);
    }

    public function testview_file(){
      $file = file_get_contents(url("omcargo/tx_payment/5/users.png"));
      return base64_encode($file);
    }

    function rejectedProformaNota($input){
      return RealisasiHelper::rejectedProformaNota($input);
    }

    function approvedProformaNota($input){
      return RealisasiHelper::approvedProformaNota($input);
    }

    function confirmRealBM($input){
      return RealisasiHelper::confirmRealBM($input);
    }

    function confirmRealBPRP($input){
      return RealisasiHelper::confirmRealBPRP($input);
    }

    function truckRegistration($input){
      if (empty($input['truck_cust_id'])) {
        $new = new TmTruckCompany;
        $new->comp_name = $input['truck_cust_name'];
        $new->comp_address = $input['truck_cust_address'];
        $new->comp_branch_id = $input['truck_branch_id'];
        $new->save();
        $input['truck_cust_id'] = $new->comp_id;
      }
      $set_data = [
        "truck_plat_no" => strtoupper($input['truck_plat_no']),
        "truck_rfid_code" => strtoupper($input['truck_rfid']),
        "customer_name" => strtoupper($input['truck_cust_name']),
        "customer_address" => strtoupper($input['truck_cust_address']),
        "cdm_customer_id" => strtoupper($input['truck_cust_id']),
        "truck_type" => strtoupper($input['truck_type']),
        "date" => \Carbon\Carbon::createFromFormat("Y-m-d H:i", $input['truck_plat_exp'])->format('d-m-Y')
      ];

      $set_data_self = [
        "truck_id" => str_replace(' ','',$input['truck_plat_no']),
        "truck_name" => $input['truck_name'],
        "truck_plat_no" => $input['truck_plat_no'],
        "truck_cust_id" => $input['truck_cust_id'],
        "truck_cust_name" => $input['truck_cust_name'],
        "truck_branch_id" => $input['truck_branch_id'],
        "truck_date" => $input['truck_date'],
        "truck_cust_address" => $input['truck_cust_address'],
        "truck_type" => $input['truck_type'],
        "truck_terminal_code" => $input['truck_terminal_code'],
        "truck_plat_exp" => $input['truck_plat_exp'],
        "truck_stnk_no" => $input['truck_stnk_no'],
        "truck_stnk_exp" => $input['truck_stnk_exp'],
        "truck_rfid" => $input['truck_rfid'],
        "truck_type_name" => $input['truck_type_name']
      ];
      if ($input['type'] == "CREATE") {
        DB::connection('mdm')->table('TM_TRUCK')->insert($set_data_self);
        return ConnectedExternalApps::truckRegistration($set_data);
      }else{
        DB::connection('mdm')->table('TM_TRUCK')->where('truck_id',$input['truck_id'])->update($set_data_self);
        return ConnectedExternalApps::updateTid($set_data);
      }
    }

    function sendTCA($input){
      $head = DB::connection('omcargo')->table('TX_HDR_TCA')->where('tca_id', $input['id'])->get();
      $head = $head[0];
      if ($head->tca_req_type  == 1) {
        $reques = DB::connection('omcargo')->table('TX_HDR_REC')->where('rec_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->rec_vvd_id;
        $vvdName = $reques->rec_vessel_name;
        $vvdVI = $reques->rec_voyin;
        $vvdVO = $reques->rec_voyout;
      }else if ($head->tca_req_type  == 2) {
        $reques = DB::connection('omcargo')->table('TX_HDR_DEL')->where('del_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->del_vvd_id;
        $vvdName = $reques->del_vessel_name;
        $vvdVI = $reques->del_voyin;
        $vvdVO = $reques->del_voyout;
      }else if ($head->tca_req_type  == 3) {
        $reques = DB::connection('omcargo')->table('TX_HDR_BM')->where('bm_no', $head->tca_req_no)->get();
        $reques = $reques[0];
        $vvdID = $reques->bm_vvd_id;
        $vvdName = $reques->bm_vessel_name;
        $vvdVI = $reques->bm_voyin;
        $vvdVO = $reques->bm_voyout;
      }
      $loop = DB::connection('omcargo')->table('TX_DTL_TCA')->where('tca_hdr_id', $input['id'])->get();
      $detil = [];
      foreach ($loop as $list) {
        $truck = DB::connection('mdm')->table('TM_TRUCK')->where('truck_id', $list->tca_truck_id)->get();
        if (count($truck) == 0) {
          return ["Success"=>false, 'result_msg' => 'Fail, not found '.$list->tca_truck_id.' on mdm.tm_truck'];
        }
        $truck = $truck[0];
        $terminal = DB::connection('mdm')->table('TM_TERMINAL')->where('terminal_code', $head->tca_terminal_code)->get();
        $terminal = $terminal[0];
        $detil[] = [
          "vNoRequest" => $head->tca_req_no,
          "vTruckId" => $truck->truck_id,
          "vTruckNumber" => $truck->truck_plat_no,
          "vBlNumber" => $head->tca_bl,
          "vTcaCompany" => $head->tca_cust_name,
          "vEi" => $head->tca_req_type == 1 ? 'I' : 'E',
          "vRfidCode" => $truck->truck_rfid,
          "vIdServiceType" => $head->tca_req_type,
          "vServiceType" => $head->tca_req_type_name,
          "vIdTruck" => $truck->truck_id,
          "vIdVvd" => $vvdID,
          "vIdTerminal" => $terminal->terminal_id
        ];
      }
      $set_data = [
        "vVessel" => $vvdName,
        "vVin" => $vvdVI,
        "vVout" => $vvdVO,
        "vNoRequest" => $head->tca_req_no,
        "vCustomerName" => $head->tca_cust_name,
        "vCustomerId" => $head->tca_cust_id,
        "vPkgName" => $head->tca_pkg_name,
        "vQty" => $head->tca_qty,
        "vTon" => $head->tca_qty,
        "vBlNumber" => $head->tca_bl,
        "vBlDate" => date('d-M-y', strtotime($head->tca_bl_date)),
        "vEi" => $head->tca_req_type == 1 ? 'I' : 'E',
        "vHsCode" => $head->tca_hs_code,
        "vIdServicetype" => $head->tca_req_type,
        "vServiceType" => $head->tca_req_type_name,
        "vIdVvd" => $vvdID,
        "vIdTerminal" => $terminal->terminal_id,
        "detail" => $detil
      ];

      return ConnectedExternalApps::createTCA($set_data, $input['id']);
    }

    function save($input, $request) {
      return GlobalHelper::save($input);
    }

    function publicCreate($input, $request){
      DB::connection($input['schema'])->table($input['table'])->insert($input['set_data']);
      return response()->json([
        "result" => "Success, create ".$input['table']." data",
      ]);
    }

    function publicUpdate($input, $request){
      DB::connection($input['schema'])->table($input['table'])->where($input['condition'])->update($input['set_data']);
      return response()->json([
        "result" => "Success, update ".$input['table']." data",
      ]);
    }

    // RequestBooking
      function sendRequest($input, $request){
        return RequestBooking::sendRequest($input);
      }

      function approvalRequest($input, $request){
        return RequestBooking::approvalRequest($input);
      }
    // RequestBooking

    // BillingEngine
      function storeProfileTariff($input, $request){
        return BillingEngine::storeProfileTariff($input);
      }
      function storeCustomerProfileTariffAndUper($input, $request){
        return BillingEngine::storeCustomerProfileTariffAndUper($input);
      }
      function getSimulasiTarif($input, $request){
        return BillingEngine::getSimulasiTarif($input);
      }
    // BillingEngine

    // UperRequest
      function storePayment($input, $request){
        return UperRequest::storePayment($input);
      }

      function confirmPaymentUper($input, $request){
        return UperRequest::confirmPaymentUper($input);
      }
    // UperRequest

    // UserAndRoleManagemnt
      function storeRole($input, $request){
        return UserAndRoleManagemnt::storeRole($input);
      }
      function storeRolePermesion($input, $request){
        return UserAndRoleManagemnt::storeRolePermesion($input);
      }
      function storeUser($input, $request){
        return UserAndRoleManagemnt::storeUser($input);
      }
      function changePasswordUser($input, $request){
        return UserAndRoleManagemnt::changePasswordUser($input);
      }
    // UserAndRoleManagemnt

    // Schema OmCargo
    function saveheaderdetail($input) {
      return GlobalHelper::saveheaderdetail($input);
    }

    function update($input){
      return GlobalHelper::update($input);
    }

    // function test($input, $request){
    //   if (isset($input["VALUE"]["DTL_OUT"])) {
    //     $input["VALUE"]["DTL_OUT"] = str_replace("T"," ",$input["VALUE"]["DTL_OUT"]);
    //     $input["VALUE"]["DTL_OUT"] = str_replace(".000Z","",$input["VALUE"]["DTL_OUT"]);
    //   }
    //   return response($input["VALUE"]["DTL_OUT"]);
    // }

  function putInvoice($input, $request){
    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putInvoice";
    $string_json= '{
         "arRequestDoc":{
            "esbHeader":{
               "internalId":"",
               "externalId":"EDI-2910201921570203666",
               "timestamp":"2019-10-29 21:57:020.36665400",
               "responseTimestamp":"",
               "responseCode":"",
               "responseMessage":""
            },
            "esbBody":[
               {
                  "header":{
                     "billerRequestId":"AJT1910001179",
                     "orgId":"1827",
                     "trxNumber":"010.804.19-60.011510",
                     "trxNumberOrig":"",
                     "trxNumberPrev":"",
                     "trxTaxNumber":"",
                     "trxDate":"2019-10-15 22:15:08",
                     "trxClass":"INV",
                     "trxTypeId":"-1",
                     "paymentReferenceNumber":"",
                     "referenceNumber":"",
                     "currencyCode":"IDR",
                     "currencyType":"",
                     "currencyRate":"0",
                     "currencyDate":null,
                     "amount":"1542550",
                     "customerNumber":"112720",
                     "customerClass":"",
                     "billToCustomerId":"-1",
                     "billToSiteUseId":"-1",
                     "termId":null,
                     "status":"P",
                     "headerContext":"PTKM",
                     "headerSubContext":"PTKM00",
                     "startDate":null,
                     "endDate":null,
                     "terminal":"-",
                     "vesselName":"SINDO EAGLE",
                     "branchCode":"JBI",
                     "errorMessage":"",
                     "apiMessage":"",
                     "createdBy":"-1",
                     "creationDate":"2019-10-15 22:15:08",
                     "lastUpdatedBy":"-1",
                     "lastUpdateDate":"2019-10-15 22:15:08",
                     "lastUpdateLogin":"-1",
                     "customerTrxIdOut":null,
                     "processFlag":"",
                     "attribute1":"PTKM00",
                     "attribute2":"",
                     "attribute3":"",
                     "attribute4":"",
                     "attribute5":"",
                     "attribute6":"",
                     "attribute7":"",
                     "attribute8":"",
                     "attribute9":"",
                     "attribute10":"",
                     "attribute11":"",
                     "attribute12":"",
                     "attribute13":"",
                     "attribute14":"",
                     "attribute15":"",
                     "interfaceHeaderAttribute1":null,
                     "interfaceHeaderAttribute2":"",
                     "interfaceHeaderAttribute3":"MUAT/EXPORT",
                     "interfaceHeaderAttribute4":"SINDO EAGLE",
                     "interfaceHeaderAttribute5":"11/12",
                     "interfaceHeaderAttribute6":"15-OCT-19",
                     "interfaceHeaderAttribute7":"",
                     "interfaceHeaderAttribute8":"",
                     "interfaceHeaderAttribute9":"",
                     "interfaceHeaderAttribute10":"false",
                     "interfaceHeaderAttribute11":"",
                     "interfaceHeaderAttribute12":"",
                     "interfaceHeaderAttribute13":"",
                     "interfaceHeaderAttribute14":"",
                     "interfaceHeaderAttribute15":"",
                     "customerAddress":"JL.TEMBANG NO.51 TG.PRIOK",
                     "customerName":"TEMPURAN EMAS PT.",
                     "sourceSystem":"ITOSJBI_NBM",
                     "arStatus":"N",
                     "sourceInvoice":"ITOSJBI_NBM",
                     "arMessage":"",
                     "customerNPWP":"01.321.865.6-042.000",
                     "perKunjunganFrom":null,
                     "perKunjunganTo":null,
                     "jenisPerdagangan":"",
                     "docNum":"",
                     "statusLunas":"Y",
                     "tglPelunasan":"2019-10-15",
                     "amountTerbilang":"",
                     "ppnDipungutSendiri":"",
                     "ppnDipungutPemungut":"",
                     "ppnTidakDipungut":"",
                     "ppnDibebaskan":"",
                     "uangJaminan":"",
                     "piutang":"",
                     "sourceInvoiceType":"ITOS",
                     "branchAccount":"100",
                     "statusCetak":"",
                     "statusKirimEmail":"",
                     "amountDasarPenghasilan":"1390500",
                     "amountMaterai":null,
                     "ppn10Persen":"139050",
                     "statusKoreksi":"",
                     "tanggalKoreksi":null,
                     "keteranganKoreksi":""
                  },
                  "lines":[
                     {
                        "billerRequestId":"AJT1910001179",
                        "trxNumber":"010.804.19-60.011510",
                        "lineId":null,
                        "lineNumber":"1",
                        "description":"PENUMPUKAN MASA I.2",
                        "memoLineId":null,
                        "glRevId":null,
                        "lineContext":"",
                        "taxFlag":"Y",
                        "serviceType":"JBI RECEIVING PNP_M_1-2",
                        "eamCode":"",
                        "locationTerminal":"",
                        "amount":"432000",
                        "taxAmount":"43200",
                        "startDate":"2019-10-20",
                        "endDate":"2019-10-23",
                        "createdBy":"-1",
                        "creationDate":"2019-10-15",
                        "lastUpdatedBy":"-1",
                        "lastUpdatedDate":"2019-10-15",
                        "interfaceLineAttribute1":"",
                        "interfaceLineAttribute2":null,
                        "interfaceLineAttribute3":null,
                        "interfaceLineAttribute4":null,
                        "interfaceLineAttribute5":"",
                        "interfaceLineAttribute6":"20 FCL DRY T",
                        "interfaceLineAttribute7":"5",
                        "interfaceLineAttribute8":"21600",
                        "interfaceLineAttribute9":"4",
                        "interfaceLineAttribute10":"",
                        "interfaceLineAttribute11":"",
                        "interfaceLineAttribute12":null,
                        "interfaceLineAttribute13":"",
                        "interfaceLineAttribute14":"",
                        "interfaceLineAttribute15":"",
                        "lineDoc":""
                     },
                     {
                        "billerRequestId":"AJT1910001179",
                        "trxNumber":"010.804.19-60.011510",
                        "lineId":null,
                        "lineNumber":"2",
                        "description":"LIFT OFF",
                        "memoLineId":null,
                        "glRevId":null,
                        "lineContext":"",
                        "taxFlag":"Y",
                        "serviceType":"JBI RECEIVING LIFT_OF",
                        "eamCode":"",
                        "locationTerminal":"",
                        "amount":"850500",
                        "taxAmount":"85050",
                        "startDate":null,
                        "endDate":null,
                        "createdBy":"-1",
                        "creationDate":"2019-10-15",
                        "lastUpdatedBy":"-1",
                        "lastUpdatedDate":"2019-10-15",
                        "interfaceLineAttribute1":"",
                        "interfaceLineAttribute2":null,
                        "interfaceLineAttribute3":null,
                        "interfaceLineAttribute4":null,
                        "interfaceLineAttribute5":"",
                        "interfaceLineAttribute6":"20 FCL DRY T",
                        "interfaceLineAttribute7":"5",
                        "interfaceLineAttribute8":"170100",
                        "interfaceLineAttribute9":null,
                        "interfaceLineAttribute10":"",
                        "interfaceLineAttribute11":"",
                        "interfaceLineAttribute12":null,
                        "interfaceLineAttribute13":"",
                        "interfaceLineAttribute14":"",
                        "interfaceLineAttribute15":"",
                        "lineDoc":""
                     },
                     {
                        "billerRequestId":"AJT1910001179",
                        "trxNumber":"010.804.19-60.011510",
                        "lineId":null,
                        "lineNumber":"3",
                        "description":"PENUMPUKAN MASA I.1",
                        "memoLineId":null,
                        "glRevId":null,
                        "lineContext":"",
                        "taxFlag":"Y",
                        "serviceType":"JBI RECEIVING PNP_M_1-1",
                        "eamCode":"",
                        "locationTerminal":"",
                        "amount":"108000",
                        "taxAmount":"10800",
                        "startDate":"2019-10-15",
                        "endDate":"2019-10-19",
                        "createdBy":"-1",
                        "creationDate":"2019-10-15",
                        "lastUpdatedBy":"-1",
                        "lastUpdatedDate":"2019-10-15",
                        "interfaceLineAttribute1":"",
                        "interfaceLineAttribute2":null,
                        "interfaceLineAttribute3":null,
                        "interfaceLineAttribute4":null,
                        "interfaceLineAttribute5":"",
                        "interfaceLineAttribute6":"20 FCL DRY T",
                        "interfaceLineAttribute7":"5",
                        "interfaceLineAttribute8":"21600",
                        "interfaceLineAttribute9":"1",
                        "interfaceLineAttribute10":"",
                        "interfaceLineAttribute11":"",
                        "interfaceLineAttribute12":null,
                        "interfaceLineAttribute13":"",
                        "interfaceLineAttribute14":"",
                        "interfaceLineAttribute15":"",
                        "lineDoc":""
                     },
                     {
                        "billerRequestId":"AJT1910001179",
                        "trxNumber":"010.804.19-60.011510",
                        "lineId":null,
                        "lineNumber":"4",
                        "description":"MATERAI",
                        "memoLineId":null,
                        "glRevId":null,
                        "lineContext":"",
                        "taxFlag":"N",
                        "serviceType":"JBI RECEIVING MATERAI",
                        "eamCode":"",
                        "locationTerminal":"",
                        "amount":"13000",
                        "taxAmount":"1300",
                        "startDate":null,
                        "endDate":null,
                        "createdBy":"-1",
                        "creationDate":"2019-10-15",
                        "lastUpdatedBy":"-1",
                        "lastUpdatedDate":"2019-10-15",
                        "interfaceLineAttribute1":"",
                        "interfaceLineAttribute2":null,
                        "interfaceLineAttribute3":null,
                        "interfaceLineAttribute4":null,
                        "interfaceLineAttribute5":"",
                        "interfaceLineAttribute6":"   ",
                        "interfaceLineAttribute7":null,
                        "interfaceLineAttribute8":"13000",
                        "interfaceLineAttribute9":null,
                        "interfaceLineAttribute10":"",
                        "interfaceLineAttribute11":"",
                        "interfaceLineAttribute12":null,
                        "interfaceLineAttribute13":"",
                        "interfaceLineAttribute14":"",
                        "interfaceLineAttribute15":"",
                        "lineDoc":""
                     }
                  ]
               }
            ],
            "esbSecurity":{
               "orgId":"1827",
               "batchSourceId":"",
               "lastUpdateLogin":"",
               "userId":"",
               "respId":"",
               "ledgerId":"",
               "respApplId":"",
               "batchSourceName":""
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
  }

  function putReceipt($input, $request){
    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putReceipt";
    $string_json= '{
       "arRequestDoc":{
          "esbHeader":{
             "internalId":"",
             "externalId":"EDI-2910201921570203666",
             "timestamp":"2019-10-29 21:57:020.36665400",
             "responseTimestamp":"",
             "responseCode":"",
             "responseMessage":""
          },
          "esbBody":[
             {
                "header":{
                   "orgId":"1827",
                   "receiptNumber":"010.804.19-60.001511",
                   "receiptMethod":"BANK",
                   "receiptAccount":"UPER IPCTPK MANDIRI IDR 1200010015040",
                   "bankId":"124009",
                   "customerNumber":"112720",
                   "receiptDate":"2019-10-15 22:15:08",
                   "currencyCode":"IDR",
                   "status":"P",
                   "amount":"1542550",
                   "processFlag":"",
                   "errorMessage":"",
                   "apiMessage":"",
                   "attributeCategory":"",
                   "referenceNum":"",
                   "receiptType":"",
                   "receiptSubType":"",
                   "createdBy":"-1",
                   "creationDate":"2019-10-15",
                   "terminal":"",
                   "attribute1":"",
                   "attribute2":"",
                   "attribute3":"",
                   "attribute4":"",
                   "attribute5":"",
                   "attribute6":"",
                   "attribute7":"",
                   "attribute8":"",
                   "attribute9":"",
                   "attribute10":"",
                   "attribute11":"",
                   "attribute12":"",
                   "attribute13":"",
                   "attribute14":"",
                   "attribute15":"",
                   "statusReceipt":"N",
                   "sourceInvoice":"ITOSJBI_NBM",
                   "statusReceiptMsg":"",
                   "invoiceNum":"",
                   "amountOrig":null,
                   "lastUpdateDate":"2019-10-15",
                   "lastUpdateBy":"-1",
                   "branchCode":"JBI",
                   "branchAccount":"100",
                   "sourceInvoiceType":"ITOS",
                   "remarkToBankId":"BANK_ACCOUNT_ID",
                   "sourceSystem":"ITOSJBI_NBM",
                   "comments":"",
                   "cmsYn":"N",
                   "tanggalTerima":null,
                   "norekKoran":""
                }
             }
          ],
          "esbSecurity":{
             "orgId":"1827",
             "batchSourceId":"",
             "lastUpdateLogin":"",
             "userId":"",
             "respId":"",
             "ledgerId":"",
             "respApplId":"",
             "batchSourceName":""
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
  }

  function putApply($input, $request){
    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putApply";
    $string_json= '{
       "arRequestDoc":{
          "esbHeader":{
             "internalId":"",
             "externalId":"EDI-2910201921570203666",
             "timestamp":"2019-10-29 21:57:020.36665400",
             "responseTimestamp":"",
             "responseCode":"",
             "responseMessage":""
          },
          "esbBody":[
             {
                "header":{
                   "paymentCode":"010.804.19-60.001501",
                   "trxNumber":"010.804.19-60.001511",
                   "orgId":"1827",
                   "amountApplied":"1542550",
                   "cashReceiptId":null,
                   "customerTrxId":null,
                   "paymentScheduleId":null,
                   "bankId":"124009",
                   "receiptSource":"CMS",
                   "legacySystem":"INVOICE",
                   "statusTransfer":"N",
                   "errorMessage":null,
                   "requestIdApply":null,
                   "createdBy":"309",
                   "creationDate":"2019-10-15",
                   "lastUpdateBy":"309",
                   "lastUpdateDate":"2019-10-15",
                   "amountPaid":"1542550",
                   "epay":"N"
                }
             }
          ],
          "esbSecurity":{
             "orgId":"1827",
             "batchSourceId":"",
             "lastUpdateLogin":"",
             "userId":"",
             "respId":"",
             "ledgerId":"",
             "respApplId":"",
             "batchSourceName":""
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
  }

  function retrievePayment($input) {
    // DB::connection('omcargo')->table('payment')->insert($input["data"]);
    return ["Result"=>"Success"];
  }

  function sendPayment($input) {
    // DB::connection('omcargo')->table('payment')->insert($input["data"]);
    return $input["data"];
  }

  // Tar Lanjutin
  function test($input) {
    $endpoint_url="http://10.88.48.57:5555/restv2/accountReceivable/putInvoice";
    $string_start= '
        {
         "arRequestDoc":{
            "esbHeader":{
               "internalId":"",
               "externalId":"EDI-2910201921570203666",
               "timestamp":"2019-10-29 21:57:020.36665400",
               "responseTimestamp":"",
               "responseCode":"",
               "responseMessage":""
            },
            "esbBody":[{';

    $string_end = '
        }],
        "esbSecurity":{
         "orgId":"1827",
         "batchSourceId":"",
         "lastUpdateLogin":"",
         "userId":"",
         "respId":"",
         "ledgerId":"",
         "respApplId":"",
         "batchSourceName":""
                }
             }
          }';

       $header      = json_decode(json_encode(DB::connection('omcargo')->table("TX_HDR_NOTA")->where('NOTA_ID', '=', $input["request"])->get()), TRUE);
       $str_header  = '
          "header":{
          "billerRequestId":"'.$header[0]['nota_no'].'",
          "orgId":"'.$header[0]['nota_org_id'].'",
          "trxNumber":"'.$header[0]['nota_no'].'",
          "trxNumberOrig":"",
          "trxNumberPrev":"",
          "trxTaxNumber":"",
          "trxDate":"'.$header[0]['nota_date'].'",
          "trxClass":"INV",
          "trxTypeId":"-1",
          "paymentReferenceNumber":"",
          "referenceNumber":"",
          "currencyCode":"'.$header[0]['nota_currency_code'].'",
          "currencyType":"",
          "currencyRate":"0",
          "currencyDate":null,
          "amount":"'.$header[0]['nota_amount'].'",
          "customerNumber":"'.$header[0]['nota_cust_id'].'",
          "customerClass":"",
          "billToCustomerId":"-1",
          "billToSiteUseId":"-1",
          "termId":null,
          "status":"P",
          "headerContext":"BRG",
          "headerSubContext":"BRG00",
          "startDate":null,
          "endDate":null,
          "terminal":"'.$header[0]['nota_terminal'].'",
          "vesselName":"'.$header[0]['nota_vessel_name'].'",
          "branchCode":"'.$header[0]['nota_branch_code'].'",
          "errorMessage":"",
          "apiMessage":"",
          "createdBy":"-1",
          "creationDate":"2019-10-15 22:15:08",
          "lastUpdatedBy":"-1",
          "lastUpdateDate":"2019-10-15 22:15:08",
          "lastUpdateLogin":"-1",
          "customerTrxIdOut":null,
          "processFlag":"",
          "attribute1":"PTKM00",
          "attribute2":"",
          "attribute3":"",
          "attribute4":"",
          "attribute5":"",
          "attribute6":"",
          "attribute7":"",
          "attribute8":"",
          "attribute9":"",
          "attribute10":"",
          "attribute11":"",
          "attribute12":"",
          "attribute13":"",
          "attribute14":"",
          "attribute15":"",
          "interfaceHeaderAttribute1":null,
          "interfaceHeaderAttribute2":"",
          "interfaceHeaderAttribute3":"MUAT/EXPORT",
          "interfaceHeaderAttribute4":"SINDO EAGLE",
          "interfaceHeaderAttribute5":"11/12",
          "interfaceHeaderAttribute6":"15-OCT-19",
          "interfaceHeaderAttribute7":"",
          "interfaceHeaderAttribute8":"",
          "interfaceHeaderAttribute9":"",
          "interfaceHeaderAttribute10":"false",
          "interfaceHeaderAttribute11":"",
          "interfaceHeaderAttribute12":"",
          "interfaceHeaderAttribute13":"",
          "interfaceHeaderAttribute14":"",
          "interfaceHeaderAttribute15":"",
          "customerAddress":"'.$header[0]['nota_cust_address'].'",
          "customerName":"'.$header[0]['nota_cust_name'].'",
          "sourceSystem":"ITOSJBI_NBM",
          "arStatus":"N",
          "sourceInvoice":"ITOSJBI_NBM",
          "arMessage":"",
          "customerNPWP":"'.$header[0]['nota_cust_npwp'].'",
          "perKunjunganFrom":null,
          "perKunjunganTo":null,
          "jenisPerdagangan":"",
          "docNum":"",
          "statusLunas":"Y",
          "tglPelunasan":"2019-10-15",
          "amountTerbilang":"",
          "ppnDipungutSendiri":"",
          "ppnDipungutPemungut":"",
          "ppnTidakDipungut":"",
          "ppnDibebaskan":"",
          "uangJaminan":"",
          "piutang":"",
          "sourceInvoiceType":"ITOS",
          "branchAccount":"100",
          "statusCetak":"",
          "statusKirimEmail":"",
          "amountDasarPenghasilan":"1390500",
          "amountMaterai":null,
          "ppn10Persen":"139050",
          "statusKoreksi":"",
          "tanggalKoreksi":null,
          "keteranganKoreksi":""},';

      $detail     = json_decode(json_encode(DB::connection('omcargo')->table("TX_DTL_NOTA")->where('NOTA_HDR_ID', '=', $input["request"])->get()), TRUE);
      foreach ($detail as $detail) {
      $str_detail= '
            {
            "billerRequestId":"'.$header[0]['nota_no'].'",
            "trxNumber":"010.804.19-60.011510",
            "lineId":null,
            "lineNumber":"1",
            "description":"'.$detail['dtl_line_desc'].'",
            "memoLineId":null,
            "glRevId":null,
            "lineContext":"",
            "taxFlag":"Y",
            "serviceType":"JBI RECEIVING PNP_M_1-2",
            "eamCode":"",
            "locationTerminal":"",
            "amount":"'.$detail['dtl_amout'].'",
            "taxAmount":"'.$detail['dtl_ppn'].'",
            "startDate":"'.$detail['dtl_create_date'].'",
            "endDate":"2019-10-23",
            "createdBy":"-1",
            "creationDate":"2019-10-15",
            "lastUpdatedBy":"-1",
            "lastUpdatedDate":"2019-10-15",
            "interfaceLineAttribute1":"",
            "interfaceLineAttribute2":null,
            "interfaceLineAttribute3":null,
            "interfaceLineAttribute4":null,
            "interfaceLineAttribute5":"",
            "interfaceLineAttribute6":"20 FCL DRY T",
            "interfaceLineAttribute7":"5",
            "interfaceLineAttribute8":"21600",
            "interfaceLineAttribute9":"4",
            "interfaceLineAttribute10":"",
            "interfaceLineAttribute11":"",
            "interfaceLineAttribute12":null,
            "interfaceLineAttribute13":"",
            "interfaceLineAttribute14":"",
            "interfaceLineAttribute15":"",
            "lineDoc":""
            }';
          }

    $string_json = $string_start.$str_header.'"lines":['.$str_detail.']'.$string_end;
    return $string_json;
  }
}
