<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrNota_ilcs extends Model
{
    protected $connection = 'omcargo_ilcs';
    protected $table = 'TX_HDR_NOTA';
    protected $primaryKey = 'nota_id';
    public $timestamps = false;
}
