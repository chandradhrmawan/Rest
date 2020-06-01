<?php

namespace App\Helper\Jbi;

use Illuminate\Http\Request;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use App\Helper\Jbi\JbiConnectedExternalApps;

class JbiEInvo
{

	private static function getJsonInvAR($arr)
	{
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
		            { ' . $hdr . ', "lines": [' . $lines . '] }
		        ],
		        "esbSecurity": {
		            "orgId": "' . $arr['branch']['branch_org_id'] . '",
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
		return json_encode(json_decode($json, true));
	}

	private static function getHdrInvAR($arr)
	{
		$e_materai = 0;
		if ($arr['nota']['nota_amount'] >= 250000 && $arr['nota']['nota_amount'] <= 1000000) {
			$e_materai = 3000;
		} else if ($arr['nota']['nota_amount'] > 1000000) {
			$e_materai = 6000;
		}
		$notaAmount = (int) $arr['nota']['nota_amount'] + $e_materai;
		$terbilang  = static::terbilang($notaAmount);
		return $hdr = '"header": {
		        	"billerRequestId":"' . $arr['nota']['nota_req_no'] . '",
		        	"orgId":"' . $arr['branch']['branch_org_id'] . '",
		        	"trxNumber":"' . $arr['nota']['nota_no'] . '",
		        	"trxNumberOrig":"",
		        	"trxNumberPrev":"' . $arr['cancNotaFrom'] . '",
		        	"trxTaxNumber":"",
		        	"trxDate":"' . $arr['nDateWitHour'] . '",
		        	"trxClass":"INV",
		        	"trxTypeId":"-1",
		        	"paymentReferenceNumber":"",
		        	"referenceNumber":"",
		        	"currencyCode":"' . $arr['nota']['nota_currency_code'] . '",
		            "currencyType": "",
		            "currencyRate": "0",
		            "currencyDate": null,
		            "amount": "' . $notaAmount . '",
		            "customerNumber": "' . $arr['nota']['nota_cust_id'] . '",
		            "customerClass": "",
		            "billToCustomerId": "-1",
		            "billToSiteUseId": "-1",
		            "termId": null,
		            "status": "P",
		            "headerContext": "' . $arr['nota']['nota_context'] . '",
		            "headerSubContext": "' . $arr['nota']['nota_sub_context'] . '",
		            "startDate": null,
		            "endDate": null,
		            "terminal": "00",
		            "vesselName": "' . $arr['nota']['nota_vessel_name'] . '",
		            "branchCode": "' . $arr['branch']['branch_code_erp'] . '",
		            "errorMessage": "",
		            "apiMessage": "",
		            "createdBy": "-1",
		            "creationDate": "' . $arr['nDateWitHour'] . '",
		            "lastUpdatedBy": "-1",
		            "lastUpdateDate": "' . $arr['nDateWitHour'] . '",
		            "lastUpdateLogin": "-1",
		            "customerTrxIdOut": null,
		            "processFlag": "",
		            "attribute1": "' . $arr['nota']['nota_sub_context'] . '",
		            "attribute2": "' . $arr['nota']['nota_cust_id'] . '",
		            "attribute3": "' . $arr['nota']['nota_cust_name'] . '",
		            "attribute4": "' . $arr['nota']['nota_cust_address'] . '",
		            "attribute5": "' . $arr['nota']['nota_cust_npwp'] . '",
		            "attribute6": "",
		            "attribute7": "",
		            "attribute8": "",
		            "attribute9": "",
		            "attribute10": "",
		            "attribute11": "",
		            "attribute12": "",
		            "attribute13": "",
		            "attribute14": "' . $arr['nota']['nota_no'] . '",
		            "attribute15": "",
		            "interfaceHeaderAttribute1": "' . $arr['nota']['nota_req_no'] . '",
		            "interfaceHeaderAttribute2": "' . $arr['nota']['nota_vessel_name'] . '",
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
		            "customerAddress": "' . $arr['nota']['nota_cust_address'] . '",
		            "customerName": "' . $arr['nota']['nota_cust_name'] . '",
		            "sourceSystem": "NPKSBILLING",
		            "arStatus": "N",
		            "sourceInvoice": "' . $arr['nota']['nota_context'] . '",
		            "arMessage": "",
		            "customerNPWP": "' . $arr['nota']['nota_cust_npwp'] . '",
		            "perKunjunganFrom": null,
		            "perKunjunganTo": null,
		            "jenisPerdagangan": "",
		            "docNum": "",
		            "statusLunas": "Y",
		            "tglPelunasan": "' . $arr['nDateNotHour'] . '",
		            "amountTerbilang": "' . $terbilang . +' Rupiah",
		            "ppnDipungutSendiri": "' . $arr['nota']['nota_ppn'] . '",
		            "ppnDipungutPemungut": "",
		            "ppnTidakDipungut": "",
		            "ppnDibebaskan": "",
		            "uangJaminan": "",
		            "piutang": "' . $arr['nota']['nota_amount'] . '",
		            "sourceInvoiceType": "' . $arr['nota']['nota_context'] . '",
		            "branchAccount": "' . $arr['branch']['branch_account_erp'] . '",
		            "statusCetak": "",
		            "statusKirimEmail": "",
		            "amountDasarPeng_ilcshasilan": "' . $arr['nota']['nota_dpp'] . '",
		            "amountMaterai": "' . $e_materai . '",
		            "ppn10Persen": "' . $arr['nota']['nota_ppn'] . '",
		            "statusKoreksi": "",
		            "tanggalKoreksi": null,
		            "keteranganKoreksi": ""
				}';
	}

	private static function getDangerous($no_container)
	{
		$getDG = DB::connection('omuster_ilcs')
			->table('TX_DTL_REC')
			->select('rec_dtl_cont_danger')
			->where('rec_dtl_cont', $no_container)
			->first();

		return $getDG;
	}

	private static function getDtlInvAR($arr)
	{
		$lines = '';
		$getNotaDtl = DB::connection('omuster_ilcs')->table('TX_DTL_NOTA')->where('nota_hdr_id', $arr['nota']['nota_id'])->get();
		$getDate = DB::connection('eng_ilcs')->table('TX_TEMP_TARIFF_HDR')
			->leftjoin('TX_TEMP_TARIFF_DTL', 'TX_TEMP_TARIFF_HDR.TEMP_HDR_ID = TX_TEMP_TARIFF_DTL.TEMP_HDR_ID')
			->select('TX_TEMP_TARIFF_DTL.DATE_IN', 'TX_TEMP_TARIFF_DTL.DATE_OUT')->where('TX_TEMP_TARIFF_HDR.BOOKING_NUMBER', $arr['nota']['nota_req_no'])->limit(1)->get();
		foreach ($getNotaDtl as $list) {
			$getDG = static::getDangerous($list->dtl_bl);
			$lines .= '
			{
				"billerRequestId": "' . $arr['nota']['nota_req_no'] . '",
				"trxNumber": "' . $arr['nota']['nota_no'] . '",
				"lineId": null,
				"lineNumber": "' . $list->dtl_line . '",
				"description": "' . $list->dtl_service_type . '",
				"memoLineId": null,
				"glRevId": null,
				"lineContext": "",
				"taxFlag": "Y",
				"serviceType": "' . $list->dtl_line_desc . '",
				"eamCode": "",
				"locationTerminal": "",
				"amount": "' . $list->dtl_dpp . '",
				"taxAmount": "' . $list->dtl_ppn . '",
				"startDate": "' . $arr['nDateNotHour'] . '",
				"endDate": "' . $arr['nDateNotHour'] . '",
				"createdBy": "-1",
				"creationDate": "' . $arr['nDateNotHour'] . '",
				"lastUpdatedBy": "-1",
				"lastUpdatedDate": "' . $arr['nDateNotHour'] . '",
				"interfaceLineAttribute1": "",
				"interfaceLineAttribute2": "' . $getDate[0]->date_in . '",
				"interfaceLineAttribute3": "' . $getDate[0]->date_out . '",
				"interfaceLineAttribute4": "' . $list->dtl_unit_name . '",
				"interfaceLineAttribute5": "",
				"interfaceLineAttribute6": "' . $list->dtl_masa . '",
				"interfaceLineAttribute7": "' . $list->dtl_tariff . '",
				"interfaceLineAttribute8": "",
				"interfaceLineAttribute9": "",
				"interfaceLineAttribute10": "' . $list->dtl_cont_size . '",
				"interfaceLineAttribute11": "' . $list->dtl_cont_type . '",
				"interfaceLineAttribute12": "' . $list->dtl_cont_status . '",
				"interfaceLineAttribute13": "' . (isset($getDG->rec_dtl_cont_danger) ? $getDG->rec_dtl_cont_danger : 'N') . '",
				"interfaceLineAttribute14": "",
				"interfaceLineAttribute15": "",
				"lineDoc": ""
			},';
		}
		return $lines = substr($lines, 0, -1);
	}

	public static function sendInvAR($arr)
	{
		$json = static::getJsonInvAR($arr);
		$res = JbiConnectedExternalApps::sendRequestToExtJsonMet([
			"user" => config('endpoint.esbInvoicePutAR.user'),
			"pass" => config('endpoint.esbInvoicePutAR.pass'),
			"target" => config('endpoint.esbInvoicePutAR.target'),
			"json" => $json
		]);
		$hsl = true;
		if ($res['response']['arResponseDoc']['esbBody'][0]['errorCode'] == 'F') {
			$hsl = false;
		}
		$res['request']['json'] = json_decode($res['request']['json'], true);
		return ['Success' => $hsl, 'sendInvProformaAR' => $res];
	}

	private static function sendInvReceipt($arr)
	{
		$getTsNota = DB::connection('mdm_ilcs')->table('TS_NOTA')->where([
			'branch_id' => $arr['nota']['nota_branch_id'],
			'branch_code' => $arr['nota']['nota_branch_code'],
			'nota_id' => $arr['nota']['nota_group_id']
		])->first();
		$e_materai = 0;
		if ($arr['nota']['nota_amount'] >= 250000 && $arr['nota']['nota_amount'] <= 1000000) {
			$e_materai = 3000;
		} else if ($arr['nota']['nota_amount'] > 1000000) {
			$e_materai = 6000;
		}
		$paymentAmount = (int) ($arr['payment']['pay_amount']) + $e_materai;
		$json = '
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
								"orgId":"' . $arr['branch']['branch_org_id'] . '",
								"receiptNumber":"' . $arr['nota']['nota_no'] . '",
								"receiptMethod":"BANK",
								"receiptAccount":"' . $arr['payment']['pay_account_name'] . ' ' . $arr['payment']['pay_bank_code'] . ' ' . $arr['payment']['pay_account_no'] . '",
								"bankId":"' . $arr['bank']['bank_id'] . '",
								"customerNumber":"' . $arr['payment']['pay_cust_id'] . '",
								"receiptDate":"' . date('Y-m-d H:i:s', strtotime($arr['payment']['pay_date'])) . '",
								"currencyCode":"' . $arr['nota']['nota_currency_code'] . '",
								"status":"P",
								"amount":"' . $paymentAmount . '",
								"processFlag":"",
								"errorMessage":"",
								"apiMessage":"",
								"attributeCategory":"",
								"referenceNum":"",
								"receiptType":"",
								"receiptSubType":"",
								"createdBy":"-1",
								"creationDate":"' . date('Y-m-d', strtotime($arr['payment']['pay_create_date'])) . '",
								"terminal":"",
								"attribute1":"' . $arr['nota']['nota_no'] . '",
								"attribute2":"' . $arr['nota']['nota_cust_id'] . '",
								"attribute3":"' . $arr['nota']['nota_cust_name'] . '",
								"attribute4":"' . $arr['nota']['nota_cust_address'] . '",
								"attribute5":"' . $arr['nota']['nota_cust_npwp'] . '",
								"attribute6":"",
								"attribute7":"' . $arr['nota']['nota_currency_code'] . '",
								"attribute8":"' . $arr['nota']['nota_vessel_name'] . '",
								"attribute9":"",
								"attribute10":"",
								"attribute11":"",
								"attribute12":"",
								"attribute13":"",
								"attribute14":"' . $arr['nota']['nota_sub_context'] . '",
								"attribute15":"",
								"statusReceipt":"N",
								"sourceInvoice":"' . $getTsNota->nota_context . '",
								"statusReceiptMsg":"",
								"invoiceNum":"",
								"amountOrig":null,
								"lastUpdateDate":"' . date('Y-m-d', strtotime($arr['payment']['pay_create_date'])) . '",
								"lastUpdateBy":"-1",
								"branchCode":"' . $arr['branch']['branch_code_erp'] . '",
								"branchAccount":"' . $arr['branch']['branch_account_erp'] . '",
								"sourceInvoiceType":"NPKSBILLING",
								"remarkToBankId":"BANK_ACCOUNT_ID",
								"sourceSystem":"NPKSBILLING",
								"comments":"' . $arr['payment']['pay_note'] . '",
								"cmsYn":"N",
								"tanggalTerima":null,
								"norekKoran":""
							}
						}
						],
						"esbSecurity":{
							"orgId":"' . $arr['branch']['branch_org_id'] . '",
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

		$json = json_encode(json_decode($json, true));

		return $res = JbiConnectedExternalApps::sendRequestToExtJsonMet([ // kirim putReceipt
			"user" => config('endpoint.esbInvoicePutReceipt.user'),
			"pass" => config('endpoint.esbInvoicePutReceipt.pass'),
			"target" => config('endpoint.esbInvoicePutReceipt.target'),
			"json" => $json
		]);
	}

	private static function sendInvApply($arr)
	{
		$e_materai = 0;
		if ($arr['nota']['nota_amount'] >= 250000 && $arr['nota']['nota_amount'] <= 1000000) {
			$e_materai = 3000;
		} else if ($arr['nota']['nota_amount'] > 1000000) {
			$e_materai = 6000;
		}
		$paymentAmount = (int) ($arr['payment']['pay_amount']) + $e_materai;
		$json = '
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
								"paymentCode":"' . $arr['nota']['nota_no'] . '",
								"trxNumber":"' . $arr['nota']['nota_no'] . '",
								"orgId":"' . $arr['branch']['branch_org_id'] . '",
								"amountApplied":"' . $paymentAmount . '",
								"cashReceiptId":null,
								"customerTrxId":"' . $arr['payment']['pay_cust_id'] . '",
								"paymentScheduleId":null,
								"bankId":"' . $arr['bank']['bank_id'] . '",
								"receiptSource":"NPKSBILLING",
								"legacySystem":"INVOICE",
								"statusTransfer":"N",
								"errorMessage":null,
								"requestIdApply":null,
								"createdBy":"-1",
								"creationDate":"' . date('Y-m-d', strtotime($arr['payment']['pay_create_date'])) . '",
								"lastUpdateBy":"-1",
								"lastUpdateDate":"' . date('Y-m-d', strtotime($arr['payment']['pay_create_date'])) . '",
								"amountPaid":"' . $arr['payment']['pay_amount'] . '",
								"epay":"N"
							}
						}
						],
						"esbSecurity":{
							"orgId":"' . $arr['branch']['branch_org_id'] . '",
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

		$json = json_encode(json_decode($json, true));

		return $res = JbiConnectedExternalApps::sendRequestToExtJsonMet([ // kirim putReceipt
			"user" => config('endpoint.esbInvoicePutApply.user'),
			"pass" => config('endpoint.esbInvoicePutApply.pass'),
			"target" => config('endpoint.esbInvoicePutApply.target'),
			"json" => $json
		]);
	}

	public static function sendInvPay($arr)
	{
		// return [ "Success" => true, "sendInvAR" => 'by pass', "sendInvPutReceipt" => 'by pass', "sendInvPutApply" => 'by pass' ];
		$sendInvAR = null;
		$sendInvPutReceipt = null;
		$sendInvPutApply = null;

		$arr['cancNotaFrom'] = null;
		if (!empty($arr['reqCanc'])) {
			$arr['cancNotaFrom'] = DB::connection('omuster_ilcs')->table('TX_HDR_NOTA')->where('nota_req_no', $arr['reqCanc']['cancelled_req_no'])->first();
			$arr['cancNotaFrom'] = (array) $arr['cancNotaFrom'];
			$arr['cancNotaFrom'] = $arr['cancNotaFrom']['nota_no'];
		}

		$branch = DB::connection('mdm_ilcs')->table('TM_BRANCH')->where('branch_id', $arr['nota']['nota_branch_id'])->where('branch_code', $arr['nota']['nota_branch_code'])->first();
		if (empty($branch)) {
			return ['Success' => false, 'response' => 'branch not found!'];
		}
		$branch = (array) $branch;
		$arr['branch'] = $branch;
		$bank = DB::connection('mdm_ilcs')->table('TM_BANK')->where([
			'bank_code' => $arr['payment']['pay_bank_code'],
			'branch_id' => $arr['payment']['pay_branch_id'],
			'branch_code' => $arr['payment']['pay_branch_code']
		])->first();
		if (empty($bank)) {
			return ['Success' => false, 'response' => 'bank not found!'];
		}
		$bank = (array) $bank;
		$arr['bank'] = $bank;

		$nota_date = $arr['nota']['nota_date'];
		$nota_date_noHour = date('Y-m-d', strtotime($arr['nota']['nota_date']));
		$arr['nDateWitHour'] = $nota_date;
		$arr['nDateNotHour'] = $nota_date_noHour;

		if ($arr['nota']['nota_flag_einv'] == 0) {
			$sendInvAR = static::sendInvAR($arr);
			if ($sendInvAR['Success'] == false) {
				return ['Success' => false, 'response' => 'fail send sendInvAR!', "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
			} else {
				DB::connection('omuster_ilcs')->table('TX_HDR_NOTA')->where('nota_id', $arr['nota']['nota_id'])->update(['nota_flag_einv' => 1]);
				$arr['nota']['nota_flag_einv'] = 1;
			}
		}
		if ($arr['nota']['nota_flag_einv'] == 1) {
			$sendInvPutReceipt = static::sendInvReceipt($arr);
			$sendInvPutReceipt['request']['json'] = json_decode($sendInvPutReceipt['request']['json'], true);
			if ($sendInvPutReceipt['response']['arResponseDoc']['esbBody'][0]['errorCode'] != 'S') {
				return ['Success' => false, 'response' => 'fail send sendInvPutReceipt!', "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
			} else {
				DB::connection('omuster_ilcs')->table('TX_HDR_NOTA')->where('nota_id', $arr['nota']['nota_id'])->update(['nota_flag_einv' => 2]);
				$arr['nota']['nota_flag_einv'] = 2;
			}
		}
		if ($arr['nota']['nota_flag_einv'] == 2) {
			$sendInvPutApply = static::sendInvApply($arr);
			$sendInvPutApply['request']['json'] = json_decode($sendInvPutApply['request']['json'], true);
			if ($sendInvPutApply['response']['arResponseDoc']['esbBody'][0]['errorCode'] != 'S') {
				return ['Success' => false, 'response' => 'fail send sendInvPutApply!', "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
			} else {
				DB::connection('omuster_ilcs')->table('TX_HDR_NOTA')->where('nota_id', $arr['nota']['nota_id'])->update(['nota_flag_einv' => 3]);
				$arr['nota']['nota_flag_einv'] = 3;
			}
		}
		return ["Success" => true, "sendInvAR" => $sendInvAR, "sendInvPutReceipt" => $sendInvPutReceipt, "sendInvPutApply" => $sendInvPutApply];
	}

	public static function penyebut($nilai)
	{
		$nilai = abs($nilai);
		$huruf = array("", "Satu", "Dua", "Tiga", "Empat", "Lima", "Enam", "Tujuh", "Delapan", "Sembilan", "Sepuluh", "Sebelas");
		$temp = "";
		if ($nilai < 12) {
			$temp = " " . $huruf[$nilai];
		} else if ($nilai < 20) {
			$temp = static::penyebut($nilai - 10) . " Belas";
		} else if ($nilai < 100) {
			$temp = static::penyebut($nilai / 10) . " Puluh" . static::penyebut($nilai % 10);
		} else if ($nilai < 200) {
			$temp = " Seratus" . static::penyebut($nilai - 100);
		} else if ($nilai < 1000) {
			$temp = static::penyebut($nilai / 100) . " Ratus" . static::penyebut($nilai % 100);
		} else if ($nilai < 2000) {
			$temp = " Seribu" . static::penyebut($nilai - 1000);
		} else if ($nilai < 1000000) {
			$temp = static::penyebut($nilai / 1000) . " Ribu" . static::penyebut($nilai % 1000);
		} else if ($nilai < 1000000000) {
			$temp = static::penyebut($nilai / 1000000) . " Juta" . static::penyebut($nilai % 1000000);
		} else if ($nilai < 1000000000000) {
			$temp = static::penyebut($nilai / 1000000000) . " Milyar" . static::penyebut(fmod($nilai, 1000000000));
		} else if ($nilai < 1000000000000000) {
			$temp = static::penyebut($nilai / 1000000000000) . " Triliun" . static::penyebut(fmod($nilai, 1000000000000));
		}
		return $temp;
	}

	public static function terbilang($nilai)
	{
		if ($nilai < 0) {
			$hasil = "Minus " . trim(static::penyebut($nilai));
		} else {
			$hasil = trim(static::penyebut($nilai));
		}
		return $hasil;
	}
}
