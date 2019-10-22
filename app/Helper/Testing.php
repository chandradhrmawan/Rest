<?php

namespace App\Helper;

use Illuminate\Http\Request;

class Testing{

	public function testing($input)
	{
		return response()->json($input);
	}
}