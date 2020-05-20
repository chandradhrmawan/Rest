<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TxHdrNota_ilcs extends Model
{
    protected $connection = 'omuster_ilcs';
    protected $table = 'TX_HDR_NOTA';
    protected $primaryKey = 'nota_id';
    public $timestamps = false;
}
