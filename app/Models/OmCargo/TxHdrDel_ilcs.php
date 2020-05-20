<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrDel_ilcs extends Model
{
    protected $connection = 'omcargo_ilcs';
    protected $table = 'TX_HDR_DEL';
    protected $primaryKey = 'del_id';
    public $timestamps = false;
    protected $fillable = [
      "del_date",
      "del_branch_id",
      "del_cust_id",
      "del_cust_name",
      "del_cust_address",
      "del_trade_type",
      "del_trade_name",
      "del_pib_peb_no",
      "del_pib_peb_date",
      "del_npe_sppb_no",
      "del_split",
      "del_vessel_code",
      "del_vessel_name",
      "del_voyin",
      "del_voyout",
      "del_vvd_id",
      "del_eta",
      "del_etb",
      "del_etd",
      "del_ata",
      "del_atd",
      "del_terminal_code",
      "del_terminal_name",
      "del_create_by",
      "del_create_date",
      "del_status",
      "del_cust_npwp",
      "del_kade",
      "del_extend_from",
      "del_extend_loop"
    ];
}
