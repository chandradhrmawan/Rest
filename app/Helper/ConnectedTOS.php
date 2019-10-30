<?php

namespace App\Helper;

use Illuminate\Http\Request;
use GuzzleHttp\Client;
use App\Models\OmCargo\TxHdrBm;
use Illuminate\Support\Facades\DB;

class ConnectedTOS{

	public static function realTos($input){
		$count = DB::connection('omcargo')->table('TX_HDR_REALISASI')->where('REAL_REQ_NO', $input['req_no'])->count();
    if ($count > 0) {
      return ['result' => "Fail, realisation has been created!", "Success" => false];
    }
    $req = TxHdrBm::where('BM_NO', $input['req_no'])->first();
    if (empty($req)) {
      return ['result' => "Fail, request not found!", "Success" => false];
    }
    $ckp = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id', $req->bm_id)->where('dtl_pkg_id', 4)->get();

    if (count($ckp) > 0) {
      foreach ($ckp as $list) {
        DB::connection('omcargo')->table('TX_REAL_TOS')->where('idvsb', $req->bm_vvd_id)->where('bl_no', $list->dtl_bm_bl)->delete();
        $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/searchRealisasi";
        $string_json = '{
          "searchRealisasiRequest": {
            "esbHeader": { },
              "esbBody": {
                "vvd": "'.$req->bm_vvd_id.'",
                "noblss": "'.$list->dtl_bm_bl.'"
              }
            }
          }';

        $username="npk_billing";
        $password ="npk_billing";
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

        $response = json_decode(json_encode($res->getBody()->getContents()));
        $response = json_decode($response, true);
        $response = $response['esbBody']['results'][0];

        if (!empty($response['idVsbVoyage'])) {
          $newreal = $response['esbBody']['results'][0];
          DB::connection('omcargo')->table('TX_REAL_TOS')->insert([
             'idvsb'=> $newreal['idVsbVoyage'],
             'bl_no'=> $newreal['blNumber'],
             'package'=> $newreal['packageName'],
             'is_hz'=> $newreal['hz'],
             'is_disturb'=> $newreal['disturb'],
             'ei'=> $newreal['ei'],
             'tl'=> $newreal['tl'],
             'total_ton'=> $newreal['ttlTon'],
             'total_cubic'=> $newreal['ttlCubic'],
             'oi'=> $newreal['oi'],
             'rpact'=> $newreal['rpact'],
             'omcargoid'=> $newreal['omCargoid']
          ]);
        }
      }
    }

    return [
      'req_header' => $req,
      'req_detil' => DB::connection('omcargo')->select(DB::raw("select * from TX_DTL_BM A left join TX_REAL_TOS B on B.BL_NO = A.DTL_BM_BL where A.HDR_BM_ID = ".$req->bm_id)),
      'result' => "Success, get data real from tos!"
    ];
	}

  public static function sendRequestBooking($input){
    $header = TxHdrBm::where('bm_no',$input)->first();
    $detil = DB::connection('omcargo')->table('TX_DTL_BM')->where('hdr_bm_id',$header->bm_id)->get();
    static::sendRealBM($head, $detil);
  }

  private static function sendRealBM($head, $detil){
    $vParam = $head->bm_no.'^'.$head->bm_cust_name.'^'.$head->bm_cust_id.'^'.$head->bm_cust_npwp.'^'.$head->bm_vessel_name.'^'.$head->bm_eta.'^'.$head->bm_open_stack.'^'.$head->bm_voyin.'^'.$head->bm_voyout.'^'.$head->bm_closing_time.'^BONGKAR MUAT^^^^^^^^^^^^'.$head->bm_etd.'^0^'.$head->bm_vvd_id.'^'.$head->bm_trade_type.'^^^';
    $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createBookingHeader";
    $string_json = '{
      "createBookingHeaderInterfaceRequest": {
        "esbHeader": {
          "externalId": "2",
          "timestamp": "2"
        },
        "esbBody": {
          "vParam": "'.$vParam.'",
          "vId": "-",
          "vReqNo": "-",
          "vBlNo": "-"
        }
      }
    }';

    $username="npk_billing";
    $password ="npk_billing";
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
      // return $e->getResponse();
    }
    foreach ($detil as $list) {
      $vParam = $list->dtl_cmdty_name.'^'.$list->dtl_cont_type.'^^^^^^'.$list->dtl_qty.'^0^^';
      $endpoint_url="http://10.88.48.57:5555/restv2/npkBilling/createBookingDetail";
      $string_json = '{
        "createBookingDetailInterfaceRequest": {
          "esbHeader": {
            "externalId": "2",
            "timestamp": "2"
            },
            "esbBody": {
              "vParam": "'.$vParam.'",
              "vId": "",
              "vIdHeader": ""
            }
          }
        }';

        $username="npk_billing";
        $password ="npk_billing";
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
      // return $e->getResponse();
        }
    }
  }

}