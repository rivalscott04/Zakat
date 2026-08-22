<?php

namespace App\Services;

use App\Exceptions\ZakatException;
use App\Models\Document;
use App\Models\DocumentAccessLog;
use App\Models\DocumentRelation;
use App\Models\DocumentVerification;
use App\Models\DocumentVersion;
use App\Support\OrganizationContext;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

/** PRD 15 — Document Management. */
class DocumentService
{
    /** PRD 15J §20 — ekstensi yang diterima beserta MIME yang sah untuk masing-masing. */
    public const ALLOWED = [
        'pdf' => ['application/pdf'],
        'doc' => ['application/msword'],
        'docx' => ['application/vnd.openxmlformats-officedocument.wordprocessingml.document'],
        'xls' => ['application/vnd.ms-excel'],
        'xlsx' => ['application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'],
        'csv' => ['text/csv', 'text/plain', 'application/csv', 'application/vnd.ms-excel'],
        'jpg' => ['image/jpeg'],
        'jpeg' => ['image/jpeg'],
        'png' => ['image/png'],
        'webp' => ['image/webp'],
    ];

    /**
     * PRD 15S §36 — hanya tipe ini yang boleh ditampilkan inline.
     *
     * Content type diambil dari daftar ini, bukan dari berkas unggahan. Memakai
     * MIME kiriman pengguna membuat berkas HTML yang disamarkan bisa dieksekusi
     * browser sebagai halaman, dan itu jalur stored XSS.
     */
    public const PREVIEWABLE = [
        'pdf' => 'application/pdf',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'webp' => 'image/webp',
    ];

    public const MAX_BYTES = 10 * 1024 * 1024;

    public function __construct(private readonly AuditService $audit) {}

    public function list(array $filters): LengthAwarePaginator
    {
        return Document::query()
            ->when($filters['search'] ?? null, fn ($q, $v) => $q->where(
                fn ($x) => $x->where('document_name', 'ilike', "%{$v}%")->orWhere('document_number', 'ilike', "%{$v}%")
            ))
            ->when($filters['status'] ?? null, fn ($q, $v) => $q->where('status', $v))
            ->when($filters['document_type'] ?? null, fn ($q, $v) => $q->where('document_type', $v))
            ->latest()
            ->paginate(min((int) ($filters['per_page'] ?? 25), (int) config('zakat.pagination.max_per_page')));
    }

    public function find(string $id): Document
    {
        return Document::with(['relations', 'versions', 'verifications'])->find($id)
            ?? throw ZakatException::notFound('Dokumen tidak ditemukan.');
    }

    // ---------------------------------------------------------------- upload

    /** PRD 15I dan §19. */
    public function upload(UploadedFile $file, array $data): Document
    {
        $extension = $this->assertFile($file);
        $checksum = hash_file('sha256', $file->getRealPath());

        // PRD 15O — berkas identik ditandai, tidak ditolak, agar petugas memutuskan.
        $duplicate = Document::query()->where('checksum', $checksum)->first();

        $document = DB::transaction(function () use ($file, $data, $extension, $checksum) {
            $document = new Document;
            $document->fill([
                'document_name' => $data['document_name'],
                // PRD 15AC §59 — nama kiriman pengguna tidak pernah dipakai apa adanya.
                'original_filename' => $this->safeFilename($file->getClientOriginalName()),
                'document_type' => $data['document_type'],
                'category' => $data['category'] ?? null,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'extension' => $extension,
                'file_size' => $file->getSize(),
                'storage_disk' => 'private',
                'checksum' => $checksum,
                'visibility' => $data['visibility'] ?? 'PRIVATE',
                'status' => 'ACTIVE',
                'expires_at' => $data['expires_at'] ?? null,
                'uploaded_by' => auth()->id(),
            ]);

            // PRD 15N — nama simpan dibuat sistem, tidak mengandung input pengguna.
            $storedName = (string) Str::ulid().'.'.$extension;
            $document->stored_filename = $storedName;
            $document->storage_path = Storage::disk('private')->putFileAs('documents/'.now()->format('Y/m'), $file, $storedName);
            $document->save();

            $this->audit->record('document_uploaded', $document);

            return $document;
        });

        if ($duplicate !== null) {
            $this->audit->record('document_duplicate_detected', $document, context: ['existing_document_id' => $duplicate->id]);
        }

        return $document;
    }

