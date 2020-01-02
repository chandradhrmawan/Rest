<html>
<head>
	<meta http-equiv="Content-Type" content="text/html; charset=utf-8"/>
	<style>
		 body{
			 width:100%;
			 margin:0 auto;
			 font-family: "Arial",Sans-serif;
       padding:20px;
		 }
		 @media print {
        body {
					width:100%;
					margin:0 auto;
          padding:20px;
					font-family: "Arial", Sans-serif;
				}
      }
	</style>
</head>
<body>
		@foreach($header as $header)
    @foreach($request as $request)
		@foreach($branch as $branch)
  <table width="100%" style="font-size:10px">
    <tr>
      <td width="10%"><img src="{{ url('/other/logo.jpg') }}" height="110"></td>
			<td width="55%">
				<div style="font-size:16px;"><b>PT. Pelabuhan Indonesia II (Persero)<br> Jl. Pasoso No.1, Tanjung Priok, Jakarta Utara 1430 </b></div>
        <p style="font-size:12px;">NPWP. 01.061.005.3-093.000</p>
				</td>
        <td style="vertical-align:top">ID BPRP : {{$header->bprp_no}}</td>
    </tr>
  </table>

<center style="width:100%;margin-top:20px;padding:5px;font-size:14px;font-weight:800">
  <u>BUKTI PEMAKAIAN RUANG PENUMPUKAN / JASA DERMAGA BARANG BONGKAR MUA</u>T
  </center>
<table  width="100%" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:12px;margin-top:20px;">
	<tr>
		<td style="vertical-align:top" width="55%">
      <table style="border-collapse:collapse; font-size:12px;">
        <tr>
          <td>1.a.</td>
          <td>Nomor Request</td>
          <td>:</td>
          <td>{{$header->bprp_req_no}}</td>
        </tr>
        <tr>
          <td>1.b.</td>
          <td>Tanggal Request</td>
          <td>:</td>
          <td>{{$header->bprp_create_date}}</td>
        </tr>
        <tr>
          <td>2.a.</td>
          <td>Penerima / Pengirim</td>
          <td>:</td>
          <td>{{$request->req_cust_name}}</td>
        </tr>
        <tr>
          <td>2.b.</td>
          <td>NPWP</td>
          <td>:</td>
          <td>{{$request->req_cust_npwp}}</td>
        </tr>
				<tr>
					<td>2.c.</td>
					<td>Customer ID</td>
					<td>:</td>
					<td>{{$request->req_cust_id}}</td>
				</tr>
				<tr>
					<td>2.d</td>
					<td>Customer Address</td>
					<td>:</td>
					<td>{{$request->req_cust_address}}</td>
				</tr>
        <tr>
          <td>3.a.</td>
          <td>No. Resi Muat / No. PEB</td>
          <td>:</td>
          <td>{{$request->req_pib_peb_no}}</td>
        </tr>
        <!-- <tr>
          <td>5.a.</td>
          <td>Pelabuhan Asal</td>
          <td>:</td>
          <td></td>
        </tr>
        <tr>
          <td>5.b.</td>
          <td>Pelabuhan Tujuan</td>
          <td>:</td>
          <td></td>
        </tr> -->
      </table>
    </td>
		<td>
			<table style="border-collapse:collapse; font-size:12px;">
        <tr>
          <td>4.</td>
          <td>Alih Lokasi (Overbrangen)</td>
        </tr>
        <tr>
          <td></td>
          <td>Dari</td>
          <td>:</td>
          <td>{{$header->bprp_voyin}}</td>
        </tr>
        <tr>
          <td></td>
          <td>Ke</td>
          <td>:</td>
          <td>{{$header->bprp_voyout}}</td>
        </tr>
        <tr>
          <td>5.</td>
          <td>Jenis Perdagangan</td>
          <td>:</td>
          <td>{{$header->bprp_trade_name}}</td>
        </tr>
        <tr>
          <td>6.</td>
          <td>Vessel / Voyage</td>
          <td>:</td>
          <td>{{$header->bprp_vessel_name}}</td>
        </tr>
        <tr>
          <td>7.</td>
          <td>Tgl. Tiba / Berangkat</td>
          <td>:</td>
          <td>
            <?php
            echo $header->bprp_eta." / ".$header->bprp_etd;
          	?>
          </td>
        </tr>
        <tr>
          <td>8.</td>
          <td>Agen Kapal</td>
          <td>:</td>
          <td>{{$header->bprp_kade_name}}</td>
        </tr>
        <!-- <tr>
          <td>11.</td>
          <td>No. BPRP Lanjutan</td>
          <td>:</td>
          <td></td>
        </tr>
        <tr>
          <td>12.</td>
          <td>Keterangan Lain</td>
          <td>:</td>
          <td></td>
        </tr> -->
      </table>
    </td>
	</tr>
</table>

<table border="1" style="border-collapse:collapse; font-size:12px;margin-top:20px;width:100%;text-align:center">
  <tr>
    <th rowspan="2">No</th>
    <th rowspan="2">Nomor BL</th>
    <th rowspan="2">Barang</th>
    <th rowspan="2">Sifat</th>
    <th rowspan="2">Stacking Area Type</th>
    <th rowspan="2">Stacking Area</th>
    <th colspan="2">Request</th>
    <th colspan="2">Actual In</th>
    <th colspan="2">Actual Out</th>
  </tr>
  <tr>
    <th>Qty</th>
    <th>Satuan</th>
    <th>Date</th>
    <th>Qty</th>
    <th>Date</th>
    <th>Qty</th>
  </tr>
  {{$no = 1}}
  @foreach($detail as $detail)
  <tr>
    <td>{{$no}}</td>
    <td>{{$detail->dtl_cmdty_name}}</td>
    <td>{{$detail->dtl_bl}}</td>
    <td>{{$detail->dtl_character_name}}</td>
    <td>{{$detail->dtl_stacking_type_name}}</td>
    <td>{{$detail->dtl_stacking_area_name}}</td>
    <td>{{$detail->dtl_req_qty}}</td>
    <td>{{$detail->dtl_req_unit_name}}</td>
    <td><?php  echo strtoupper(date("d-M-y", strtotime($detail->dtl_datein))); ?></td>
    <td>{{$detail->dtl_in_qty}}</td>
    <td><?php  echo strtoupper(date("d-M-y", strtotime($detail->dtl_dateout))); ?></td>
    <td>{{$detail->dtl_out_qty}}</td>
  </tr>
  {{$no++}}
  @endforeach
</table>
<table style="border-collapse:collapse; font-size:11px;margin-top:60px;float:right;text-align:center">
	<tr><td>Banten, <?php  echo strtoupper(date("d-M-y", strtotime($header->bprp_date))); ?></td></tr>
	<tr><td>A.N. GENERAL MANAGER<br>DEPUTY GM KEUANGAN & SDM</td></tr>
	<tr><td><div style="margin-top:50px"><u>Ambarwati Legina</u></div></td></tr>
	<tr><td>NIPP. 285047354</td></tr>
</table>

	<div style="position:absolute;bottom:20px;font-size:12px; width:100%">
		{{$branch->branch_name}} <br>{{$branch->branch_address}}
		<div style="margin-top:50px;font-size:11px">
				{{$header->bprp_no}}
		</div>
	</div>
	<p style="position:absolute;right:20px;bottom:0px;font-size:11px">Print Date : <?php echo date("d-M-Y H:s:i")." | Page 1/1"; ?></p>
	@endforeach
  @endforeach
	@endforeach
</body>
</html>



</body>
</html>
