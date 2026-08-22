<?php

namespace App\Http\Resources;

use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * PRD 15C.
 *
 * storage_path dan storage_disk sengaja tidak ikut: keduanya lokasi internal
 * dan tidak boleh menjadi petunjuk untuk mengakses berkas langsung
 * (PRD 15AC §59 soal direct storage path access).
 */
class DocumentResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'document_number' => $this->document_number,
            'document_name' => $this->document_name,
            'original_filename' => $this->original_filename,
            'document_type' => $this->document_type,
            'category' => $this->category,
            'mime_type' => $this->mime_type,
            'extension' => $this->extension,
            'file_size' => $this->file_size,
            'checksum' => $this->checksum,
            'version' => $this->version,
            'visibility' => $this->visibility,
            'status' => $this->status,
            'previewable' => array_key_exists((string) $this->extension, DocumentService::PREVIEWABLE),
            'expires_at' => $this->expires_at?->toIso8601String(),
            'uploaded_by' => $this->uploaded_by,
            'created_at' => $this->created_at?->toIso8601String(),
            'relations' => $this->whenLoaded('relations'),
            'versions' => $this->whenLoaded('versions'),
            'verifications' => $this->whenLoaded('verifications'),
        ];
    }
}
