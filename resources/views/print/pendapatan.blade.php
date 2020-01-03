<?php
$now = date('d_m_Y');
$rand = rand(10, 100000);
header("Content-type: application/vnd-ms-excel");
header("Content-Disposition: attachment; filename=$now"."_pendapatan_$rand.xls");
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
    <td  style="border:0px" colspan="4">
      <h1 style="text-align:center;font-size:16px">LAPORAN DETAIL PENDAPATAN<br/>
        <font style="font-size:12px;font-weight:200">
          <?php if ($start != 0) { echo date("d/m/Y", strtotime($start)); } else { echo "------"; } ?> s/d <?php if ($end != 0) { echo $end; } else { echo "-----"; } ?>
        </font>
      </h1>
    </td>
  </tr>
</table>
<br>
<table border="1" width="100%" align="center">
  <tr>
    <th>Commodity</th>
    <th>Satuan</th>
    <th>Tanggal Nota</th>
    <th>Jumlah</th>
    <th colspan="2">Nota DPP</th>
  </tr>

  <?php
  for ($i=0; $i < count($kemasan); $i++) {
  ?>
  <tr>
    <th colspan="6" style="background: red;color:#fff;text-align:left;">{{$kemasan[$i]}}</th>
  </tr>
  <?php for ($j=0; $j < count($data[$kemasan[$i]]); $j++) { ?>
    <tr>
      <td><?php echo $data[$kemasan[$i]][$j]->komoditi; ?></td>
      <td style="text-align:center"><?php echo $data[$kemasan[$i]][$j]->satuan; ?></td>
      <td style="text-align:center"><?php echo date("d-m-Y", strtotime($data[$kemasan[$i]][$j]->tgl_nota)); ?></td>
      <td style="text-align:center"><?php echo number_format($data[$kemasan[$i]][$j]->qty); ?></td>
      <td style="border-right:0px">Rp</td>
      <td style="text-align:right;border-left:0px;"><?php echo number_format($data[$kemasan[$i]][$j]->dpp); ?></td>
    </tr>
  <?php } ?>
<?php } ?>
</table>
<br>
<table width="100%">
  <tr align="right">
    <td style="border:0px" colspan="6"><h6>Print Date : <?php echo date("d/M/Y h:s:i"); ?></h6></td>
  </tr>
</table>
