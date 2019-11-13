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
	<?php if ($header->uper_paid == "Y") { ?>
		<img src="{{ url('/other/belum_lunas.png')}}" alt="" style="position:absolute;opacity:0.3;margin-left:200px;transform: rotate(70deg);margin-top:100px;width:50%">
	<?php } else { ?>
		<img src="{{ url('/other/lunas.png') }}" alt="" style="position:absolute;opacity:0.3;margin-left:100px;margin-top:100px;transform: rotate(20deg);">
	<?php } ?>
	@foreach($branch as $branch)
  <table width="100%" style="font-size:9px">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo.jpg') }}" height="50"></td>
      <td width="55%">
        <div<b>{{$branch->branch_name}} <br>{{$branch->branch_address}} </b><br>NPWP.{{$branch->branch_npwp}}</div>
        </td>
      <td style="vertical-align:top;text-align:right">
        <table style="border-collapse:collapse; font-size:9px;">
          <tr>
            <td>No. Uper</td>
            <td>: {{$header->uper_no}}</td>
          </tr>
					<tr>
						<td>Tgl. Uper</td>
						<td>: {{$header->uper_date}}</td>
					</tr>
          <tr>
            <td>No. Request</td>
            <td>: {{$header->uper_req_no}}</td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
	@endforeach

<center style="width:100%;background-color:orange;color:#fff;margin-top:20px">UPER USTER</center>
<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:8px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <table style="border-collapse:collapse; font-size:9px;">
        <tr>
          <td colspan="3">
            <font style="font-size:9;text-align:left"><b>Pengguna Jasa</b></font><br><br>
          </td>
        </tr>
        <tr>
          <td>Nama</td>
          <td>: </td>
          <td>{{$header->uper_cust_name}}</td>
        </tr>
        <tr>
          <td>Alamat</td>
          <td>: </td>
          <td>{{$header->uper_cust_address}}</td>
        </tr>
        <tr>
          <td>NPWP</td>
          <td>: </td>
          <td>{{$header->uper_cust_npwp}}</td>
        </tr>
      </table>
    </td>
		<td>
      <table style="border-collapse:collapse; font-size:9px;">
        <tr>
          <td>Nama kapal</td>
          <td>: </td>
        <td>{{$header->uper_vessel_name}}</td>
        </tr>
        <tr>
          <td>No.PBM </td>
          <td>: </td>
          <td>{{$header->uper_pbm_id}}</td>
        </tr>
      </table>
    </td>
	</tr>
	@endforeach
