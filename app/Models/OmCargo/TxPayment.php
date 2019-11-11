<?php

namespace App\Models\OmCargo;

use Illuminate\Database\Eloquent\Model;

class TxPayment extends Model
{
    protected $connection = 'omcargo';
    protected $table = 'TX_PAYMENT';
    protected $primaryKey = 'pay_id';
    public $timestamps = false;

    protected $fillable = [
      "pay_no",
      "pay_req_no",
      "pay_method",
      "pay_cust_id",
      "pay_cust_name",
      "pay_bank_code",
      "pay_bank_name",
      "pay_branch_id",
      "pay_account_no",
      "pay_account_name",
      "pay_amount",
      "pay_date",
      "pay_note",
      "pay_status",
      "pay_create_date",
      "pay_type",
      "pay_dest_bank_code",
      "pay_dest_bank_name",
      "pay_dest_account_no",
      "pay_dest_account_name"
    ];
}
