<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TmUser extends Model
{
    protected $connection = 'omuster';
    protected $table = 'TM_USER';
    protected $primaryKey = 'user_id';
    public $timestamps = false;
}