</table>
<table  width="100%" align="center" border="1" cellspacing="1" cellpadding="2" style="border-collapse:collapse; font-size:8px;margin-top:20px">
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
  <tr style="background:yellow;">
    <td style="border-right: 0;padding-left:9px">{{$bl->dtl_bl}}</td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0;text-align:center"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-right: 0;border-left:0"></td>
    <td style="border-left:  0;"></td>
  </tr>
	@foreach($data as $data)
	<tr>
    <td style="padding-left:9px">{{$data->dtl_service_type}}</td>
    <td><?php if ($data->dtl_bm_tl == "N") { echo "NON-TL"; } else { echo "TL"; } ?></td>
    <td>{{$data->dtl_package}}</td>
    <td>{{$data->dtl_commodity}}</td>
    <td style="text-align:center">{{$data->dtl_unit_name}}</td>
		<?php if ($data->dtl_bm_type == "Bongkar") { ?>
			<td style="text-align:center">{{$data->dtl_qty}}</td>
			<td></td>
		<?php } else { ?>
			<td></td>
			<td style="text-align:center">{{$data->dtl_qty}}</td>
		<?php } ?>
    <td style="text-align:right">{{number_format($data->dtl_tariff)}}</td>
    <td style="text-align:right">{{number_format($data->dtl_amount)}}</td>
  </tr>
	@endforeach
	@endforeach
  <tr style="background:yellow">
    <td style="border-right: 0;padding-left:9px" colspan="9">Sewa Alat</td>
  </tr>
	<?php if ($sewa == "0") { ?>
		<tr>
			<td colspan="9" style="padding-left:9px">Tidak Ada Sewa Alat</td>
		</tr>
	<?php } else { ?>
		@foreach($sewa as $sewa)
		  <tr>
		    <td style="border-right: 0;padding-left:9px">{{$sewa->dtl_equipment}}</td>
		    <td style="border-right: 0;border-left:0"></td>
		    <td style="border-right: 0;border-left:0"></td>
		    <td style="border-right: 0;border-left: style="text-align:center"0"></td>
		    <td style="text-align:center">{{$sewa->dtl_unit_name}}</td>
		    <?php if ($sewa->dtl_bm_type == "Bongkar") { ?>
		      <td style="text-align:center">{{$sewa->dtl_qty}}</td>
		      <td></td>
		    <?php } else { ?>
		      <td></td>
		      <td style="text-align:center">{{$sewa->dtl_qty}}</td>
		    <?php } ?>
		    <td style="text-align:right">{{number_format($sewa->dtl_tariff)}}</td>
		    <td style="text-align:right">{{number_format($sewa->dtl_amount)}}</td>
		  </tr>
		@endforeach
	<?php } ?>

	<tr style="background:yellow">
    <td style="border-right: 0;padding-left:9px" colspan="9">Retribusi Alat</td>
  </tr>
	<?php if ($retribusi == "0") { ?>
		<tr>
			<td colspan="9" style="padding-left:9px">Tidak Ada Retribusi Alat</td>
		</tr>
	<?php } else { ?>
		@foreach($retribusi as $retribusi)
		  <tr>
		    <td style="border-right: 0;padding-left:9px">{{$retribusi->dtl_equipment}}</td>
		    <td style="border-right: 0;border-left:0"></td>
		    <td style="border-right: 0;border-left:0"></td>
		    <td style="border-right: 0;border-left: style="text-align:center"0"></td>
		    <td style="text-align:center">{{$retribusi->dtl_unit_name}}</td>
		    <?php if ($retribusi->dtl_bm_type == "Bongkar") { ?>
		      <td style="text-align:center">{{$retribusi->dtl_qty}}</td>
		      <td></td>
		    <?php } else { ?>
		      <td></td>
		      <td style="text-align:center">{{$retribusi->dtl_qty}}</td>
		    <?php } ?>
		    <td style="text-align:right">{{number_format($retribusi->dtl_tariff)}}</td>
		    <td style="text-align:right">{{number_format($retribusi->dtl_amount)}}</td>
		  </tr>
		@endforeach
	<?php } ?>
  <tr>
    <td style="border-right: 0;border-bottom: 0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-bottom: 0;border-left:0" colspan="2">DPP</td>
    <td style="border-right: 0;border-bottom: 0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-bottom: 0;text-align:right">{{number_format($dpp)}}</td>
  </tr>
  <tr>
    <td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">PPN 10%</td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;border-bottom:0;text-align:right">{{number_format($ppn)}}</td>
  </tr>
	<tr>
    <td style="border-right: 0;border-top: 0;border-bottom:0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0" colspan="2">Administrasi</td>
    <td style="border-right: 0;border-top: 0;border-bottom:0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;border-bottom:0;text-align:right">{{number_format(0)}}</td>
  </tr>
  <tr>
    <td style="border-right: 0;border-top: 0;width:50%" colspan="5"></td>
    <td style="border-right: 0;border-top: 0;border-left:0" colspan="2">Grand Total</td>
    <td style="border-right: 0;border-top: 0;border-left:0;text-align:right;padding-right:9px">IDR</td>
    <td style="border-left:  0;border-top: 0;text-align:right">{{number_format($dpp+$ppn)}}</td>
  </tr>
</table>
<p style="font-size:9px">Terbilang : <font style="text-transform:capitalize">{{$terbilang}}</font></p>
<table style="border-collapse:collapse; font-size:8px;margin-top:60px;float:right;text-align:center">
	<tr><td>Palembang, 29 Agustus 2019</td></tr>
	<tr><td>DGM Keuangan & Administrasi</td></tr>
	<tr><td><div style="margin-top:50px"><u>Clara Primasari Henryanto</u></div></td></tr>
	<tr><td>NIPP. 287117773</td></tr>
</table>
</body>
</html>
