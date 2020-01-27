<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class TsTariff extends Model
{
    protected $connection = 'eng';
    protected $table = 'TS_TARIFF';
    protected $primaryKey = 'tariff_id';
    public $timestamps = false;
}
