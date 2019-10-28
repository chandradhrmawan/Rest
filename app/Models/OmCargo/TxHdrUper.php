<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrUper extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TX_HDR_UPER';
    protected $primaryKey = 'uper_id';
    public $timestamps = false;
}
