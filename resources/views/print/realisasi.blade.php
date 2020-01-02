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
  <table width="100%" style="font-size:10px">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo.jpg') }}" height="50"></td>
			<td width="55%">
				<div<b>{{$branch->branch_name}} <br>{{$branch->branch_address}} </b><div style="margin-top:5px;font-size:8px">NPWP. {{$branch->branch_npwp}}</div></div>
				</td>
      <td style="vertical-align:top;text-align:right">
        <table style="border-collapse:collapse; font-size:10px;">
          <tr>
            <td>No. Realisasi</td>
            <td>: {{$header->real_no}}</td>
          </tr>
          <tr>
            <td>Tanggal Realisasi</td>
            <td>:
							<?php
							$originalDate = $header->bm_date;
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

	<center style="width:100%;background-color:#ff3030;color:#fff;margin-top:20px;padding:5px;font-weight:800"> REALISASI BONGKAR MUAT </center>
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
          <td>{{$header->bm_cust_name}}</td>
        </tr>
				<tr>
					<td>No Account</td>
					<td>: </td>
					<td>{{$header->bm_cust_id}}</td>
				</tr>
        <tr>
          <td>Alamat</td>
          <td>: </td>
          <td>{{$header->bm_cust_address}}</td>
        </tr>
        <tr>
          <td>NPWP</td>
          <td>: </td>
          <td>{{$header->bm_cust_npwp}}</td>
        </tr>
      </table>
    </td>
		<td>
      <table style="border-collapse:collapse; font-size:11px;">
					<tr>
						<td>Nama.PBM </td>
						<td>: </td>
						<td>{{$header->bm_pbm_name}}</td>
					</tr>
				<tr>
          <td>Nama Kapal</td>
          <td>: </td>
          <td>{{$header->bm_vessel_name}}</td>
        </tr>
				<tr>
          <td>Periode Kunjungan</td>
          <td>: </td>
        <td>
          <?php
          echo date("d-M-y", strtotime($header->bm_eta))." / ".date("d-M-y", strtotime($header->bm_etd));
           ?>
        </td>
        </tr>
				<tr>
          <td>Kade</td>
          <td>: </td>
        <td>{{$header->bm_kade}}</td>
        </tr>
				<tr>
          <td>Tipe Perdagangan</td>
          <td>: </td>
          <td>{{$header->bm_trade_name}}</td>
        </tr>
				<tr>
					<td>No. Request </td>
					<td>:</td>
					<td>{{$header->bm_no}}</td>
				</tr>
      </table>
    </td>
	</tr>
</table>

<?php if ($bl != "0") { ?>
<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<th rowspan="2" width="15%">NO BL</th>
		<th rowspan="2" width="15%">TL</th>
		<th rowspan="2" width="15%">Kemasan</th>
		<th rowspan="2" width="15%">BARANG</th>
    <th rowspan="2" width="5%">Satuan</th>
    <th colspan="2" width="15%">Qty</th>
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
    <th width="15%">Durasi / Lama Pemakaian Alat</th>
	</tr>

<?php foreach ($alat as $alat) { ?>
		<tr>
      <td style="padding-left:9px"><?php echo $alat["dtl_group_tariff_name"]; ?></td>
			<td style="border-right: 0;padding-left:9px"><?php echo $alat["dtl_equipment"]; ?></td>
			<td style="text-align:center"><?php echo $alat["dtl_unit_name"]; ?></td>
			<td style="text-align:center"><?php echo number_format($alat["dtl_eq_qty"]) ?></td>
			<td style="text-align:center"><?php echo number_format($alat["dtl_qty"]); ?></td>
		</tr>
<?php }} ?>
</table>
<br>
<table style="border-collapse:collapse; font-size:8px;float:right;text-align:center">
	<tr><td>Banten,
	<?php
	$originalDate = $header->bm_date;
	$newDate = date("d-M-y", strtotime($originalDate));
	echo strtoupper($newDate);
	?></td></tr>
	<tr><td>A.N. GENERAL MANAGER<br>DEPUTY GM KEUANGAN & SDM</td></tr>
	<tr><td><div style="margin-top:50px"><u>Ambarwati Legina</u></div></td></tr>
	<tr><td>NIPP. 285047354</td></tr>
</table>

<div style="position:absolute;bottom:20px;font-size:11px; width:100%">
	{{$branch->branch_name}} <br>{{$branch->branch_address}}
	<div style="margin-top:50px;font-size:8px">
    {{$header->real_no}}
	</div>
</div>
<p style="position:absolute;right:0px;bottom:15px;font-size:8px">Print Date : <?php echo date("d-M-Y H:s:i")." | Page 1/1"; ?></p>
@endforeach
@endforeach
</body>
</html>



</body>
</html>
