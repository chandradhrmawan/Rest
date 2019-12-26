<?php
$now = date('d_m_Y');
$rand = rand(10, 100000);
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$now"."_debitur_$rand.xls");
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
 .str{ mso-number-format:\@; }
</style>
<table width="100%">
  <tr>
    <td style="border:0px"><img width="180" src="{{ url('/other/logo_ptp.png') }}"></td>
    <td style="border:0px"></td>
    <td  style="border:0px" colspan="2">
      <h1 style="text-align:center;font-size:16px">LAPORAN DEBITUR<br/>
        <font style="font-size:12px;font-weight:200">
          <?php if ($start != 0) { echo date("d/m/Y", strtotime($start)); } else { echo "------"; } ?> s/d <?php if ($end != 0) { echo $end; } else { echo "-----"; } ?></font>
      </h1>
    </td>
  </tr>
</table>
<br>
	<table border="1" width="100%" align="center">
    <thead>
      <tr>
          <th>No Nota</th>
          <th>Debitur</th>
          <th>Layanan</th>
          <th colspan="2">Pendapatan</th>
      </tr>
      </thead>
      @foreach($result as $data)
      <tr align="center">
          <td class="str"><?php echo $data->nota_no; ?></td>
          <td style="text-align:left">{{$data->nota_cust_name}}</td>
          <td>{{$data->layanan}}</td>
          <td style="text-align:left;border-right:0px">Rp</td>
          <td style="text-align:right;border-left:0px">{{number_format($data->nota_dpp)}}</td>
      </tr>
      @endforeach
</table>
<br>
<table width="100%">
  <tr align="right">
    <td style="border:0px" colspan="8"><h6>Print Date : <?php echo date("d/M/Y h:s:i"); ?></h6></td>
  </tr>
</table>
