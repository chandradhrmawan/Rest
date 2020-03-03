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
		@foreach($label as $label)
  <table width="100%" style="font-size:10px">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo.jpg') }}" height="50"></td>
			<td width="45%">
				<div>PT. Pelabuhan Tanjung Priok <br>Jln. Raya Pelabuhan No.9 Tanjung Priok <div style="margin-top:3px;font-size:10px">NPWP. 03.276.305.4-093.000</div></div>
				</td>
      <td style="vertical-align:top;text-align:right" width="42%">
        <table style="border-collapse:collapse; font-size:10px;width:100%">
          <tr>
            <td>No. Proforma</td>
            <td>: {{$header->nota_no}}</td>
          </tr>
          <tr>
            <td>Tanggal Proforma</td>
            <td>:
							<?php
							$originalDate = $header->nota_date;
							$newDate = date("d-M-y", strtotime($originalDate));
							echo strtoupper($newDate);
							?>
						</td>
          </tr>
					<tr>
            <td> </td>
            <td></td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

	<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800"> PROFORMA {{$label->nota_name}}</center>
	<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:11px;margin-top:20px">
	<tr style="text-align:center">
		<td style="vertical-align:top">
      <table style="border-collapse:collapse; font-size:11px;">
        <tr>
          <td colspan="3">
            <font style="font-size:11px;text-align:left"><b>Pengguna Jasa</b></font><br>
          </td>
        </tr>
        <tr>
          <td>Nama</td>
          <td>: </td>
          <td>{{$header->nota_cust_name}}</td>
        </tr>
				<tr>
					<td>No Account</td>
					<td>: </td>
					<td>{{$header->nota_cust_id}}</td>
				</tr>
        <tr>
          <td>Alamat</td>
          <td>: </td>
          <td>{{$header->nota_cust_address}}</td>
        </tr>
        <tr>
          <td>NPWP</td>
          <td>: </td>
          <td>{{$header->nota_cust_npwp}}</td>
        </tr>
      </table>
    </td>
		<td>
      <table style="border-collapse:collapse; font-size:11px;">
					<tr>
						<td>Nama.PBM </td>
						<td>: </td>
						<td>-</td>
					</tr>
				<tr>
          <td>Nama Kapal</td>
          <td>: </td>
          <td>{{$header->nota_vessel_name}}</td>
        </tr>
				<tr>
          <td>Periode Kunjungan</td>
          <td>: </td>
        <td>-</td>
        </tr>
				<tr>
          <td>Kade</td>
          <td>: </td>
        <td>-</td>
        </tr>
				<tr>
          <td>Tipe Perdagangan</td>
          <td>: </td>
        <td>
				</td>
        </tr>
				<tr>
					<td>No. Request </td>
					<td>:</td>
					<td>{{$header->nota_req_no}}</td>
				</tr>
      </table>
    </td>
	</tr>
</table>

<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:10px;margin-top:20px">
<?php if ($label->nota_id == '21' || $label->nota_id == '22') {?>
	<tr style="text-align:center">
		<th width="20%">Layanan</th>
		<th width="20%">Nama Barang</th>
		<th width="10%">Satuan</th>
		<th width="10%">Qty</th>
		<th width="20%">Tarif Dasar</th>
		<th width="20%">Total</th>
	</tr>
	@foreach($detail as $detail)
	<tr>
		<td>{{$detail->group_tariff_name}}</td>
		<td style="text-align:center">{{$detail->commodity}}</td>
		<td style="text-align:center">{{$detail->unit_name}}</td>
		<td style="text-align:center">{{$detail->qty}}</td>
		<td style="text-align:right">{{number_format($detail->tariff)}}</td>
		<td style="text-align:right">{{number_format($detail->dpp)}}</td>
	</tr>
	@endforeach
<?php
} else {
?>
<tr style="text-align:center">
	<th rowspan="2" width="21%">Layanan</th>
	<th colspan="3" width="35%">Container</th>
	<th rowspan="2" width="8%">Qty</th>
	<th rowspan="2" width="13%">Tarif Dasar</th>
	<th rowspan="2" width="15%">Total</th>
</tr>
<tr style="text-align:center">
	<td>Size</td>
	<td>Type</td>
	<td>Status</td>
@foreach($detail as $detail)
<tr>
	<td>{{$detail->group_tariff_name}}</td>
	<td style="text-align:center">{{$detail->cont_size}}</td>
	<td style="text-align:center">{{$detail->cont_type}}</td>
	<td style="text-align:center">{{$detail->cont_status}}</td>
	<td style="text-align:center">{{$detail->qty}}</td>
	<td style="text-align:right">{{number_format($detail->tariff)}}</td>
	<td style="text-align:right">{{number_format($detail->dpp)}}</td>
</tr>
@endforeach
<?php } ?>
</table>
</table>

<table  width="100%" border="1" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
  <tr>
    <td style="border-right: 0;border-bottom: 0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-bottom: 0;border-left:0" colspan="2">DPP</td>
    <td style="border-right: 0;border-bottom: 0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-bottom: 0;text-align:right">{{number_format($header->nota_dpp)}}</td>
  </tr>
	<tr>
		<td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
		<td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">Administrasi</td>
		<td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
		<td style="border-left:  0;border-top: 0;border-bottom:0;text-align:right">{{number_format(0)}}</td>
	</tr>
  <tr>
    <td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">PPN 10%</td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;text-align:right">{{number_format($header->nota_ppn)}}</td>
  </tr>
  <tr>
    <td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">Total</td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;border-bottom:0;text-align:right">{{number_format($header->nota_amount)}}</td>
  </tr>
	<tr>
		<td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
		<td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">Jumlah Pembayaran</td>
		<td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
		<td style="border-left:  0;border-top: 0;text-align:right">{{number_format(0)}}</td>
	</tr>
	<tr>
		<td style="border-right: 0;border-top: 0;width:50%" colspan="5"></td>
		<td style="border-right: 0;border-top: 0;border-left:0" colspan="2">Grand Total</td>
		<td style="border-right: 0;border-top: 0;border-left:0;text-align:right;padding-right:9px">IDR</td>
		<td style="border-left:  0;border-top: 0;text-align:right">{{number_format($total)}}</td>
	</tr>
</table>
<p style="font-size:9px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}} Rupiah</font></p>
<table style="border-collapse:collapse; font-size:11px;margin-top:60px;float:right;text-align:center">
	<tr><td>Palembang, <?php  echo strtoupper(date("d-M-y", strtotime($header->nota_date))); ?></td></tr>
	<tr><td>A.N. GENERAL MANAGER<br>DEPUTY GM KEUANGAN & ADMINISTRASI</td></tr>
	<tr><td><div style="margin-top:50px"><u>Ambarwati Legina</u></div></td></tr>
	<tr><td>NIPP. 285047354</td></tr>
</table>

<div style="position:absolute;bottom:20px;font-size:11px; width:100%">
	{{$branch->branch_name}} <br>{{$branch->branch_address}}
	<div style="margin-top:50px;font-size:8px">
			{{$header->nota_no}}
	</div>
</div>
<p style="position:absolute;right:0px;bottom:15px;font-size:8px">Print Date : <?php echo date("d-M-Y H:s:i")." | Page 1/1"; ?></p>
@endforeach
@endforeach
@endforeach

</body>
</html>



</body>
</html>
