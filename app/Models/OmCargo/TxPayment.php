<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxPayment extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TX_PAYMENT';
    protected $primaryKey = 'pay_id';
    public $timestamps = false;
}
