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
          <td width="10%">Perusahaan</td>
          <td width="1%">: </td>
          <td>{{$header->nota_cust_name}}</td>
        </tr>
				<tr>
          <td>Pemilik</td>
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
				<tr>
					<td>No Doc</td>
					<td>: </td>
					<td>{{$header->nota_req_no}}</td>
				</tr>
      </table>
    </td>
		<td>
			<table style="border-collapse:collapse; font-size:11px;">
				<tr>
          <td>Kade</td>
          <td>: </td>
          <td>-</td>
        </tr>
				<tr>
          <td>No I BPR/BL/DO</td>
          <td>: </td>
          <td>-</td>
        </tr>
				<!-- <tr>
          <td>PBM</td>
          <td>: </td>
          <td>{{$header->nota_pbm_name}}</td>
        </tr> -->
				<tr>
          <td>Kapal/Voy/Tgl</td>
          <td>: </td>
          <td>{{$header->nota_vessel_name}}</td>
        </tr>
				<tr>
					<td>Jenis Perdagangan </td>
					<td>: </td>
					<td>-</td>
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
			<td>{{$penumpukan->group_tariff_name}}</td>
			<td style="text-align:center">{{$penumpukan->package_name}}</td>
			<td style="text-align:center">{{$penumpukan->unit_name}}</td>
			<td style="text-align:center">{{$penumpukan->qty}}</td>
			<td style="text-align:right">{{number_format($penumpukan->tariff)}}</td>
			<td>IDR</td>
			<td style="text-align:right">{{number_format($penumpukan->dpp)}}</td>
		</tr>
		@endforeach
	<?php } ?>
</table>
<?php } else if (in_array($label->nota_id, [4,3,7,10,17,18])) {  ?>
	<table  width="100%" align="center" border="0" cellspacing="2" cellpadding="4" style="border-collapse:collapse; font-size:11px;">
		<tr>
			<td colspan="4" style="text-align:left">
				<?php	if ($penumpukan != 0) { ?>
				<p><b>Jenis Jasa</b><br>JML x SIZE : {{$penumpukan[0]->qty}} x  {{$penumpukan[0]->cont_size}}"</p>
			<?php } else {  ?>
				<p><b>Jenis Jasa</b><br>JML x SIZE : {{$detail[0]->qty}} x  {{$detail[0]->cont_size}}"</p>
			<?php } ?>
			</td>
		</tr>
	@foreach($detail as $detail)
	<tr>
		<td>{{$detail->group_tariff_name}}</td>
		<td>:Rp. </td>
		<td>{{$detail->qty}} x {{number_format($detail->tariff)}}</td>
		<td style="text-align:right">{{number_format($detail->dpp)}}</td>
	</tr>
	@endforeach
	<?php	if ($penumpukan != 0) { ?>
		@foreach($penumpukan as $penumpukan)
		<tr>
			<td>{{$penumpukan->group_tariff_name}}</td>
			<td>:Rp. </td>
			<td>{{$penumpukan->qty}} x {{number_format($penumpukan->tariff)}}</td>
			<td style="text-align:right">{{number_format($penumpukan->dpp)}}</td>
		</tr>
		@endforeach
	<?php } ?>
	<tr>
		<td>Uang Jasa</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">{{number_format($header->nota_dpp)}}</td>
	</tr>
	<tr>
		<td>PPN</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">{{number_format($header->nota_ppn)}}</td>
	</tr>
	<tr>
		<td>Materai</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">0</td>
	</tr>
	<tr>
		<td>Jumlah</td>
		<td style="border-top:solid thin">:Rp.</td>
		<td style="border-top:solid thin"></td>
		<td style="text-align:right;border-top:solid thin">{{number_format($header->nota_amount)}}</td>
	</tr>
	<tr>
		<td>PPN ditanggung Pemerintah</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">0</td>
	</tr>
	<tr>
		<td>Jumlah Uper</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">0</td>
	</tr>
	<tr>
		<td>Piutang</td>
		<td>:Rp.</td>
		<td></td>
		<td style="text-align:right">{{number_format($bayar)}}</td>
	</tr>
</table>
<?php } else { ?>
<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;">
	<tr style="text-transform:uppercase;font-weight:800">
		<th width="15%" style="border-top:solid 1px;border-bottom:solid 1px;">Keterangan</th>
		<th width="10%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Tgl Awal</th>
		<th width="10%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Tgl Akhir</th>
		<th width="1%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Box</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Sz</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Ty</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">St</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Hz</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Hr</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px;text-align:center">Tarif</th>
		<th width="5%" style="border-top:solid 1px;border-bottom:solid 1px">Val</th>
		<th width="10%" style="border-top:solid 1px;border-bottom:solid 1px;">Jumlah</th>
	</tr>
	@foreach($detail as $detail)
	<tr>
		<td>{{$detail->group_tariff_name}}</td>
		<td style="text-align:center">-</td>
		<td style="text-align:center">-</td>
		<td style="text-align:center">{{$detail->qty}}</td>
		<td style="text-align:center">{{$detail->cont_size}}</td>
		<td style="text-align:center">{{$detail->cont_type}}</td>
		<td style="text-align:center">{{$detail->cont_status}}</td>
		<td style="text-align:center">-</td>
		<td style="text-align:center">-</td>
		<td style="text-align:right">{{number_format($detail->tariff)}}</td>
		<td>IDR</td>
		<td style="text-align:right">{{number_format($detail->dpp)}}</td>
	</tr>
	@endforeach
	<?php	if ($penumpukan != 0) { ?>
		@foreach($penumpukan as $penumpukan)
		<tr>
			<td>{{$penumpukan->group_tariff_name}}</td>
			<td style="text-align:center">-</td>
			<td style="text-align:center">-</td>
			<td style="text-align:center">{{$penumpukan->qty}}</td>
			<td style="text-align:center">{{$penumpukan->cont_size}}</td>
			<td style="text-align:center">{{$penumpukan->cont_type}}</td>
			<td style="text-align:center">{{$penumpukan->cont_status}}</td>
			<td style="text-align:center">-</td>
			<td style="text-align:center">-</td>
			<td style="text-align:right">{{number_format($penumpukan->tariff)}}</td>
			<td>IDR</td>
			<td style="text-align:right">{{number_format($detail->dpp)}}</td>
		</tr>
		@endforeach
	<?php } ?>
	<tr>
		<td colspan="12" style="border-bottom:solid 1px;"></td>
	</tr>
</table>
<?php } ?>

