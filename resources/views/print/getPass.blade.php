<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		 body{
			 width:100%;
			 margin:0 auto;
			 font-family: "Arial", Sans-serif;
			 font-size:9px;
		 }
		 @media print {
        body {
					width:100%;
					margin:0 auto;
					font-family: "Arial", Sans-serif;
					font-size:9px;
				}
      }

		@page { size: 80mm 297mm;margin: 0.2in; }
	</style>
</head>
<body>

@foreach($data as $data)
  <table width="100%" style="font-size:10px">
    <tr>
      <td style="width:10%"><img src="{{ url('/other/logo_ptp.png') }}" style="height:50px"></td>
      <td style="vertical-align:top"><b>PTP MULTIPURPOSE TERMINAL</b><hr><b>GET PASS {{$title}} CARGO</b></td>
    </tr>
  </table>

<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <b>BL NUMBER</b>
		 <div style="padding:5px;width:100%">
			 <img src="data:image/png;base64,<?php echo DNS1D::getBarcodePNG($data->tca_bl, 'C128B'); ?>" alt="barcode" style="width:200px;height:30px"/>
		 </div>
    </td>
	</tr>
	<tr style="text-align:center">
		<td>
      <div><b>TRUCK ID</b></div>
			<div style="padding:5px;width:100%">
				<img src="data:image/png;base64,<?php echo DNS1D::getBarcodePNG($data->tca_truck_id, 'C128B'); ?>" alt="barcode" style="width:200px;height:30px" />
			</div>
    </td>
	</tr>
  <!-- <tr>
    <td colspan="3">
      <div style="margin-top:40px"><b>Cargo Name</b><br>JAGUNG</div>
    </td>
  </tr> -->
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:20px">
  <tr>
    <td width="35%">HS Code<br>{{$data->tca_hs_code}}</td>
    <td width="35%">Qty/Unit<br>{{$data->tca_qty}}/{{$data->tca_unit_name}}</td>
    <td>Package<br>{{$data->tca_pkg_name}}</td>
  </tr>
  <tr>
    <td>No Urut<br>1/1</td>
    <td>IMO Class<br></td>
    <td>Status TL<br>
			YES
		</td>
  </tr>
  <tr>
    <td>Vessel<br>{{$data->tca_vessel_name}}</td>
    <td>Vessel Code<br>{{$data->tca_vessel_code}}</td>
    <td>Customer<br>{{$data->tca_cust_name}}</td>
  </tr>
  <tr>
    <td>No Do<br>{{$data->dtl_id}}</td>
    <td>Tgl BL<br>{{$data->tca_create_date}}</td>
    <td>No Request<br>{{$data->tca_req_no}}</td>
  </tr>
  <tr>
    <td>Paid Thru<br>28-JAN-18 23:59</td>
    <td>Plat No Truck<br>B6666TU</td>
    <td>Truck ID<br>{{$data->tca_truck_id}}</td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:40px">
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
<center style="margin-top:30px;font-size:9px;"><b>Please fold here - Do not tear <br>(Silahkan lipat di sini - Jangan disobek) </b></center>
<hr width="100%" style="border-style:dotted">
<center style="font-size:11px;margin-top:20px"><b>Gate Copy </b></center>

<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <b>BL NUMBER</b>
		 <div style="padding:5px;width:100%">
			 <img src="data:image/png;base64,<?php echo DNS1D::getBarcodePNG($data->tca_bl, 'C128B'); ?>" alt="barcode" style="width:200px;height:30px"/>
		 </div>
    </td>
	</tr>
	<tr style="text-align:center">
		<td>
      <div><b>TRUCK ID</b></div>
			<div style="padding:5px;width:100%">
				<img src="data:image/png;base64,<?php echo DNS1D::getBarcodePNG($data->tca_truck_id, 'C128B'); ?>" alt="barcode" style="width:200px;height:30px" />
			</div>
    </td>
	</tr>
  <!-- <tr>
    <td colspan="3">
      <div style="margin-top:40px"><b>Cargo Name</b><br>JAGUNG</div>
    </td>
  </tr> -->
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:20px">
<tr>
	<td width="35%">HS Code<br>{{$data->tca_hs_code}}</td>
	<td width="35%">Qty/Unit<br>{{$data->tca_qty}}/{{$data->tca_unit_name}}</td>
	<td>Package<br>{{$data->tca_pkg_name}}</td>
</tr>
<tr>
	<td>No Urut<br>1/1</td>
	<td>IMO Class<br></td>
	<td>Status TL<br>
		YES
	</td>
</tr>
<tr>
	<td>Vessel<br>{{$data->tca_vessel_name}}</td>
	<td>Vessel Code<br>{{$data->tca_vessel_code}}</td>
	<td>Customer<br>{{$data->tca_cust_name}}</td>
</tr>
<tr>
	<td>No Do<br>{{$data->dtl_id}}</td>
	<td>Tgl BL<br>{{$data->tca_create_date}}</td>
	<td>No Request<br>{{$data->tca_req_no}}</td>
</tr>
<tr>
	<td>Paid Thru<br>28-JAN-18 23:59</td>
	<td>Plat No Truck<br>B6666TU</td>
	<td>Truck ID<br>{{$data->tca_truck_id}}</td>
</tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:40px">
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
@endforeach
</body>
</html>