    /** PRD 15P — berkas lama disimpan sebagai versi, tidak dibuang. */
    public function replace(Document $document, UploadedFile $file, array $data): Document
    {
        $this->assertMutable($document);
        $extension = $this->assertFile($file);

        return DB::transaction(function () use ($document, $file, $data, $extension) {
            // Versi menyimpan berkas lama apa adanya supaya riwayat tetap dapat
            // ditelusuri; menghapusnya membuat versioning tidak ada gunanya.
            $version = new DocumentVersion;
            $version->fill([
                'document_id' => $document->id,
                'version_number' => (int) $document->version,
                'storage_path' => $document->storage_path,
                'file_size' => $document->file_size,
                'checksum' => $document->checksum,
                'change_note' => $data['change_note'] ?? null,
                'created_by' => auth()->id(),
            ]);
            // Model ini mematikan timestamps otomatis sedangkan kolomnya NOT NULL,
            // jadi harus diisi eksplisit dan di luar mass assignment.
            $version->created_at = now();
            $version->save();

            $storedName = (string) Str::ulid().'.'.$extension;

            $document->forceFill([
                'storage_path' => Storage::disk('private')->putFileAs('documents/'.now()->format('Y/m'), $file, $storedName),
                'stored_filename' => $storedName,
                'original_filename' => $this->safeFilename($file->getClientOriginalName()),
                'file_size' => $file->getSize(),
                'checksum' => hash_file('sha256', $file->getRealPath()),
                'extension' => $extension,
                'mime_type' => $file->getMimeType() ?: 'application/octet-stream',
                'version' => (int) $document->version + 1,
            ])->saveQuietly();

            $this->audit->record('document_replaced', $document);

            return $document;
        });
    }

    // ------------------------------------------------------------ perubahan

    public function update(Document $document, array $data): Document
    {
        $this->assertMutable($document);

        $document->fill($data);
        $document->save();
        $this->audit->record('document_updated', $document);

        return $document;
    }

    public function delete(Document $document): Document
    {
        $document->forceFill(['status' => 'DELETED'])->saveQuietly();
        $document->delete();
        $this->audit->record('document_deleted', $document);

        return $document;
    }

    public function restore(Document $document): Document
    {
        $document->restore();
        $document->forceFill(['status' => 'ACTIVE'])->saveQuietly();
        $this->audit->record('document_restored', $document);

        return $document;
    }

    public function archive(Document $document): Document
    {
        if ($document->status === 'DELETED') {
            throw ZakatException::invalidTransition('Dokumen yang dihapus tidak dapat diarsipkan.');
        }

        $document->forceFill(['status' => 'ARCHIVED'])->saveQuietly();
        $this->audit->record('document_archived', $document);

        return $document;
    }

    /** PRD 15Q. */
    public function verify(Document $document, bool $approved, ?string $note): Document
    {
        $document->forceFill(['status' => $approved ? 'VERIFIED' : 'REJECTED'])->saveQuietly();

        DocumentVerification::create([
            'document_id' => $document->id,
            'status' => $approved ? 'VERIFIED' : 'REJECTED',
            'verification_note' => $note,
            'verified_by' => auth()->id(),
            'verified_at' => now(),
        ]);

        $this->audit->record($approved ? 'document_verified' : 'document_rejected', $document);

        return $document;
    }

    public function relate(Document $document, array $data): DocumentRelation
    {
        $relation = DocumentRelation::create($data + ['document_id' => $document->id, 'created_by' => auth()->id()]);
        $this->audit->record('document_relation_created', $document, context: ['relation_id' => $relation->id]);

        return $relation;
    }

