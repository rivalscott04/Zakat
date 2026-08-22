<?php

namespace App\Models;

use App\Models\Concerns\BelongsToOrganization;
use App\Models\Concerns\HasBusinessNumber;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['bank_name', 'account_name', 'account_number_encrypted', 'account_number_masked', 'currency', 'opening_balance', 'current_balance', 'status'])]
#[Hidden(['account_number_encrypted'])]
class BankAccount extends Model
{
    use BelongsToOrganization, HasBusinessNumber, HasUlids;

    public static function businessCode(): string
    {
        return 'BNK';
    }

    public function businessNumberColumn(): string
    {
        return 'account_code';
    }

    protected function casts(): array
    {
        return ['account_number_encrypted' => 'encrypted', 'opening_balance' => 'decimal:2', 'current_balance' => 'decimal:2'];
    }

    public function statements(): HasMany
    {
        return $this->hasMany(BankStatement::class);
    }

    public static function mask(string $value): string
    {
        $digits = preg_replace('/\D/', '', $value) ?? '';

        return strlen($digits) <= 4 ? str_repeat('*', strlen($digits)) : str_repeat('*', strlen($digits) - 4).substr($digits, -4);
    }
}
