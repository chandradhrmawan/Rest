<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		 body{
			 width:100%;
			 margin:0 auto;
			 font-family: "Arial",Sans-serif;
		 }
		 @media print {
        body {
					width:100%;
					margin:0 auto;
					font-family: "Arial", Sans-serif;
				}
      }
	</style>
</head>
<body>
		@foreach($header as $header)
		@foreach($label as $label)
		<?php
		$noa = 0;
		$nomor = 0;
		if ($header->nota_paid == "I") { ?>
			<img src="{{ url('/other/belum_lunas.png')}}" alt="" style="position:absolute;opacity:0.3;margin-left:100px;transform: rotate(-30deg);margin-top:300px;width:80%">
		<?php } ?>
		@foreach($branch as $branch)
  <table width="100%" style="font-size:10px">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo_ptp.png') }}" height="70"></td>
			<td width="45%" style="vertical-align:top;font-size:12px">
				<div>PT. Pelabuhan Tanjung Priok <br>Jln. Raya Pelabuhan No.9 Tanjung Priok <div style="margin-top:3px;font-size:10px">NPWP. 03.276.305.4-093.000</div></div>
				</td>
      <td style="vertical-align:top;text-align:right">
        <table style="border-collapse:collapse; font-size:11px;width:70%">
          <tr>
            <td>No. Nota</td>
            <td>: {{$header->nota_no}}</td>
          </tr>
          <tr>
            <td>Tanggal</td>
            <td>:
							<?php
							$originalDate = $header->nota_date;
							$newDate = date("d-M-y", strtotime($originalDate));
							echo strtoupper($newDate);
							?>
						</td>
          </tr>
					<tr>
            <td colspan="2">
							<!-- Nota sebagai faktur pajak berdasarkan Peraturan Dirjen Pajak Nomor PER-13/PJ/2019 tanggal 2 Juli 2019 -->
						</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>

<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800;text-transform:uppercase">Nota Penjualan Jasa {{$label->nota_name}}</center>

<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:11px;margin-top:20px;margin-bottom:20px">
	<tr style="text-align:center">
		<td style="vertical-align:top;width:60%" >
      <table style="border-collapse:collapse; font-size:11px;" width="100%">
        <tr>
          <td colspan="3">
            <font style="font-size:11px;text-align:left;font-weight:800"><b>Penerima Jasa</b></font><br>
          </td>
        </tr>
        <tr>
          <td width="10%">Nama</td>
          <td width="1%">: </td>
          <td>{{$header->nota_cust_name}}</td>
        </tr>
				<!-- <tr>
          <td>Nomor</td>
          <td>: </td>
          <td>{{$header->nota_cust_id}}</td>
        </tr> -->
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
          <td>No. DO</td>
          <td>: </td>
          <td>-</td>
        </tr>
				<tr>
          <td>PBM</td>
          <td>: </td>
          <td>{{$header->nota_pbm_name}}</td>
        </tr>
				<tr>
          <td>Nama Kapal</td>
          <td>: </td>
          <td>{{$header->nota_vessel_name}}</td>
        </tr>
				<tr>
					<td>Periode Kunjungan </td>
					<td>: </td>
					<td>{{$header->nota_id}}</td>
				</tr>
      </table>
    </td>
	</tr>
</table>

