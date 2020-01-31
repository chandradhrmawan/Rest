<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TxHdrNota extends Model
{
    protected $connection = 'omuster';
    protected $table = 'TX_HDR_NOTA';
    protected $primaryKey = 'nota_id';
    public $timestamps = false;
}
