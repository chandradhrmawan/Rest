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
  <table width="100%">
    <tr>
      <td width="13%"><img src="{{ url('/other/logo.jpg') }}" height="50"></td>
      <td style="vertical-align:top">
        <div><b>PT. PELABUHAN INDONESIA II <br> TERMINAL BANTEN</b></div>
        </td>
      <td style="vertical-align:top;text-align:right">
        <div><b>Get Pass DELIVERY CARGO</b></div>
      </td>
    </tr>
  </table>


@foreach($header as $header)
@foreach($detail as $detail)
<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:9;margin-top:20px">
	<tr style="text-align:center">
		<td colspan="2">
      <b>BL NUMBER</b>
		 <div style="padding-left:150px"><?php echo DNS1D::getBarcodeHTML($detail["dtl_req_bl"], "C39",1,33);?></div>
    </td>
		<td style="text-align:left">
      <div style="margin-left:40px"><b>TRUCK ID</b></div>
			<?php echo DNS1D::getBarcodeHTML("1234567", "C39",1,33);?>
    </td>
	</tr>
  <tr>
    <td colspan="3">
      <div style="margin-top:40px"><b>Cargo Name</b><br>{{$detail["dtl_cmdty_name"]}}</div>
    </td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9;margin-top:20px">
  <tr>
    <td width="35%">HS Code<br>1701</td>
    <td width="35%">Qty/Unit<br>{{$detail["dtl_qty"]}} {{$detail["dtl_unit_name"]}}</td>
    <td>Package<br>{{$detail["dtl_pkg_name"]}}</td>
  </tr>
  <tr>
    <td>No Urut<br>1/1</td>
    <td>IMO Class<br></td>
    <td>Status TL<br>
			<?php if ($detail["dtl_req_tl"] == 'Y') { echo "Yes"; } else { echo "No"; } ?>
		</td>
  </tr>
  <tr>
    <td>Vessel<br>{{$header["req_vessel_name"]}}</td>
    <td>Voyage<br>04/05</td>
    <td>Customer<br>{{$detail["dtl_cust_name"]}}</td>
  </tr>
  <tr>
    <td>No Do<br>1234</td>
    <td>Tgl BL<br>{{$detail["dtl_create_date"]}}</td>
    <td>No Request<br>{{$detail["dtl_req_bl"]}}</td>
  </tr>
  <tr>
    <td>Paid Thru<br>28-JAN-18 23:59</td>
    <td>Plat No Truck<br>B6666TU</td>
    <td>Truck ID<br>5T627</td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9;margin-top:40px">
  <tr>
    <td>
      <b>Keterangan</b>
      <br>1. Kartu ini harap dibawa saat melakukan gate in
      <br>2. Harap perhatikan closing time dan paid thru
      <br>3. Periksa kembali cargo yang tertera pada kartu
      <br>4. Bila kartu ini hilang harap segera melapor ke IPC
      <br>5. Bila menemukan kartu ini harap menyerahkan ke IPC
    </td>
  </tr>
</table>
<center style="margin-top:30px;font-size:12px;"><b>Please fold here - Do not tear (Silahkan lipat di sini - Jangan disobek) </b></center>
<hr width="100%" style="border-style:dotted">
<center style="font-size:12px;"><b>Gate Copy </b></center>
<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:9;margin-top:20px">
	<tr style="text-align:center">
		<td colspan="2">
      <b>BL NUMBER</b>
		 <div style="padding-left:150px"><?php echo DNS1D::getBarcodeHTML($detail["dtl_req_bl"], "C39",1,33);?></div>
    </td>
		<td style="text-align:left">
      <div style="margin-left:40px"><b>TRUCK ID</b></div>
			<?php echo DNS1D::getBarcodeHTML("1234567", "C39",1,33);?>
    </td>
	</tr>
  <tr>
    <td colspan="3">
      <div style="margin-top:40px"><b>Cargo Name</b><br>{{$detail["dtl_cmdty_name"]}}</div>
    </td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9;margin-top:20px">
  <tr>
    <td width="35%">HS Code<br>1701</td>
    <td width="35%">Qty/Unit<br>{{$detail["dtl_qty"]}} {{$detail["dtl_unit_name"]}}</td>
    <td>Package<br>{{$detail["dtl_pkg_name"]}}</td>
  </tr>
  <tr>
    <td>No Urut<br>1/1</td>
    <td>IMO Class<br></td>
    <td>Status TL<br>
			<?php if ($detail["dtl_req_tl"] == 'Y') { echo "Yes"; } else { echo "No"; } ?>
		</td>
  </tr>
  <tr>
    <td>Vessel<br>{{$header["req_vessel_name"]}}</td>
    <td>Voyage<br>04/05</td>
    <td>Customer<br>{{$detail["dtl_cust_name"]}}</td>
  </tr>
  <tr>
    <td>No Do<br>1234</td>
    <td>Tgl BL<br>{{$detail["dtl_create_date"]}}</td>
    <td>No Request<br>{{$detail["dtl_req_bl"]}}</td>
  </tr>
  <tr>
    <td>Paid Thru<br>28-JAN-18 23:59</td>
    <td>Plat No Truck<br>B6666TU</td>
    <td>Truck ID<br>5T627</td>
  </tr>
</table>
@endforeach
@endforeach
</body>
</html>