<?php if (!in_array($label->nota_id, [4,3,7,10,17,18])) {  ?>
<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:11px;margin-top:20px">
	<tr>
    <td colspan="7" style="text-align:right">Discount</td>
    <td style="text-align:right;">:</td>
    <td style="text-align:right">0</td>
  </tr>
	<tr>
    <td colspan="7" style="text-align:right">Administrasi</td>
    <td style="text-align:right;">:</td>
    <td style="text-align:right">0</td>
  </tr>
	<tr>
    <td colspan="7" style="text-align:right">Dasar Pengenaan Pajak</td>
    <td style="text-align:right;">:</td>
    <td style="text-align:right">{{number_format($header->nota_dpp)}}</td>
  </tr>
  <tr>
    <td colspan="7" style="text-align:right">Jumlah PPN</td>
    <td style="text-align:right;">:</td>
    <td style="text-align:right;">{{number_format($header->nota_ppn)}}</td>
  </tr>
  <!-- <tr>
    <td colspan="7" style="text-align:right">Jumlah Tagihan</td>
    <td style="text-align:right;">:</td>
    <td style="text-align:right;">{{number_format($header->nota_amount)}}</td>
  </tr> -->
	<tr>
		<td colspan="7" style="text-align:right">Materai</td>
		<td style="text-align:right;">:</td>
		<td style="text-align:right">0</td>
	</tr>
	<tr>
		<td colspan="7" style="text-align:right">Jumlah Dibayar</td>
		<td style="text-align:right;">:</td>
		<td style="text-align:right">{{number_format($header->nota_amount)}}</td>
		<!-- <td style="text-align:right">{{number_format($bayar)}}</td> -->
	</tr>
	<!-- <tr>
		<td colspan="7" style="text-align:right">
			<b>Piutang</b>
		</td>
		<td style="text-align:right;"><b>:</b></td>
		<td style="text-align:right"><b>{{number_format($total)}}</b></td>
	</tr> -->
</table>
<?php } ?>
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
