<?php

namespace App\Models\Mdm;

use Illuminate\Database\Eloquent\Model;

class TmUnit extends Model
{
    protected $connection = 'mdm';
    protected $table = 'TM_UNIT';
    public $timestamps = false;
}
