<?php
namespace App\Helper;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helper\PlgConnectedExternalApps;

class PlgEInvo{

	private static function getJsonInvAR($arr){
		$hdr = static::getHdrInvAR($arr);
		$lines = static::getDtlInvAR($arr);
		$json = '
		{
		    "arRequestDoc": {
		        "esbHeader": {
		        	"internalId": "",
		        	"externalId": "",
		        	"timestamp": "",
		        	"responseTimestamp": "",
		        	"responseCode": "",
		        	"responseMessage": ""
		        },
		        "esbBody": [
		            { '.$hdr.', "lines": ['.$lines.'] }
		        ],
		        "esbSecurity": {
		            "orgId": "'.$arr['branch']['branch_org_id'].'",
		            "batchSourceId": "",
		            "lastUpdateLogin": "",
		            "userId": "",
		            "respId": "",
		            "ledgerId": "",
		            "respApplId": "",
		            "batchSourceName": ""
		        }
		    }
		}';
		return json_encode(json_decode($json,true));
	}

	private static function getHdrInvAR($arr){
		return $hdr = '"header": {
		        	"billerRequestId":"'.$arr['nota']['nota_req_no'].'",
		        	"orgId":"'.$arr['branch']['branch_org_id'].'",
		        	"trxNumber":"'.$arr['nota']['nota_no'].'",
		        	"trxNumberOrig":"",
		        	"trxNumberPrev":"'.$arr['cancNotaFrom'].'",
		        	"trxTaxNumber":"",
		        	"trxDate":"'.$arr['nDateWitHour'].'",
		        	"trxClass":"INV",
		        	"trxTypeId":"-1",
		        	"paymentReferenceNumber":"",
		        	"referenceNumber":"",
		        	"currencyCode":"'.$arr['nota']['nota_currency_code'].'",
		            "currencyType": "",
		            "currencyRate": "0",
		            "currencyDate": null,
		            "amount": "'.$arr['nota']['nota_amount'].'",
		            "customerNumber": "'.$arr['nota']['nota_cust_id'].'",
		            "customerClass": "",
		            "billToCustomerId": "-1",
		            "billToSiteUseId": "-1",
		            "termId": null,
		            "status": "P",
		            "headerContext": "'.$arr['nota']['nota_context'].'",
		            "headerSubContext": "'.$arr['nota']['nota_sub_context'].'",
		            "startDate": null,
		            "endDate": null,
		            "terminal": "00",
		            "vesselName": "'.$arr['nota']['nota_vessel_name'].'",
		            "branchCode": "'.$arr['branch']['branch_code_erp'].'",
		            "errorMessage": "",
		            "apiMessage": "",
		            "createdBy": "-1",
		            "creationDate": "'.$arr['nDateWitHour'].'",
		            "lastUpdatedBy": "-1",
		            "lastUpdateDate": "'.$arr['nDateWitHour'].'",
		            "lastUpdateLogin": "-1",
		            "customerTrxIdOut": null,
		            "processFlag": "",
		            "attribute1": "'.$arr['nota']['nota_sub_context'].'",
		            "attribute2": "'.$arr['nota']['nota_cust_id'].'",
		            "attribute3": "'.$arr['nota']['nota_cust_name'].'",
		            "attribute4": "'.$arr['nota']['nota_cust_address'].'",
		            "attribute5": "'.$arr['nota']['nota_cust_npwp'].'",
		            "attribute6": "",
		            "attribute7": "",
		            "attribute8": "",
		            "attribute9": "",
		            "attribute10": "",
		            "attribute11": "",
		            "attribute12": "",
		            "attribute13": "",
		            "attribute14": "'.$arr['nota']['nota_no'].'",
		            "attribute15": "",
		            "interfaceHeaderAttribute1": "",
		            "interfaceHeaderAttribute2": "'.$arr['nota']['nota_vessel_name'].'",
		            "interfaceHeaderAttribute3": "",
		            "interfaceHeaderAttribute4": "",
		            "interfaceHeaderAttribute5": "",
		            "interfaceHeaderAttribute6": "",
		            "interfaceHeaderAttribute7": "",
		            "interfaceHeaderAttribute8": "",
		            "interfaceHeaderAttribute9": "",
		            "interfaceHeaderAttribute10": "",
		            "interfaceHeaderAttribute11": "",
		            "interfaceHeaderAttribute12": "",
		            "interfaceHeaderAttribute13": "",
		            "interfaceHeaderAttribute14": "",
		            "interfaceHeaderAttribute15": "",
		            "customerAddress": "'.$arr['nota']['nota_cust_address'].'",
		            "customerName": "'.$arr['nota']['nota_cust_name'].'",
		            "sourceSystem": "NPKSBILLING",
		            "arStatus": "N",
		            "sourceInvoice": "'.$arr['nota']['nota_context'].'",
		            "arMessage": "",
		            "customerNPWP": "'.$arr['nota']['nota_cust_npwp'].'",
		            "perKunjunganFrom": null,
		            "perKunjunganTo": null,
		            "jenisPerdagangan": "",
		            "docNum": "",
		            "statusLunas": "",
		            "tglPelunasan": "'.$arr['nDateNotHour'].'",
		            "amountTerbilang": "",
		            "ppnDipungutSendiri": "'.$arr['nota']['nota_ppn'].'",
		            "ppnDipungutPemungut": "",
		            "ppnTidakDipungut": "",
		            "ppnDibebaskan": "",
		            "uangJaminan": "",
		            "piutang": "'.$arr['nota']['nota_amount'].'",
		            "sourceInvoiceType": "'.$arr['nota']['nota_context'].'",
		            "branchAccount": "'.$arr['branch']['branch_account_erp'].'",
		            "statusCetak": "",
		            "statusKirimEmail": "",
		            "amountDasarPenghasilan": "'.$arr['nota']['nota_dpp'].'",
		            "amountMaterai": null,
		            "ppn10Persen": "'.$arr['nota']['nota_ppn'].'",
		            "statusKoreksi": "",
		            "tanggalKoreksi": null,
		            "keteranganKoreksi": ""
		        }';
	}

	private static function getDtlInvAR($arr){
		$lines = '';
		$getNotaDtl = DB::connection('omuster')->table('TX_DTL_NOTA')->where('nota_hdr_id',$arr['nota']['nota_id'])->get();
		foreach ($getNotaDtl as $list) {
			$lines .= '
			{
				"billerRequestId": "'.$arr['nota']['nota_req_no'].'",
				"trxNumber": "'.$arr['nota']['nota_no'].'",
				"lineId": null,
				"lineNumber": "'.$list->dtl_line.'",
				"description": "'.$list->dtl_service_type.'",
				"memoLineId": null,
				"glRevId": null,
				"lineContext": "",
				"taxFlag": "Y",
				"serviceType": "'.$list->dtl_line_desc.'",
				"eamCode": "",
				"locationTerminal": "",
				"amount": "'.$list->dtl_dpp.'",
				"taxAmount": "'.$list->dtl_ppn.'",
				"startDate": "'.$arr['nDateNotHour'].'",
				"endDate": "'.$arr['nDateNotHour'].'",
				"createdBy": "-1",
				"creationDate": "'.$arr['nDateNotHour'].'",
				"lastUpdatedBy": "-1",
				"lastUpdatedDate": "'.$arr['nDateNotHour'].'",
				"interfaceLineAttribute1": "",
				"interfaceLineAttribute2": "'.$list->dtl_service_type.'",
				"interfaceLineAttribute3": "",
				"interfaceLineAttribute4": "",
				"interfaceLineAttribute5": "",
				"interfaceLineAttribute6": "",
				"interfaceLineAttribute7": "",
				"interfaceLineAttribute8": "",
				"interfaceLineAttribute9": "",
				"interfaceLineAttribute10": "",
				"interfaceLineAttribute11": "",
				"interfaceLineAttribute12": "",
				"interfaceLineAttribute13": "'.$list->dtl_qty.'",
				"interfaceLineAttribute14": "'.$list->dtl_unit_name.'",
				"interfaceLineAttribute15": "",
				"lineDoc": ""
			},';
		}
        return $lines = substr($lines, 0,-1);
	}

	public static function sendInvAR($arr){
		$json = static::getJsonInvAR($arr);
		$res = PlgConnectedExternalApps::sendRequestToExtJsonMet([
        	"user" => config('endpoint.esbInvoicePutAR.user'),
        	"pass" => config('endpoint.esbInvoicePutAR.pass'),
        	"target" => config('endpoint.esbInvoicePutAR.target'),
        	"json" => $json
        ]);
        $hsl = true;
        if ($res['response']['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
        	$hsl = false;
        }
		$res['request']['json'] = json_decode($res['request']['json'],true);
		return ['Success' => $hsl, 'sendInvProformaAR' => $res];
	}

	private static function sendInvReceipt($arr){
		$getTsNota = DB::connection('mdm')->table('TS_NOTA')->where([
			'branch_id' => $arr['nota']['nota_branch_id'],
			'branch_code' => $arr['nota']['nota_branch_code'],
			'nota_id' => $arr['nota']['nota_group_id']
		])->first();
		$json ='
			{
				"arRequestDoc":{
					"esbHeader":{
						"internalId":"",
						"externalId":"",
						"timestamp":"",
						"responseTimestamp":"",
						"responseCode":"",
						"responseMessage":""
						},
						"esbBody":[
						{
							"header":{
								"orgId":"'.$arr['branch']['branch_org_id'].'",
								"receiptNumber":"'.$arr['nota']['nota_no'].'",
								"receiptMethod":"BANK",
								"receiptAccount":"'.$arr['payment']['pay_account_name'].' '.$arr['payment']['pay_bank_code'].' '.$arr['payment']['pay_account_no'].'",
								"bankId":"'.$arr['bank']['bank_id'].'",
								"customerNumber":"'.$arr['payment']['pay_cust_id'].'",
								"receiptDate":"'.date('Y-m-d H:i:s', strtotime($arr['payment']['pay_date'])).'",
								"currencyCode":"'.$arr['nota']['nota_currency_code'].'",
								"status":"P",
								"amount":"'.$arr['payment']['pay_amount'].'",
								"processFlag":"",
								"errorMessage":"",
								"apiMessage":"",
								"attributeCategory":"",
								"referenceNum":"",
								"receiptType":"",
								"receiptSubType":"",
								"createdBy":"-1",
								"creationDate":"'.date('Y-m-d', strtotime($arr['payment']['pay_create_date'])).'",
								"terminal":"",
								"attribute1":"'.$arr['nota']['nota_no'].'",
								"attribute2":"'.$arr['nota']['nota_cust_id'].'",
								"attribute3":"'.$arr['nota']['nota_cust_name'].'",
								"attribute4":"'.$arr['nota']['nota_cust_address'].'",
								"attribute5":"'.$arr['nota']['nota_cust_npwp'].'",
								"attribute6":"",
								"attribute7":"'.$arr['nota']['nota_currency_code'].'",
								"attribute8":"'.$arr['nota']['nota_vessel_name'].'",
								"attribute9":"",
								"attribute10":"",
								"attribute11":"",
								"attribute12":"",
								"attribute13":"",
								"attribute14":"'.$arr['nota']['nota_sub_context'].'",
								"attribute15":"",
								"statusReceipt":"N",
								"sourceInvoice":"'.$getTsNota->nota_context.'",
								"statusReceiptMsg":"",
								"invoiceNum":"",
								"amountOrig":null,
								"lastUpdateDate":"'.date('Y-m-d', strtotime($arr['payment']['pay_create_date'])).'",
								"lastUpdateBy":"-1",
								"branchCode":"'.$arr['branch']['branch_code_erp'].'",
								"branchAccount":"'.$arr['branch']['branch_account_erp'].'",
								"sourceInvoiceType":"NPKSBILLING",
								"remarkToBankId":"BANK_ACCOUNT_ID",
								"sourceSystem":"NPKSBILLING",
								"comments":"'.$arr['payment']['pay_note'].'",
								"cmsYn":"N",
								"tanggalTerima":null,
								"norekKoran":""
							}
						}
						],
						"esbSecurity":{
							"orgId":"'.$arr['branch']['branch_org_id'].'",
							"batchSourceId":"",
							"lastUpdateLogin":"",
							"userId":"",
							"respId":"",
							"ledgerId":"",
							"respApplId":"",
							"batchSourceName":""
						}
					}
				}
		';

		$json = json_encode(json_decode($json,true));

		return $res = PlgConnectedExternalApps::sendRequestToExtJsonMet([ // kirim putReceipt
			"user" => config('endpoint.esbInvoicePutReceipt.user'),
			"pass" => config('endpoint.esbInvoicePutReceipt.pass'),
			"target" => config('endpoint.esbInvoicePutReceipt.target'),
			"json" => $json
		]);
	}

	private static function sendInvApply($arr){
		$json ='
			{
				"arRequestDoc":{
					"esbHeader":{
						"internalId":"",
						"externalId":"",
						"timestamp":"",
						"responseTimestamp":"",
						"responseCode":"",
						"responseMessage":""
						},
						"esbBody":[
						{
							"header":{
								"paymentCode":"'.$arr['nota']['nota_no'].'",
								"trxNumber":"'.$arr['nota']['nota_no'].'",
								"orgId":"'.$arr['branch']['branch_org_id'].'",
								"amountApplied":"'.$arr['payment']['pay_amount'].'",
								"cashReceiptId":null,
								"customerTrxId":"'.$arr['payment']['pay_cust_id'].'",
								"paymentScheduleId":null,
								"bankId":"'.$arr['bank']['bank_id'].'",
								"receiptSource":"NPKSBILLING",
								"legacySystem":"INVOICE",
								"statusTransfer":"N",
								"errorMessage":null,
								"requestIdApply":null,
								"createdBy":"-1",
								"creationDate":"'.date('Y-m-d', strtotime($arr['payment']['pay_create_date'])).'",
								"lastUpdateBy":"-1",
								"lastUpdateDate":"'.date('Y-m-d', strtotime($arr['payment']['pay_create_date'])).'",
								"amountPaid":"'.$arr['payment']['pay_amount'].'",
								"epay":"N"
							}
						}
						],
						"esbSecurity":{
							"orgId":"'.$arr['branch']['branch_org_id'].'",
							"batchSourceId":"",
							"lastUpdateLogin":"",
							"userId":"",
							"respId":"",
							"ledgerId":"",
							"respApplId":"",
							"batchSourceName":""
						}
					}
				}
		';

		$json = json_encode(json_decode($json,true));

		return $res = PlgConnectedExternalApps::sendRequestToExtJsonMet([ // kirim putReceipt
			"user" => config('endpoint.esbInvoicePutApply.user'),
			"pass" => config('endpoint.esbInvoicePutApply.pass'),
			"target" => config('endpoint.esbInvoicePutApply.target'),
			"json" => $json
		]);
	}

	public static function sendInvPay($arr){
		// return [ "Success" => true, "sendInvAR" => 'by pass', "sendInvPutReceipt" => 'by pass', "sendInvPutApply" => 'by pass' ];
		$sendInvAR = null;
		$sendInvPutReceipt = null;
		$sendInvPutApply = null;

		$arr['cancNotaFrom'] = null;
		if (!empty($arr['reqCanc'])) {
			$arr['cancNotaFrom'] = DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_req_no',$arr['reqCanc']['cancelled_req_no'])->first();
			$arr['cancNotaFrom'] = (array)$arr['cancNotaFrom'];
			$arr['cancNotaFrom'] = $arr['cancNotaFrom']['nota_no'];
		}

		$branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$arr['nota']['nota_branch_id'])->where('branch_code',$arr['nota']['nota_branch_code'])->first();
		if (empty($branch)) {
			return ['Success' =>false, 'response' => 'branch not found!'];
		}
		$branch = (array)$branch;
		$arr['branch'] = $branch;
		$bank = DB::connection('mdm')->table('TM_BANK')->where([
			'bank_code' => $arr['payment']['pay_bank_code'],
			'branch_id' => $arr['payment']['pay_branch_id'],
			'branch_code' => $arr['payment']['pay_branch_code']
		])->first();
		if (empty($bank)) {
			return ['Success' =>false, 'response' => 'bank not found!'];
		}
		$bank = (array)$bank;
		$arr['bank'] = $bank;

		$nota_date = $arr['nota']['nota_date'];
		$nota_date_noHour = date('Y-m-d', strtotime($arr['nota']['nota_date']));
		$arr['nDateWitHour'] = $nota_date;
		$arr['nDateNotHour'] = $nota_date_noHour;

		if ($arr['nota']['nota_flag_einv'] == 0) {
			$sendInvAR = static::sendInvAR($arr);
			if ($sendInvAR['Success'] == false) {
				return ['Success' =>false, 'response' => 'fail send sendInvAR!', "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply ];
			}else{
				DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_id',$arr['nota']['nota_id'])->update(['nota_flag_einv'=>1]);
				$arr['nota']['nota_flag_einv'] = 1;
			}
		}
		if ($arr['nota']['nota_flag_einv'] == 1) {
			$sendInvPutReceipt = static::sendInvReceipt($arr);
			$sendInvPutReceipt['request']['json'] = json_decode($sendInvPutReceipt['request']['json'],true);
			if ($sendInvPutReceipt['response']['arResponseDoc']['esbBody'][0]['errorCode'] != 'S') {
				return ['Success' =>false, 'response' => 'fail send sendInvPutReceipt!', "sendInvAR" =>$sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
			}else{
				DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_id',$arr['nota']['nota_id'])->update(['nota_flag_einv'=>2]);
				$arr['nota']['nota_flag_einv'] = 2;
			}
		}
		if ($arr['nota']['nota_flag_einv'] == 2) {
			$sendInvPutApply = static::sendInvApply($arr);
			$sendInvPutApply['request']['json'] = json_decode($sendInvPutApply['request']['json'],true);
			if ($sendInvPutApply['response']['arResponseDoc']['esbBody'][0]['errorCode'] != 'S') {
				return ['Success' =>false, 'response' => 'fail send sendInvPutApply!', "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
			}else{
				DB::connection('omuster')->table('TX_HDR_NOTA')->where('nota_id',$arr['nota']['nota_id'])->update(['nota_flag_einv'=>3]);
				$arr['nota']['nota_flag_einv'] = 3;
			}
		}
		return [ "Success" => true, "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply ];
	}
}
