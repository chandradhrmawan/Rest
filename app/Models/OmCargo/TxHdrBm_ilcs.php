<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrBm_ilcs extends Model
{
    protected $connection = 'omcargo_ilcs';
    protected $table = 'TX_HDR_BM';
    protected $primaryKey = 'bm_id';
    public $timestamps = false;
    protected $fillable = [
      "bm_date",
      "bm_branch_id",
      "bm_cust_id",
      "bm_cust_name",
      "bm_cust_address",
      "bm_pbm_id",
      "bm_pbm_name",
      "bm_shipping_agent_id",
      "bm_shipping_agent_name",
      "bm_vessel_code",
      "bm_vessel_name",
      "bm_voyin",
      "bm_voyout",
      "bm_vvd_id",
      "bm_eta",
      "bm_etb",
      "bm_etd",
      "bm_ata",
      "bm_atd",
      "bm_split",
      "bm_trade_type",
      "bm_trade_name",
      "bm_pib_peb_no",
      "bm_pib_peb_date",
      "bm_create_by",
      "bm_create_date",
      "bm_status",
      "bm_terminal_code",
      "bm_terminal_name",
      "bm_npe_sppb_no",
      "bm_booking",
      "bm_cust_npwp",
      "bm_kade"
    ];
}
