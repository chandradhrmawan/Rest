<?php
$now = date('d_m_Y');
$rand = rand(10, 100000);
header("Content-type: application/vnd.ms-excel");
header("Content-type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet");
header("Content-Disposition: attachment; filename=$now"."_trafik_$rand.xls");
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
      <h1 style="text-align:center;font-size:16px">LAPORAN TRAFIK, PRODUKSI, DAN PENDAPATAN<br/>
        <font style="font-size:12px;font-weight:200">
          <?php //if ($start != 0) { echo date("d/m/Y", strtotime($start)); } else { echo "------"; } ?> s/d <?php //if ($end != 0) { echo $end; } else { echo "-----"; } ?>
        </font>
      </h1>
    </td>
  </tr>
</table>
<br>
<table border="1" width="100%" align="center">
  <tr>
    <th rowspan="2">No</th>
    <th rowspan="2">Nama Kapal</th>
    <th rowspan="2">UKK</th>
    <th colspan="2">Tanggal Kegiatan</th>
    <th rowspan="2">Nama Debitur</th>
    <th rowspan="2">PBM</th>
    <th>Jenis Aktifitas</th>
    <th colspan="5">Pendapatan Stevedoring</th>
    <th colspan="5">Pendapatan Cargodoring</th>
    <th colspan="5">Pendapatan Angkutan Langsung</th>
    <th colspan="5">Pendapatan Sewa Alat</th>
    <th colspan="5">Pendapatan Retribusi Alat</th>
    <th colspan="5">Pendapatan PFS</th>
  </tr>
  <tr>
    <th>Mulai</th>
    <th>Selesai</th>
    <th>B/M/I/E</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
    <th>No Pranota</th>
    <th>Rp</th>
    <th>Jenis Barang / Alat</th>
    <th>Satuan</th>
    <th>Qty</th>
  </tr>
  <?php $no = 1; ?>
  @foreach ($data as $data)
  <tr>
    <td><?php echo $no;$no++; ?></td>
    <td>{{$data->nama_kapal}}</td>
    <td>{{$data->nama_kapal}}</td>
    <td>{{$data->ukk}}</td>
    <td>{{$data->nota_date}}</td>
    <td>{{$data->nota_date}}</td>
    <td>{{$data->debitur}}</td>
    <td>{{$data->pbm}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->steve_amt)}}</td>
    <td>{{$data->steve_cmdt}}</td>
    <td>{{$data->steve_unit}}</td>
    <td>{{number_format($data->steve_qty)}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->cargo_amt)}}</td>
    <td>{{$data->cargo_cmdt}}</td>
    <td>{{$data->cargo_unit}}</td>
    <td>{{number_format($data->cargo_qty)}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->ang_lang_amt)}}</td>
    <td>{{$data->ang_lang_cmdt}}</td>
    <td>{{$data->ang_lang_unit}}</td>
    <td>{{number_format($data->ang_lang_qty)}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->sewa_alat_amt)}}</td>
    <td>{{$data->sewa_alat_cmdt}}</td>
    <td>{{$data->sewa_alat_unit}}</td>
    <td>{{number_format($data->sewa_alat_qty)}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->retr_alat_amt)}}</td>
    <td>{{$data->retr_alat_cmdt}}</td>
    <td>{{$data->retr_alat_unit}}</td>
    <td>{{number_format($data->retr_alat_qty)}}</td>
    <td>{{$data->nota_no}}</td>
    <td>{{number_format($data->pfs_amt)}}</td>
    <td>{{$data->pfs_cmdt}}</td>
    <td>{{$data->pfs_unit}}</td>
    <td>{{number_format($data->pfs_qty)}}</td>
  </tr>
  @endforeach

</table>
<br>
<table width="100%">
  <tr align="right">
    <td style="border:0px" colspan="6"><h6>Print Date : <?php echo date("d/M/Y h:s:i"); ?></h6></td>
  </tr>
</table>
