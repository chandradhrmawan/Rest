<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TxPayment extends Model
{
    protected $connection = 'omuster';
    protected $table = 'TX_PAYMENT';
    protected $primaryKey = 'pay_id';
    public $timestamps = false;
}
