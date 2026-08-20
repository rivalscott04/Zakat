<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
class ZakatFitrahConfiguration extends Model { use HasUlids; protected $guarded = []; protected function casts(): array { return ['quantity'=>'decimal:8','cash_equivalent'=>'decimal:8','effective_from'=>'date','effective_until'=>'date']; } }
