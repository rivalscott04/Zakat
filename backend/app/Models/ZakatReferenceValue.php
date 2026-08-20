<?php
namespace App\Models;
use App\Models\Concerns\BelongsToOrganization; use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
class ZakatReferenceValue extends Model { use BelongsToOrganization, HasUlids; protected $guarded = []; protected function casts(): array { return ['value'=>'decimal:8','effective_at'=>'datetime','expires_at'=>'datetime','effective_from'=>'date','effective_until'=>'date']; } }
