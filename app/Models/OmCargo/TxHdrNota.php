<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrNota extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TX_HDR_NOTA';
    protected $primaryKey = 'nota_id';
    public $timestamps = false;
}
