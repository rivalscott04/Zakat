<?php

namespace App\Models;

use App\Enums\BankTransferStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

/** PRD 12M §31. Nomor rekening penuh tidak pernah ikut serialisasi default. */
#[Fillable(['distribution_id', 'bank_name', 'account_holder_name', 'transfer_reference', 'transfer_amount', 'transfer_date', 'status', 'failure_reason'])]
#[Hidden(['account_number_encrypted'])]
class DistributionBankTransfer extends Model
{
    use HasUlids;

    protected function casts(): array
    {
        return ['account_number_encrypted' => 'encrypted', 'transfer_amount' => 'decimal:2', 'transfer_date' => 'date', 'status' => BankTransferStatus::class];
    }

    public function distribution()
    {
        return $this->belongsTo(Distribution::class);
    }

    /** PRD 12D §9 — hanya empat digit terakhir yang boleh tampil tanpa permission khusus. */
    public static function mask(string $accountNumber): string
    {
        $digits = preg_replace('/\D/', '', $accountNumber) ?? '';

        return strlen($digits) <= 4 ? str_repeat('*', strlen($digits)) : str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