    public function removeRelation(Document $document, string $relationId): void
    {
        $relation = $document->relations()->find($relationId) ?? throw ZakatException::notFound('Relasi dokumen tidak ditemukan.');
        $relation->delete();
        $this->audit->record('document_relation_deleted', $document, context: ['relation_id' => $relationId]);
    }

    // ------------------------------------------------------------- pengaksesan

    /**
     * PRD 15S §36 — content type ditentukan sistem dari ekstensi tersimpan.
     *
     * @return array{path: string, mime: string, filename: string}
     */
    public function previewPayload(Document $document): array
    {
        $mime = self::PREVIEWABLE[$document->extension] ?? null;

        if ($mime === null) {
            throw ZakatException::conflict('Tipe dokumen ini hanya dapat diunduh, tidak dapat ditampilkan langsung.');
        }

        $this->assertReadable($document);

        return [
            'path' => Storage::disk($document->storage_disk)->path($document->storage_path),
            'mime' => $mime,
            'filename' => $document->original_filename,
        ];
    }

    public function assertDownloadable(Document $document): void
    {
        $this->assertReadable($document);
    }

    public function log(Document $document, string $action): void
    {
        DocumentAccessLog::create([
            'document_id' => $document->id,
            'user_id' => auth()->id(),
            'action' => $action,
            'ip_address' => request()->ip(),
            'user_agent' => substr((string) request()->userAgent(), 0, 500),
            'accessed_at' => now(),
            'created_at' => now(),
        ]);

        $this->audit->record('document_'.$action, $document);
    }

    // --------------------------------------------------------------- helpers

    /**
     * PRD 15 §19 — ekstensi, MIME, dan ukuran diperiksa bersamaan.
     *
     * Ekstensi saja tidak cukup karena mudah dipalsukan, dan MIME kiriman client
     * juga tidak dipercaya: yang dipakai adalah hasil deteksi isi berkas.
     */
    public function assertFile(UploadedFile $file): string
    {
        if (! $file->isValid()) {
            throw ZakatException::conflict('Berkas tidak valid.');
        }

        if ($file->getSize() > self::MAX_BYTES) {
            throw ZakatException::conflict('Ukuran berkas maksimal 10 MB.');
        }

        $extension = strtolower($file->getClientOriginalExtension());

        if (! array_key_exists($extension, self::ALLOWED)) {
            throw ZakatException::conflict('Jenis berkas belum didukung.');
        }

        $detected = $file->getMimeType() ?: '';

        if (! in_array($detected, self::ALLOWED[$extension], true)) {
            throw ZakatException::conflict('Isi berkas tidak sesuai dengan ekstensinya.');
        }

        return $extension;
    }

    /** PRD 15F §10 — dokumen privat hanya untuk pengunggah atau pemegang izin khusus. */
    private function assertReadable(Document $document): void
    {
        if ($document->status === 'DELETED') {
            throw ZakatException::notFound('Dokumen tidak ditemukan.');
        }

        if ($document->visibility !== 'PRIVATE') {
            return;
        }

        $user = auth()->user();

        if ($user === null) {
            throw ZakatException::forbidden('Dokumen ini bersifat privat.');
        }

        if ($document->uploaded_by === $user->getKey()) {
            return;
        }

        if (! $user->hasPermissionTo('document.manage', OrganizationContext::id())) {
            throw ZakatException::forbidden('Dokumen privat hanya dapat diakses pengunggah atau pemegang izin document.manage.');
        }
    }

    private function assertMutable(Document $document): void
    {
        if (in_array($document->status, ['ARCHIVED', 'DELETED'], true)) {
            throw ZakatException::invalidTransition('Dokumen arsip atau terhapus bersifat hanya-baca.');
        }
    }

    /** Buang path dan karakter kendali agar nama berkas aman dipakai pada header. */
    private function safeFilename(string $name): string
    {
        $base = basename(str_replace('\\', '/', $name));
        $clean = preg_replace('/[^\pL\pN._-]+/u', '_', $base) ?? 'dokumen';

        return Str::limit(trim($clean, '._-') ?: 'dokumen', 150, '');
    }
}
