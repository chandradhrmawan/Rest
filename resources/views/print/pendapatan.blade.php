<?php
// header("Content-type: application/vnd-ms-excel");
// header("Content-Disposition: attachment; filename=hasil.xls");
?>
<style type="text/css">
body{
  font-family: sans-serif;
}
table{
  margin: 20px auto;
  border-collapse: collapse;
  border: 1px thin #3c3c3c;
}
table th,
table td{
  border: 1px thin #3c3c3c;
  padding: 3px 8px;

}
a{
  background: blue;
  color: #fff;
  padding: 8px 10px;
  text-decoration: none;
  border-radius: 2px;
}
</style>
<table width="100%">
  <tr>
    <td style="border:0px"><img width="180" src="{{ url('/other/logo_ptp.png') }}"></td>
    <td style="border:0px"></td>
    <td  style="border:0px" colspan="5">
      <h1 style="text-align:center;font-size:16px">LAPORAN DETAIL PENDAPATAN<br/>
      <font style="font-size:12px;font-weight:200">20/12/2019 - 22/12/2019</font>
      </h1>
    </td>
  </tr>
</table>
<br>
	<table border="1" width="100%" align="center">
    <thead>
      <tr>
          <th rowspan="2">No</th>
          <th rowspan="2">Kemasan</th>
          <th rowspan="2">Komoditi</th>
          <th rowspan="2">Satuan</th>
          <th colspan="2">Realisasi Bulan ke-1</th>
          <th colspan="2">Realisasi Bulan ke-2</th>
      </tr>
      <tr>
        <th>Jumlah</th>
        <th>Pendapatan</th>
        <th>Jumlah</th>
        <th>Pendapatan</th>
      </tr>
      </thead>
      <tr align="center">
          <td>1</td>
          <td>Curah Kering</td>
          <td>Beras</td>
          <td>Ton</td>
          <td>1.500</td>
          <td>4.000</td>
          <td>1.500</td>
          <td>4.000</td>
      </tr>
</table>
<br>
<table width="100%">
  <tr align="right">
    <td style="border:0px" colspan="4"><h6>Print Date : <?php echo date("d/M/Y h:s:i"); ?></h6></td>
  </tr>
</table>
