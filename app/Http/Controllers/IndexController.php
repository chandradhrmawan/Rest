<?php

namespace App\Http\Controllers;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class IndexController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function api(Request $request) {
      $input  = $this->request->all();
      $action = $input["action"];
      return $this->$action($input);
    }

      public function view($input) {
      $table  = $input["table"];
      $db     = $input["db"];

      if (isset($input['pagination'])) {
        $page   = $input["pagination"];
        $data   = \DB::connection($db)->table($table)->skip($page[0])->take($page[1])->get();
      } else {
        $data   = \DB::connection($db)->table($table)->get();
      }
      return response()->json($data);
    }
    //
}
