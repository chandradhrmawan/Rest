<?php
namespace App\Helper;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helper\PlgConnectedExternalApps;

class PlgEInvo{

	private static function getJsonInvAR($arr){
		$hdr = static::getDtlInvAR($arr);
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
		$hdr = '"header": {
		        	"billerRequestId":"'.$arr['nota']['nota_req_no'].'",
		        	"orgId":"'.$arr['branch']['branch_org_id'].'",
		        	"trxNumber":"'.$arr['nota']['nota_no'].'",
		        	"trxNumberOrig":"",
		        	"trxNumberPrev":"",
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
		            "terminal": "-",
		            "vesselName": "'.$arr['nota']['nota_vessel_name'].'",
		            "branchCode": "'.$arr['nota']['nota_branch_code'].'",
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
		            "branchAccount": "'.$arr['nota']['nota_branch_account'].'",
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
        $lines = substr($lines, 0,-1);
        return $lines;
	}

	public static function sendInvProforma($arr){
		return ['Success' => true, 'sendInvProforma' => 'by pass dulu']; // by pass dulu
		$branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$arr['nota']['nota_branch_id'])->where('branch_code',$arr['nota']['nota_branch_code'])->get();
		if (count($branch) == 0) {
			return ['Success' =>false, 'response' => 'branch not found!'];
		}
		$branch = (array)$branch[0];
		$nota_date = $arr['nota']['nota_date'];
		$nota_date_noHour = date('Y-m-d', strtotime($arr['nota']['nota_date']));
		$sendArr = $arr;
		$sendArr['nDateWitHour'] = $nota_date;
		$sendArr['nDateNotHour'] = $nota_date_noHour;
		$sendArr['branch'] = $branch;
		$json = static::getJsonInvAR($sendArr);
		return json_decode($json, true);
		$json = json_encode(json_decode($json, true));
		$res = PlgConnectedExternalApps::sendRequestToExtJsonMet([
        	"user" => config('endpoint.esbPutInvoice.user'),
        	"pass" => config('endpoint.esbPutInvoice.pass'),
        	"target" => config('endpoint.esbPutInvoice.target'),
        	"json" => $json
        ]);
        $hsl = true;
        if ($res['response']['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
        	$hsl = false;
        }
		return ['Success' => $hsl, 'sendInvProforma' => $res];
	}

	public static function sendInvPay($arr){
		// di by passs dulu
		return ['Success' => true, 'response' => 'by passs'];
		// di by passs dulu
		$branch = DB::connection('mdm')->table('TM_BRANCH')->where('branch_id',$arr['nota']['nota_branch_id'])->where('branch_code',$arr['nota']['nota_branch_code'])->get();
		if (count($branch) == 0) {
			return ['Success' =>false, 'response' => 'branch not found!'];
		}
		$branch = (array)$branch[0];
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
					{
						"header": {
							"orgId": "'.$branch['branch_org_id'].'",
							"receiptNumber": "'.$arr['nota']['nota_no'].'",
							"receiptMethod": "BANK",
							"receiptAccount": "Mandiri IDR 120.00.4107201.3",
							"bankId": "105009",
							"customerNumber": "12777901",
							"receiptDate": "2019-11-28 20:15:08",
							"currencyCode": "IDR",
							"status": "P",
							"amount": "20000",
							"processFlag": "",
							"errorMessage": "",
							"apiMessage": "",
							"attributeCategory": "UPER",
							"referenceNum": "",
							"receiptType": "",
							"receiptSubType": "",
							"createdBy": "-1",
							"creationDate": "2019-11-28",
							"terminal": "",
							"attribute1": "",
							"attribute2": "",
							"attribute3": "",
							"attribute4": "",
							"attribute5": "",
							"attribute6": "",
							"attribute7": "",
							"attribute8": "",
							"attribute9": "",
							"attribute10": "",
							"attribute11": "",
							"attribute12": "",
							"attribute13": "",
							"attribute14": "BRG10",
							"attribute15": "",
							"statusReceipt": "N",
							"sourceInvoice": "BRG",
							"statusReceiptMsg": "",
							"invoiceNum": "",
							"amountOrig": null,
							"lastUpdateDate": "2019-11-28",
							"lastUpdateBy": "-1",
							"branchCode": "BTN",
							"branchAccount": "081",
							"sourceInvoiceType": "NPKBILLING",
							"remarkToBankId": "BANK_ACCOUNT_ID",
							"sourceSystem": "NPKBILLING",
							"comments": "Pembayaran uper",
							"cmsYn": "N",
							"tanggalTerima": null,
							"norekKoran": ""
						}
					}
				],
				"esbSecurity": {
					"orgId": "1822",
					"batchSourceId": "",
					"lastUpdateLogin": "",
					"userId": "",
					"respId": "",
					"ledgerId": "",
					"respApplId": "",
					"batchSourceName": ""
				}
			}
		}
		';

		$res = PlgConnectedExternalApps::sendRequestToExtJsonMet([ // kirim putReceipt
			"user" => config('endpoint.esbPutReceipt.user'),
			"pass" => config('endpoint.esbPutReceipt.pass'),
			"target" => config('endpoint.esbPutReceipt.target'),
			"json" => $json
		]);
	}
}