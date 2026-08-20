<?php
namespace App\Models;
use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
class ZakatRuleParameter extends Model { use HasUlids; protected $guarded = []; protected function casts(): array { return ['default_value'=>'array','validation_rules'=>'array','is_required'=>'boolean']; } public function rule() { return $this->belongsTo(ZakatRule::class,'zakat_rule_id'); } }
