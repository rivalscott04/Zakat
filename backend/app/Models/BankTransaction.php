<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOrganization; use Illuminate\Database\Eloquent\Attributes\Fillable; use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model; use Illuminate\Database\Eloquent\Relations\HasMany;
#[Fillable(['bank_statement_id','bank_account_id','transaction_reference','transaction_date','value_date','description','debit_amount','credit_amount','balance','currency','counterparty_name','counterparty_account','raw_data','match_status','duplicate_status'])]
class BankTransaction extends Model { use BelongsToOrganization,HasUlids; protected function casts():array{return ['transaction_date'=>'date','value_date'=>'date','debit_amount'=>'decimal:2','credit_amount'=>'decimal:2','balance'=>'decimal:2','raw_data'=>'array'];} public function matches():HasMany{return $this->hasMany(ReconciliationMatch::class);} public function statement(){return $this->belongsTo(BankStatement::class);} }
