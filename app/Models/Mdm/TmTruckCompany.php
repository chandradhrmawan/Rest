<?php

namespace App\Models\Mdm;

use Illuminate\Database\Eloquent\Model;

class TmTruckCompany extends Model
{
    protected $connection = 'mdm';
    protected $table = 'TM_TRUCK_COMPANY';
    protected $primaryKey = 'comp_id';
    public $timestamps = false;
}