<?php if ($label->nota_id == '21' || $label->nota_id == '22') {?>
	<table  width="100%" align="center" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;">
	<tr style="text-align:center">
		<th width="3%" style="border-bottom:solid 1px; text-align:center">No</th>
		<th width="17%" style="border-bottom:solid 1px">Layanan</th>
		<th width="15%" style="border-bottom:solid 1px">Kemasan</th>
		<th width="10%" style="border-bottom:solid 1px">Satuan</th>
		<th width="6%" style="border-bottom:solid 1px">Qty</th>
		<th width="10%" style="border-bottom:solid 1px">Tarif Dasar</th>
		<th width="5%" style="border-bottom:solid 1px"></th>
		<th width="20%" style="border-bottom:solid 1px">Total</th>
	</tr>
	@foreach($detail as $detail)
	<tr>
		<td style="text-align:center"><?php $nomor++;echo $nomor; ?></td>
		<td>{{$detail->group_tariff_name}}</td>
		<td style="text-align:center">{{$detail->package_name}}</td>
		<td style="text-align:center">{{$detail->unit_name}}</td>
		<td style="text-align:center">{{$detail->qty}}</td>
		<td style="text-align:right">{{number_format($detail->tariff)}}</td>
		<td>IDR</td>
		<td style="text-align:right">{{number_format($detail->dpp)}}</td>
	</tr>
	@endforeach
	<?php	if ($penumpukan != 0) { ?>
		@foreach($penumpukan as $penumpukan)
		<tr>
			<td style="text-align:center"><?php $nomor++;echo $nomor; ?></td>
			<td>{{$penumpukan->dtl_group_tariff_name}}</td>
			<td style="text-align:left">{{$penumpukan->dtl_cont_size}} / {{$penumpukan->dtl_cont_type}} / {{$penumpukan->dtl_cont_status}}</td>
			<td style="text-align:center">{{$penumpukan->dtl_qty}}</td>
			<td style="text-align:right">{{number_format($penumpukan->dtl_tariff)}}</td>
			<td>IDR</td>
			<td style="text-align:right">{{number_format($penumpukan->dtl_dpp)}}</td>
		</tr>
		@endforeach
	<?php } ?>
</table>
<?php
} else {
?>
<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;">
	<tr style="text-transform:uppercase;font-weight:800">
		<th width="5%" style="border-bottom:solid 1px;text-align:center">No</th>
		<th width="15%" style="border-bottom:solid 1px">Layanan</th>
		<th width="10%" style="border-bottom:solid 1px">Container</th>
		<th width="1%" style="border-bottom:solid 1px;text-align:center">Qty</th>
		<th width="10%" style="border-bottom:solid 1px;text-align:center">Tarif Dasar</th>
		<th width="5%" style="border-bottom:solid 1px"></th>
		<th width="10%" style="border-bottom:solid 1px">Jumlah</th>
	</tr>
	@foreach($detail as $detail)
	<tr>
		<td style="text-align:center"><?php $nomor++;echo $nomor; ?></td>
		<td>{{$detail->group_tariff_name}}</td>
		<td style="text-align:left">{{$detail->cont_size}} / {{$detail->cont_type}} / {{$detail->cont_status}}</td>
		<td style="text-align:center">{{$detail->qty}}</td>
		<td style="text-align:right">{{number_format($detail->tariff)}}</td>
		<td>IDR</td>
		<td style="text-align:right">{{number_format($detail->dpp)}}</td>
	</tr>
	@endforeach
	<?php	if ($penumpukan != 0) { ?>
		@foreach($penumpukan as $penumpukan)
		<tr>
			<td style="text-align:center"><?php $nomor++;echo $nomor; ?></td>
			<td>{{$penumpukan->group_tariff_name}}</td>
			<td style="text-align:left">{{$penumpukan->cont_size}} / {{$penumpukan->cont_type}} / {{$penumpukan->cont_status}}</td>
			<td style="text-align:center">{{$penumpukan->qty}}</td>
			<td style="text-align:right">{{number_format($penumpukan->tariff)}}</td>
			<td>IDR</td>
			<td style="text-align:right">{{number_format($penumpukan->dpp)}}</td>
		</tr>
		@endforeach
	<?php } ?>
</table>
<?php } ?>

<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:11px;margin-top:20px">
  <tr>
    <td colspan="7">DASAR PENGENAAN PAJAK</td>
    <td style="text-align:right;padding-right:9px">IDR</td>
    <td style="text-align:right">{{number_format($header->nota_dpp)}}</td>
  </tr>
  <tr>
    <td colspan="7">PPN 10%</td>
    <td style="text-align:right;padding-right:9px">IDR</td>
    <td style="text-align:right;border-bottom:solid 1px">{{number_format($header->nota_ppn)}}</td>
  </tr>
  <tr>
    <td style="" colspan="7">Jumlah Tagihan</td>
    <td style="text-align:right;padding-right:9px">IDR</td>
    <td style="text-align:right;">{{number_format($header->nota_amount)}}</td>
  </tr>
	<tr>
		<td style="" colspan="7">Uang Pembayaran</td>
		<td style="text-align:right;padding-right:9px">IDR</td>
		<td style="text-align:right">{{number_format($bayar)}}</td>
	</tr>
	<tr>
		<td style="" colspan="7">
			<b>Piutang</b>
		</td>
		<td style="text-align:right;padding-right:9px"><b>IDR</b></td>
		<td style="text-align:right"><b>{{number_format($total)}}</b></td>
	</tr>
</table>
<p style="font-size:11px;margin-top:50px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}} Rupiah</font></p>
<table style="width:100%">
	<tr>
		<td>
			<div><?php echo DNS2D::getBarcodeHTML($qrcode, "QRCODE", 2,2); ?></div>
		</td>
		<td style="vertical-align:top">
			<table style="border-collapse:collapse; font-size:11px;float:right;text-align:center">
				<tr><td>Banten,
        <?php
        $originalDate = $header->nota_date;
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
