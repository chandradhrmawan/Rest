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
    <h1>TS_NOTA | <font color="red">{{$label}}</font></h1>
    <form action="{{url('apiPost')}}" method="post" >
      <select class="" name="id">
        @foreach ($search as $search)
          <option value="{{$search->nota_id}}">{{$search->nota_name}}</option>
        @endforeach
        <input type="submit" name="" value="Search">
      </select>
    </form>

    <form action="{{url('updateTsNota')}}" method="post" >
    <table style="font-size:12px" border="1" width="100%">
      <tr>
        <th width="8%">Nota Id</th>
        <th width="8%">Nota Label</th>
        <th width="8%">Branch Id</th>
        <th width="8%">Branch Code</th>
        <th width="8%">Flag Status</th>
        <th width="8%">Service Code</th>
        <th>Api Set</th>
      </tr>
      <?php $no=1; ?>
      @foreach($data as $data)
      <tr>
        <td class="field">{{$data->nota_id}}</td>
        <td class="field">{{$data->nota_label}}</td>
        <td class="field">{{$data->branch_id}}</td>
        <td class="field">{{$data->branch_code}}</td>
        <td class="field">{{$data->flag_status}}</td>
        <td class="field">{{$data->service_code}}</td>
        <td><textarea style="width:100%;height:200px" name="set">{{$data->api_set}}</textarea></td>
        <input type="hidden" name="notaId" value="{{$data->nota_id}}">
        <input type="hidden" name="branchId" value="{{$data->branch_id}}">

      </tr>
      @endforeach
      <tr>
        <td colspan="7"> <button type="submit" onclick="return confirm('Are you sure?');" name="button" style="float:right;background:blue;color:white;width:100px;height:30px;border:none">Update</button> </td>
      </tr>
    </form>
    </table>
  </body>
</html>
