<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
class ZakatAgricultureConfiguration extends Model { use HasUlids; protected $guarded = []; protected function casts(): array { return ['minimum_quantity'=>'decimal:8','rate'=>'decimal:8']; } }
