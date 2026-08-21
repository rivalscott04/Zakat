<?php
namespace App\Models; use App\Models\Concerns\BelongsToOrganization; use Illuminate\Database\Eloquent\Attributes\Fillable; use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
#[Fillable(['document_id','version_number','storage_path','file_size','checksum','change_note','created_by'])] class DocumentVersion extends Model { use BelongsToOrganization,HasUlids; public $timestamps=false; protected function casts():array{return ['file_size'=>'integer','created_at'=>'datetime'];} }
