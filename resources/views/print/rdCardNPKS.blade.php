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

    .page_break { page-break-before: always; }

		@page { size: 80mm 297mm;margin: 0.2in; }
	</style>
</head>
<body>
  <?php $no = 1; ?>
  @foreach($detail as $detail)
  <table width="100%" style="font-size:10px">
    <tr>
      <td style="width:10%"><img src="{{ url('/other/logo_ptp.png') }}" style="height:50px"></td>
      <td style="vertical-align:top"><b>PTP MULTIPURPOSE TERMINAL</b><hr><b>{{$title}} CARD</b></td>
    </tr>
  </table>

<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <b>Request Number</b>
		 <div style="padding:5px;width:100%">
       {{$header[$config["head_primery"]]}}
		 </div>
    </td>
	</tr>
</table>

<!-- <img src="data:image/png;base64,<?php //echo DNS1D::getBarcodePNG($header[$config["head_primery"]], 'C128B'); ?>" alt="barcode" style="width:200px;height:30px"/> -->

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:20px">
  <tr>
    <td width="35%">No Container<br><b>{{$detail[$config['DTL_BL']]}}</td>
    <td width="35%">Danger<br><b>
			<?php
			if ($detail[$config['DTL_CHARACTER']] == 'N') {
        echo "No";
      } else {
				echo "Yes";
      }
			 ?>
		</td>
    <td>Commodity<br><b>
      <?php
      if (empty($detail[$config['DTL_CMDTY_NAME']])) {
        echo "-";
      } else {
        echo $detail[$config['DTL_CMDTY_NAME']];
      } ?>
    </td>
  </tr>
  <tr>
    <td>Container Size<br><b>{{$detail[$config['DTL_CONT_SIZE']]}}</td>
    <td>Container Type<br><b>{{$detail[$config['DTL_CONT_TYPE']]}}</td>
    <td>Container Status<br><b>
      <?php
      if (empty($detail[$config['DTL_CONT_STATUS']])) {
        echo "-";
      } else {
        echo $detail[$config['DTL_CONT_STATUS']];
      } ?>
    </td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:10px">
  <tr>
    <td width="20%">
      <?php echo DNS2D::getBarcodeHTML($header[$config["head_primery"]], "QRCODE", 2,2); ?>
    </td>
    <td width="80%" style="font-size:8px">
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
<table width="100%" style="font-size:10px;margin-top:20px">
  <tr>
    <td style="width:10%"><img src="{{ url('/other/logo_ptp.png') }}" style="height:50px"></td>
    <td style="vertical-align:top"><b>PTP MULTIPURPOSE TERMINAL</b><hr><b>{{$title}} CARD</b></td>
  </tr>
</table>

<table  width="100%" align="center" border="0" cellspacing="1" cellpadding="1" style="border-collapse:collapse; font-size:10px;margin-top:20px">
	<tr style="text-align:center">
		<td>
      <b>Request Number</b>
		 <div style="padding:5px;width:100%">
       {{$header[$config["head_primery"]]}}
		 </div>
    </td>
	</tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:20px">
  <tr>
    <td width="35%">No Container<br><b>{{$detail[$config['DTL_BL']]}}</td>
    <td width="35%">Danger<br><b>
			<?php
			if ($detail[$config['DTL_CHARACTER']] == 'N') {
				echo "No";
			} else {
				echo "Yes";
			}
			 ?>
		</td>
    <td>Commodity<br><b>
      <?php
      if (empty($detail[$config['DTL_CMDTY_NAME']])) {
        echo "-";
      } else {
        echo $detail[$config['DTL_CMDTY_NAME']];
      } ?>
    </td>
  </tr>
  <tr>
    <td>Container Size<br><b>{{$detail[$config['DTL_CONT_SIZE']]}}</td>
    <td>Container Type<br><b>{{$detail[$config['DTL_CONT_TYPE']]}}</td>
    <td>Container Status<br><b>
      <?php
      if (empty($detail[$config['DTL_CMDTY_NAME']])) {
        echo "-";
      } else {
        echo $detail[$config['DTL_CMDTY_NAME']];
      } ?>
    </td>
  </tr>
</table>

<table width="100%" style="border-collapse:collapse; font-size:9px;margin-top:10px">
  <tr>
    <td width="20%">
      <?php echo DNS2D::getBarcodeHTML($header[$config["head_primery"]], "QRCODE", 2,2); ?>
    </td>
    <td width="80%" style="font-size:8px">
      <b>Keterangan</b>
      <br>1. Kartu ini harap dibawa saat melakukan gate in
      <br>2. Harap perhatikan closing time dan paid thru
      <br>3. Periksa kembali cargo yang tertera pada kartu
      <br>4. Bila kartu ini hilang harap segera melapor ke IPC
      <br>5. Bila menemukan kartu ini harap menyerahkan ke IPC
    </td>
  </tr>
</table>
<?php
if ($no < $page) { ?>
  <div class="page_break"></div>
<?php $no++; } ?>
@endforeach
</body>
</html>
