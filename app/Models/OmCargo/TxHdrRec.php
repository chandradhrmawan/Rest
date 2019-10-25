<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxHdrrec extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TX_HDR_REC';
    protected $primaryKey = 'rec_id';
    public $timestamps = false;
    protected $fillable = [
    "rec_date",
    "rec_branch_id",
    "rec_cust_id",
    "rec_cust_name",
    "rec_cust_address",
    "rec_trade_type",
    "rec_trade_name",
    "rec_pib_peb_no",
    "rec_pib_peb_date",
    "rec_npe_sppb_no",
    "rec_vbooking",
    "rec_split",
    "rec_vessel_code",
    "rec_vessel_name",
    "rec_voyin",
    "rec_voyout",
    "rec_vvd_id",
    "rec_eta",
    "rec_etb",
    "rec_etd",
    "rec_ata",
    "rec_atd",
    "rec_terminal_code",
    "rec_terminal_name",
    "rec_create_by",
    "rec_create_date",
    "rec_status",
    "rec_cust_npwp",
    "rec_kade",
    "rec_extend_from",
    "rec_extend_loop",
    "rec_extend_status"
    ];
}
