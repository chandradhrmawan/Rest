<?php
$now = date('d_m_Y');
$rand = rand(10, 100000);
header("Content-type: application/vnd.ms-excel");
header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=$now"."_pendapatan_$rand.xls");
?>
<style type="text/css">
body{
  font-family: sans-serif;
  font-size: 12px;
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
  font-size:12px;

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
    <td style="border:0px"><img width="120" src="http://sdnpakis5sby.sch.id/logo.jpg"></td>
    <td style="border:0px"></td>
    <td  style="border:0px" colspan="27">
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
    <th rowspan="2">No</th>
    <th rowspan="2">Kemasan</th>
    <th rowspan="2">Komoditi</th>
    <th rowspan="2">Satuan</th>
    <th colspan="2">Realisasi Bulan Ke-1</th>
    <th colspan="2">Realisasi Bulan Ke-2</th>
    <th colspan="2">Realisasi Bulan Ke-3</th>
    <th colspan="2">Realisasi Bulan Ke-4</th>
    <th colspan="2">Realisasi Bulan Ke-5</th>
    <th colspan="2">Realisasi Bulan Ke-6</th>
    <th colspan="2">Realisasi Bulan Ke-7</th>
    <th colspan="2">Realisasi Bulan Ke-8</th>
    <th colspan="2">Realisasi Bulan Ke-9</th>
    <th colspan="2">Realisasi Bulan Ke-10</th>
    <th colspan="2">Realisasi Bulan Ke-11</th>
    <th colspan="2">Realisasi Bulan Ke-12</th>
  </tr>
  <tr>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
    <th>Jumlah</th>
    <th>Pendapatan</th>
  </tr>
  <?php
  for ($i=0; $i < count($kemasan); $i++) {
  ?>
  <tr>
    <td rowspan="<?php echo count($data[$kemasan[$i]])+1; ?>"><?php echo $i+1; ?></td>
    <td rowspan="<?php echo count($data[$kemasan[$i]])+1; ?>">{{$kemasan[$i]}}</td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
    <td></td>
  <?php for ($j=0; $j < count($data[$kemasan[$i]]); $j++) { ?>
    <tr>
      <td><?php echo $data[$kemasan[$i]][$j]->dtl_commodity; ?></td>
      <td style="text-align:center"><?php echo $data[$kemasan[$i]][$j]->dtl_unit_name; ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jan_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jan_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->feb_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->feb_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->mar_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->mar_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->apr_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->apr_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->may_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->may_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jun_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jun_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jul_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->jul_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->aug_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->aug_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->sep_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->sep_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->oct_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->oct_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->nov_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->nov_amt); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->dec_qty); ?></td>
      <td style="text-align:right"><?php echo number_format($data[$kemasan[$i]][$j]->dec_amt); ?></td>
    </tr>
  <?php }} ?>
<tr>
  <td></td>
</tr>

</table>
<br>
<table width="100%">
  <tr align="right">
    <td style="border:0px" colspan="6"><h6>Print Date : <?php echo date("d/M/Y h:s:i"); ?></h6></td>
  </tr>
</table>
