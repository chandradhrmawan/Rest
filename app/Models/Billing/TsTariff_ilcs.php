<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;

class TsTariff_ilcs extends Model
{
    protected $connection = 'eng_ilcs';
    protected $table = 'TS_TARIFF';
    protected $primaryKey = 'tariff_id';
    public $timestamps = false;
}
