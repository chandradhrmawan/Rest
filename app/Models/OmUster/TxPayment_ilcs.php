<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TxPayment_ilcs extends Model
{
    protected $connection = 'omuster_ilcs';
    protected $table = 'TX_PAYMENT';
    protected $primaryKey = 'pay_id';
    public $timestamps = false;
}
