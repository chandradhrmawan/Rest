<?php

namespace App\Models\OmUster;

use Illuminate\Database\Eloquent\Model;

class TrRole extends Model
{
    protected $connection = 'omuster';
    protected $primaryKey = 'role_id';
    protected $table = 'TR_ROLE';
    public $timestamps = false;
    protected $fillable = [
    	"role_name",
    	"role_status",
    	"role_service",
    	"role_desc"
    ];
}
