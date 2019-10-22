<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;
use App\Models\Mdm\TmUnit;
class TsUnit extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TS_UNIT';
    public $timestamps = false;

    public function getTmUnit()
    {
       return $this->hasOne(TmUnit::class, 'unit_id', 'unit_id');
    }
}
