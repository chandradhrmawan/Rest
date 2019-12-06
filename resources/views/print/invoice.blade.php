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
<center style="width:100%;;margin-top:5px;font-size:11px;text-transform:uppercase">Dermaga Penumpukan</center>

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
				<!-- <tr>
          <td>Periode Kunjungan</td>
          <td>: </td>
        <td>{{$kapal->periode}}</td>
        </tr>
				<tr>
          <td>Kade</td>
          <td>: </td>
        <td>{{$kapal->kade}}</td>
        </tr>
				<tr>
          <td>Tipe Perdagangan</td>
          <td>: </td>
        <td>
					<?php
					// if ($kapal->nota_trade_type == "D") {
					// 	echo "Domestik";
					// } else {
					// 	echo "International";
					//} ?>
				</td>
        </tr> -->
				<tr>
					<td>No. Request </td>
					<td>: </td>
					<td>{{$header->nota_id}}</td>
					<!-- <td>{{$header->nota_req_no}}</td> -->
				</tr>
      </table>
    </td>
	</tr>
</table>

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
		<?php $no = 0;$nomor = 0;  if ($penumpukan != "0") { $total = count($penumpukan);?>
			@foreach($penumpukan as $penumpukan)
			<tr>
				<td style="padding-left:9px"><?php $nomor++;echo $nomor; ?></td>
				<td style="padding-left:9px">{{$penumpukan["dtl_group_tariff_name"]}}</td>
				<?php if ($no < 1) { ?>
					<td rowspan="<?php echo $total; ?>" style="padding-left:9px;text-align:center">
						{{(new \App\Helper\GlobalHelper)->tanggalMasukKeluar($label[0]->nota_service_om_code, $header->nota_req_no, 0)}}
					</td>
				<?php } else { ?>

				<?php } ?>
				<td style="text-align:center">
					{{number_format($penumpukan["dtl_masa"])}}
					<?php //if(!empty($penumpukan["masa1"])) { echo $penumpukan["masa1"]; } else { echo "0"; } ?><br>
					<?php //if(!empty($penumpukan["masa2"])) { echo $penumpukan["masa2"]; } else { echo "0"; } ?></td>
				<td style="text-align:center">
					{{number_format($penumpukan["dtl_tariff"])}}
					<?php //if(!empty($penumpukan["trf1up"])) { echo number_format($penumpukan["trf1up"]); } else { echo "0"; } ?><br>
					<?php //if(!empty($penumpukan["trf2up"])) { echo number_format($penumpukan["trf2up"]); } else { echo "0"; } ?>
				</td>
				<td style="text-align:center">
					{{number_format($penumpukan["dtl_dpp"])}}

					<?php
						// $sewa1 = $penumpukan["masa1"]*$penumpukan["trf1up"]*$penumpukan["dtl_qty"];
						// echo number_format($sewa1)."<br>";
					 ?>
					 <?php
		 				// $sewa2 = $penumpukan["masa2"]*$penumpukan["trf2up"]*$penumpukan["dtl_qty"];
		 				// echo number_format($sewa2);
						$no++;
						$dpp = 0;
		 			 ?></td>
				<td style="text-align:left">IDR</td>
				<td style="text-align:right">{{number_format($penumpukan["dtl_dpp"])}}</td>
			</tr>
			@endforeach
		<?php } ?>
	<?php if ($alat != "0") {?>
		<?php foreach ($alat as $alat) { ?>
				<tr>
					<td style="padding-left:9px"><?php $no++;echo $no; ?></td>
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

<?php if ($bl != "0") { ?>
<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:11px;margin-top:20px">
	<tr style="text-align:center">
		<th rowspan="2" width="15%">NO BL</th>
		<th rowspan="2" width="15%">TL</th>
		<th rowspan="2" width="15%">Kemasan</th>
		<th rowspan="2" width="15%">BARANG</th>
    <th rowspan="2" width="5%">Satuan</th>
    <th colspan="2" width="15%">Qty</th>
    <th rowspan="2" width="10%">Tarif Dasar</th>
    <th rowspan="2" width="10%">Total</th>
	</tr>
  <tr style="text-align:center">
    <th>Bongkar</th>
    <th>Muat</th>
  </tr>
	@foreach($bl as $bl)
  <tr style="background:#ff3030;color:white">
    <td style="padding-left:9px" colspan="9">{{$bl}}</td>
  </tr>
<?php foreach ($handling[$bl] as $value) { ?>
	<tr>
		<td>{{$value["dtl_group_tariff_name"]}}</td>
		<td style="text-align:center">
			<?php
			if ($value["dtl_bm_tl"] == "Y") {
				echo "TL";
			} else {
				echo "NON-TL";
			}
			 ?>
		</td>
		<td>{{$value["dtl_package"]}}</td>
		<td>{{$value["dtl_commodity"]}}</td>
		<td style="text-align:center">{{$value["dtl_unit_name"]}}</td>
		<?php	if ($value["dtl_bm_type"] == "Bongkar") { ?>
			<td style="text-align:center">{{$value["dtl_qty"]}}</td>
			<td style="text-align:center">-</td>
		<?php } else { ?>
			<td style="text-align:center">-</td>
			<td style="text-align:center">{{$value["dtl_qty"]}}</td>
		<?php } ?>
		<td style="text-align:right">{{number_format($value["dtl_tariff"])}}</td>
		<td style="text-align:right">{{number_format($value["dtl_dpp"])}}</td>
	</tr>
<?php } ?>
	@endforeach
<?php } ?>
</table>

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
				echo "<b>Sisa Uper</b>";
			} else {
				echo "<b>Piutang</b>";
			} ?></b>
		</td>
		<td style="text-align:right;padding-right:9px"><b>IDR</b></td>
		<td style="text-align:right"><b>{{number_format(abs((new \App\Helper\GlobalHelper)->getUper($header->nota_req_no)-$header->nota_amount))}}</b></td>
	</tr>
</table>
<p style="font-size:11px;margin-top:50px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}}</font></p>
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
				{{$header->nota_req_no}}
		</div>
	</div>
	<p style="position:absolute;right:0px;bottom:15px;font-size:8px">Print Date : <?php echo date("d-M-Y")." | Page 1/1"; ?></p>
	@endforeach
	@endforeach
	@endforeach
</body>
</html>



</body>
</html>
