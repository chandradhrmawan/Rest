<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		 body{
			 width:100%;
			 margin:0 auto;
			 font-family: 'Courier';
		 }
		 @media print {
        body {
					width:100%;
					margin:0 auto;
					font-family: 'Courier';
				}
      }
	</style>
</head>
<body>

	@foreach($header as $header)
	@foreach($branch as $branch)
  @foreach($data as $data)
  <table width="100%" style="font-size:9px">
    <tr>
			<td width="13%"><img src="{{ url('/other/logo_ptp.png') }}" height="70"></td>
			<td width="45%" style="vertical-align:top;font-size:12px">
				<div>PT. Pelabuhan Tanjung Priok <br>Jln. Raya Pelabuhan No.9 Tanjung Priok <div style="margin-top:3px;font-size:10px">NPWP. 03.276.305.4-093.000</div></div>
				</td>
      <td style="vertical-align:top;text-align:right">
      </td>
    </tr>
  </table>

<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800">Uang Untuk Diperhitungkan (UPER)</center>
<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:8px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <table style="border-collapse:collapse; font-size:9px;">
        <tr>
          <td>Sudah Terima Dari</td>
          <td>: </td>
          <td>{{$data->uper_cust_name}}</td>
        </tr>
        <tr>
          <td>Untuk Kapal / Voyage</td>
          <td>: </td>
          <td>{{$data->uper_vessel_name}}</td>
        </tr>
        <tr>
          <td>Periode Kunjungan</td>
          <td>: </td>
          <td>{{$data->periode}}</td>
        </tr>
        <tr>
          <td>Nomor Uper</td>
          <td>: </td>
          <td>{{$data->uper_no}}</td>
        </tr>
        <tr>
          <td>Untuk Pembayaran</td>
          <td>: </td>
          <td>
            <?php
            if($data->uper_trade_type == "D") {
              echo "PELAYARAN DALAM NEGERI";
            } else {
              echo "PELAYARAN LUAR NEGERI";
            }
            ?>
          </td>
        </tr>
        <tr>
          <td>Jumlah UPER</td>
          <td>: </td>
          <td>{{number_format($data->uper_amount)}}</td>
        </tr>
        <tr>
          <td>Jumlah Pembayaran</td>
          <td>: </td>
          <td>{{number_format($data->pay_amount)}}</td>
        </tr>
        <tr>
          <td><br>Cara Pembayaran</td>
          <td><br>: </td>
          <td><br>{{$data->pay_account_name}}</td>
        </tr>
        <tr>
          <td>Tanggal Pembayaran</td>
          <td>: </td>
          <td>{{$data->pay_date}}</td>
        </tr>
        <tr>
          <td>Keterangan</td>
          <td>: </td>
          <td>{{$data->pay_note}}</td>
        </tr>
      </table>
    </td>
		<td style="vertical-align:top">
      <table style="border-collapse:collapse; font-size:9px;">
        <tr>
          <td>No. Account</td>
          <td>: </td>
          <td>{{$data->pay_cust_id}}</td>
        </tr>
      </table>
</table>

<p style="font-size:9px;margin-top:80px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}} Rupiah</font></p>
<table style="width:100%">
	<tr>
		<td>
			<div><?php echo DNS2D::getBarcodeHTML($qrcode, "QRCODE", 1.5,1.5); ?></div>
		</td>
		<td style="vertical-align:top">
      <table style="border-collapse:collapse; font-size:8px;float:right;text-align:center">
				<tr><td>Banten,
        <?php
        $originalDate = $header->uper_date;
        $newDate = date("d-M-y", strtotime($originalDate));
        echo strtoupper($newDate);
        ?></td></tr>
				<tr><td>A.N. GENERAL MANAGER<br>DEPUTY GM KEUANGAN & SDM</td></tr>
				<tr><td><div style="margin-top:50px"><u>Ambarwati Legina</u></div></td></tr>
				<tr><td>NIPP. 285047354</td></tr>
			</table>
		</td>
	</tr>
</table>

<div style="position:absolute;bottom:20px;font-size:9px; width:100%">
	{{$branch->branch_name}} <br>{{$branch->branch_address}}
	<div style="margin-top:50px;font-size:8px">
			{{$branch->branch_npwp}}
	</div>
</div>
<p style="position:absolute;right:0px;bottom:15px;font-size:8px">Print Date : <?php echo date('Y-m-d H:i:s', strtotime('7 hour 10 minute'))." | Page 1/1"; ?></p>
@endforeach
@endforeach
@endforeach
</body>
</html>
