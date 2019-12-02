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
		@foreach($kapal as $kapal)
  <table width="100%" style="font-size:10px">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo.jpg') }}" height="50"></td>
			<td width="55%">
				<div<b>{{$branch->branch_name}} <br>{{$branch->branch_address}} </b><div style="margin-top:5px;font-size:8px">NPWP. {{$branch->branch_npwp}}</div></div>
				</td>
      <td style="vertical-align:top;text-align:right">
        <table style="border-collapse:collapse; font-size:10px;">
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

	@foreach($label as $label)
	<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800"> PROFORMA {{$label->nota_name}}</center>
	@endforeach
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
					<td>Customer ID</td>
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
          <td>No Account</td>
          <td>: </td>
          <td>-</td>
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
					if ($kapal->nota_trade_type == "D") {
						echo "Domestik";
					} else {
						echo "International";
					} ?>
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

<?php if ($penumpukan != "0") { ?>
	<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:10px;margin-top:20px">
		<tr style="text-align:center">
			<th width="15%">NO BL</th>
			<th width="15%">Kemasan</th>
			<th width="15%">BARANG</th>
			<th width="5%">Qty</th>
			<th width="15%">Tgl Masuk<br>Tgl Keluar<br>Jml Hari</th>
			<th width="15%">Hari Masa 1 <br> Hari Masa 2</th>
			<th width="15%">Tarif Masa 1<br>Tarif Masa 2</th>
			<th width="15%">Sewa Masa 1<br>Sewa Masa 2</th>
			<th width="10%">Jumlah</th>
		</tr>
		<?php
			$no = 0;
		 ?>
		@foreach($penumpukan as $penumpukan)
		<tr>
			<td style="padding-left:9px">{{$penumpukan["dtl_bl"]}}</td>
			<td style="padding-left:9px">{{$penumpukan["dtl_package"]}}</td>
			<td style="padding-left:9px">{{$penumpukan["dtl_commodity"]}}</td>
			<td style="text-align:center">{{$penumpukan["dtl_qty"]}}</td>
			<td style="text-align:center">
				{{(new \App\Helper\GlobalHelper)->tanggalMasukKeluar($label->nota_service_om_code, $header->nota_req_no, $no)}}
			</td>
			<td style="text-align:center">
				<?php if(!empty($penumpukan["masa1"])) { echo $penumpukan["masa1"]; } else { echo "0"; } ?><br>
				<?php if(!empty($penumpukan["masa2"])) { echo $penumpukan["masa2"]; } else { echo "0"; } ?>
			</td>
			<td style="text-align:right">
				<?php if(!empty($penumpukan["trf1up"])) { echo number_format($penumpukan["trf1up"]); } else { echo "0"; } ?><br>
				<?php if(!empty($penumpukan["trf2up"])) { echo number_format($penumpukan["trf2up"]); } else { echo "0"; } ?>
			</td>
			<td style="text-align:right">
				<?php
					$sewa1 = $penumpukan["masa1"]*$penumpukan["trf1up"]*$penumpukan["dtl_qty"];
					echo number_format($sewa1)."<br>";
				 ?>
				 <?php
	 				$sewa2 = $penumpukan["masa2"]*$penumpukan["trf2up"]*$penumpukan["dtl_qty"];
	 				echo number_format($sewa2);
					$no++;
					$dpp = $sewa1+$sewa2;
	 			 ?>
			 </td>
			<td style="text-align:right">{{number_format($dpp)}}</td>
		</tr>
		@endforeach
	</table>
<?php } ?>

<?php if ($bl != "0") { ?>
<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:10px;margin-top:20px">
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
  <tr style="background-color:#ff3030;color:#fff;">
    <td style="border-right: 0;padding-left:9px">{{$bl}}</td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0;text-align:center"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-left:  0;"></td>
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

<?php if ($alat != "0") { ?>
<table  width="100%" border="1" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<th width="15%">Layanan</th>
		<th width="15%">Nama Alat</th>
		<th width="15%">Satuan Alat</th>
		<th width="15%">Jumlah Alat</th>
    <th width="15%">Qty</th>
    <th width="10%">Tarif Dasar</th>
    <th width="10%">Total</th>
	</tr>

<?php foreach ($alat as $alat) { ?>
		<tr>
			<td style="border-right: 0;padding-left:9px"><?php echo $alat["dtl_equipment"]; ?></td>
			<td style="padding-left:9px"><?php echo $alat["dtl_group_tariff_name"]; ?></td>
			<td style="text-align:center"><?php echo $alat["dtl_unit_name"]; ?></td>
			<td style="text-align:center"><?php echo number_format($alat["dtl_eq_qty"]) ?></td>
			<td style="text-align:center"><?php echo number_format($alat["dtl_qty"]); ?></td>
			<td style="text-align:right"><?php echo number_format($alat["dtl_tariff"]); ?></td>
			<td style="text-align:right"><?php echo number_format($alat["dtl_dpp"]); ?></td>
		</tr>
<?php }} ?>
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
    <td style="border-left:  0;border-top: 0;border-bottom:0;text-align:right">{{number_format($header->nota_ppn)}}</td>
  </tr>
  <tr>
    <td style="border-right: 0;border-top: 0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-left:0" colspan="2">Grand Total</td>
    <td style="border-right: 0;border-top: 0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;text-align:right">{{number_format($header->nota_amount)}}</td>
  </tr>
</table>
<p style="font-size:9px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}}</font></p>
<table style="border-collapse:collapse; font-size:10px;margin-top:60px;float:right;text-align:center">
	<tr><td>Palembang,
	<?php
	$originalDate = $header->nota_date;
	$newDate = date("d-M-y", strtotime($originalDate));
	echo strtoupper($newDate);
	?></td></tr>
	<tr><td>DGM Keuangan & Administrasi</td></tr>
	<tr><td><div style="margin-top:50px"><u>Clara Primasari Henryanto</u></div></td></tr>
	<tr><td>NIPP. 287117773</td></tr>
</table>

<div style="position:absolute;bottom:20px;font-size:11px; width:100%">
	{{$branch->branch_name}} <br>{{$branch->branch_address}}
	<div style="margin-top:50px;font-size:8px">
			{{$header->nota_no}}
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
