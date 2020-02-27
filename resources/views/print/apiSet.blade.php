<!DOCTYPE html>
<html lang="en" dir="ltr">
  <head>
    <meta charset="utf-8">
    <title></title>
    <style media="screen">
      .field {
        vertical-align:top;text-align:center
      }
    </style>
  </head>
  <body>
    <table style="font-size:12px" border="1" width="100%">
      <tr>
        <th>No</th>
        <th width="8%">Nota Id</th>
        <th width="8%">Branch Id</th>
        <th width="8%">Branch Code</th>
        <th width="8%">Flag Status</th>
        <th width="8%">Service Code</th>
        <th>Api Set</th>
      </tr>
      <?php $no=1; ?>
      @foreach($data as $data)
      <tr>
        <td class="field"><?php echo $no++; ?></td>
        <td class="field">{{$data->nota_id}}</td>
        <td class="field">{{$data->branch_id}}</td>
        <td class="field">{{$data->branch_code}}</td>
        <td class="field">{{$data->flag_status}}</td>
        <td class="field">{{$data->service_code}}</td>
        <td><textarea style="width:100%;height:200px">{{$data->api_set}}</textarea></td>
      </tr>
      @endforeach

    </table>
  </body>
</html>
