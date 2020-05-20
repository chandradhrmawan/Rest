<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrUper_ilcs extends Model
{
    protected $connection = 'omcargo_ilcs';
    protected $table = 'TX_HDR_UPER';
    protected $primaryKey = 'uper_id';
    public $timestamps = false;
}
