<?php
namespace App\Models; use App\Models\Concerns\BelongsToOrganization; use Illuminate\Database\Eloquent\Attributes\Fillable; use Illuminate\Database\Eloquent\Concerns\HasUlids; use Illuminate\Database\Eloquent\Model;
#[Fillable(['document_id','user_id','action','ip_address','user_agent','accessed_at','created_at'])] class DocumentAccessLog extends Model { use BelongsToOrganization,HasUlids; public $timestamps=false; protected function casts():array{return ['accessed_at'=>'datetime','created_at'=>'datetime'];} }
