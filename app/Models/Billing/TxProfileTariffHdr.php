<?php

namespace App\Models\Billing;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class TxProfileTariffHdr extends Model
{
    protected $connection = 'eng';
    protected $table = 'TX_PROFILE_TARIFF_HDR';
    protected $primaryKey = 'tariff_id';
    public $timestamps = false;
    protected $fillable = [
    	"tariff_type",
    	"tariff_start",
    	"tariff_end",
    	"branch_id",
    	"tariff_no",
    	"tariff_status",
    	"created_date",
    	"created_by",
    	"service_code",
    	"tariff_name",
    	"tariff_file",
    	"tariff_short_name"
    ];

    protected $appends = ['branch_code','branch_name'];

    public function getBranchCodeAttribute(){
      $get = DB::connection('mdm')->table('TM_BRANCH')->where('BRANCH_ID', $this->attributes['branch_id'])->get();
      if (count($get) > 0) {
        return $get->branch_code;
      }else{
        return '-';
      }
    }

    public function getBranchNameAttribute(){
      $get = DB::connection('mdm')->table('TM_BRANCH')->where('BRANCH_ID', $this->attributes['branch_id'])->get();
      if (count($get) > 0) {
        return $get->branch_name;
      }else{
        return '-';
      }
    }
}
