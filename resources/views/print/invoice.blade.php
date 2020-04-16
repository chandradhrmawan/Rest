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
		@foreach($kapal as $kapal)
		@foreach($sign as $sign)

		<?php if ($header->nota_paid == "I") { ?>
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

<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800;text-transform:uppercase">Nota Penjualan Jasa Kepelabuhan</center>
<?php if ($label[0]->nota_service_om_code != "BM") { ?>
<center style="width:100%;;margin-top:5px;font-size:11px;text-transform:uppercase">Dermaga Penumpukan</center>
<?php } else { ?>
<center style="width:100%;;margin-top:5px;font-size:11px;text-transform:uppercase">Usaha Terminal</center>
<?php } ?>

<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:11px;margin-top:20px">
	<tr style="text-align:center">
		<td style="vertical-align:top;width:60%" >
      <table style="border-collapse:collapse; font-size:11px;">
        <tr>
          <td colspan="3">
            <font style="font-size:11px;text-align:left;font-weight:800"><b>Penerima Jasa</b></font><br>
          </td>
        </tr>
        <tr>
          <td>Nama</td>
          <td>: </td>
          <td>{{$header->nota_cust_name}}</td>
        </tr>
				<tr>
          <td>Nomor</td>
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
          <td>Nama Kapal</td>
          <td>: </td>
          <td>{{$header->nota_vessel_name}}</td>
        </tr>
				<tr>
					<td>No. Request </td>
					<td>: </td>
					<td>{{$header->nota_id}}</td>
				</tr>
      </table>
    </td>
	</tr>
</table>

	<?php if ($label[0]->nota_service_om_code != "BM") { ?>
	<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;margin-top:20px">
		<tr style="text-align:center;text-transform:uppercase;font-weight:800">
			<th style="border-bottom:solid 1px">No</th>
			<th style="border-bottom:solid 1px">Jenis Barang<br> Jumlah Barang</th>
			<th style="border-bottom:solid 1px">Tanggal<br>-Masuk<br>-Keluar<br>-Jumlah Hari</th>
			<th style="border-bottom:solid 1px">Hari<br>-Masa 1<br>-Masa 2</th>
			<th style="border-bottom:solid 1px">Tarif<br>-Penumpukan<br>-Dermaga</th>
			<th style="border-bottom:solid 1px">Sewa<br>-Masa 1<br>-Masa 2</th>
			<th style="border-bottom:solid 1px"></th>
			<th style="border-bottom:solid 1px">Jumlah</th>
		</tr>
		<?php
			$noa = 0;
			$nomor = 0;
			if ($penumpukan != "0") {
				$total = count($penumpukan);
		?>
			@foreach($penumpukan as $penumpukan)
			<tr>
				<td style="padding-left:9px"><?php $nomor++;echo $nomor; ?></td>
				<td style="padding-left:9px">{{$penumpukan["dtl_commodity"]}}</td>
					<td rowspan="<?php echo $total; ?>" style="padding-left:9px;text-align:center">
						{{(new \App\Helper\GlobalHelper)->tanggalMasukKeluar($label[0]->nota_service_om_code, $header->nota_req_no, $penumpukan->dtl_id)}}
						<?php $noa++; ?>
					</td>
				<td style="text-align:center">
					{{number_format($penumpukan["masa1"])}}<br>
					{{number_format($penumpukan["masa2"])}}
				</td>
				<td style="text-align:center">
					{{number_format($penumpukan["trf1up"])}}<br>
					{{number_format($penumpukan["trf2up"])}}
				</td>
				<td style="text-align:center">
					<?php
						echo number_format($penumpukan["masa1"]*$penumpukan["dtl_qty"]*$penumpukan["trf1up"])."<br>";
						echo number_format($penumpukan["masa2"]*$penumpukan["dtl_qty"]*$penumpukan["trf2up"]);
					 ?>
				</td>
				<td style="text-align:left">IDR</td>
				<td style="text-align:right">{{number_format($penumpukan["dtl_dpp"])}}</td>
			</tr>
			@endforeach
		<?php } ?>
	<?php if ($alat != "0") {?>
		<?php foreach ($alat as $alat) { ?>
				<tr>
					<td style="padding-left:9px"><?php $nomor++;echo $nomor; ?></td>
					<td style="border-right: 0;padding-left:9px"><?php echo $alat["dtl_group_tariff_name"]; ?></td>
					<td style="text-align:center"></td>
					<td style="text-align:center"></td>
					<td style="text-align:center"></td>
					<td style="text-align:right"></td>
					<td style="text-align:left">IDR</td>
					<td style="text-align:right"><?php echo number_format($alat["dtl_dpp"]); ?></td>
				</tr>
		<?php }} ?>
	</table>
<?php	} else { ?>
	<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;margin-top:20px">
<?php
 	$no = 1;
	if ($bl != "0") {
?>
	<tr>
		<td colspan="4"><b>Jenis Jasa</b></td>
	</tr>
<?php foreach ($handling as $value) { ?>
	<tr>
		<td width="5%"><?php echo $no; $no++; ?></td>
		<td width="59%" style="text-align:left">{{$value["dtl_group_tariff_name"]}}</td>
		<td style="text-align:left">IDR</td>
		<td style="text-align:right">{{number_format($value["dtl_dpp"])}}</td>
	</tr>
<?php } ?>
<?php } ?>
<?php if ($alat != "0") {?>
	<?php foreach ($alat as $alat) { ?>
			<tr>
				<td width="5%"><?php echo $no;$no++; ?></td>
				<td width="66%"><?php echo $alat["dtl_group_tariff_name"]; ?></td>
				<td style="text-align:left">IDR</td>
				<td style="text-align:right"><?php echo number_format($alat["dtl_dpp"]); ?></td>
			</tr>
	<?php }} ?>
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
		<td style="" colspan="7">Uang Jaminan</td>
		<td style="text-align:right;padding-right:9px">IDR</td>
		<td style="text-align:right">{{number_format((new \App\Helper\GlobalHelper)->getUper($header->nota_req_no))}}</td>
	</tr>
	<tr>
		<td style="" colspan="7">
			<b><?php
			if((new \App\Helper\GlobalHelper)->getUper($header->nota_req_no)-$header->nota_amount > 0) {
				if ($label[0]->nota_service_om_code == "BM") {
					echo "<b>Pelunasan</b>";
				} else {
					echo "<b>Sisa Uper</b>";
				}
			} else {
				if ($label[0]->nota_service_om_code == "BM") {
					echo "<b>Pelunasan</b>";
				} else {
					echo "<b>Piutang</b>";
				}

			} ?></b>
		</td>
		<td style="text-align:right;padding-right:9px"><b>IDR</b></td>
		<td style="text-align:right"><b>{{number_format((new \App\Helper\GlobalHelper)->getUper($header->nota_req_no)-$header->nota_amount)}}</b></td>
	</tr>
</table>
<p style="font-size:11px;margin-top:50px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}} Rupiah</font></p>
<table style="width:100%">
	<tr>
		<td>
			<div><?php echo DNS2D::getBarcodeHTML($qrcode, "QRCODE", 2,2); ?></div>
		</td>
		<td style="vertical-align:top">
			<table style="border-collapse:collapse; font-size:11px;margin-top:60px;float:right;text-align:center">
				<tr><td>Banten, <?php  echo strtoupper(date("d-M-y", strtotime($header->nota_date))); ?></td></tr>
				<tr><td>A.N. {{$sign->sign_an}}<br>{{$sign->sign_position}}</td></tr>
				<tr><td><div style="margin-top:50px"><u>{{$sign->sign_name}}</u></div></td></tr>
				<tr><td>NIPP. {{$sign->sign_nipp}}</td></tr>
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
	@endforeach

</body>
</html>



</body>
</html>
