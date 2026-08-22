<?php

namespace App\Http\Controllers\Api\V1;

use App\Exceptions\ZakatException;
use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\ReplaceDocumentRequest;
use App\Http\Requests\StoreDocumentRelationRequest;
use App\Http\Requests\StoreDocumentRequest;
use App\Http\Requests\UpdateDocumentRequest;
use App\Http\Requests\VerifyDocumentRequest;
use App\Http\Resources\DocumentResource;
use App\Models\Document;
use App\Services\DocumentService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Symfony\Component\HttpFoundation\StreamedResponse;

/** PRD 15X — API document management. */
class DocumentController extends Controller
{
    public function __construct(private readonly DocumentService $service) {}

    public function index(ListRequest $request): JsonResponse
    {
        return ApiResponse::data(DocumentResource::collection($this->service->list($request->filters())));
    }

    public function store(StoreDocumentRequest $request): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->upload($request->file('file'), $request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->find($id)));
    }

    public function update(UpdateDocumentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->update($this->service->find($id), $request->validated())));
    }

    public function replace(ReplaceDocumentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->replace($this->service->find($id), $request->file('file'), $request->validated())));
    }

    public function delete(string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->delete($this->service->find($id))));
    }

    public function restore(string $id): JsonResponse
    {
        $document = Document::withTrashed()->find($id) ?? throw ZakatException::notFound('Dokumen tidak ditemukan.');

        return ApiResponse::data(new DocumentResource($this->service->restore($document)));
    }

    public function archive(string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource($this->service->archive($this->service->find($id))));
    }

    // ------------------------------------------------------------ berkas

    /**
     * PRD 15T §37 — unduhan selalu sebagai lampiran.
     *
     * Content type dipaksa octet-stream dan nosniff dipasang supaya berkas tidak
     * pernah dieksekusi browser sebagai halaman.
     */
    public function download(string $id): StreamedResponse|BinaryFileResponse
    {
        $document = $this->service->find($id);
        $this->service->assertDownloadable($document);
        $this->service->log($document, 'downloaded');

        return Storage::disk($document->storage_disk)->download(
            $document->storage_path,
            $document->original_filename,
            ['Content-Type' => 'application/octet-stream', 'X-Content-Type-Options' => 'nosniff'],
        );
    }

    /** PRD 15S §36 — hanya PDF dan gambar, dengan content type dari daftar sistem. */
    public function preview(string $id): BinaryFileResponse
    {
        $document = $this->service->find($id);
        $payload = $this->service->previewPayload($document);
        $this->service->log($document, 'previewed');

        return response()->file($payload['path'], [
            'Content-Type' => $payload['mime'],
            'Content-Disposition' => 'inline; filename="'.$payload['filename'].'"',
            'X-Content-Type-Options' => 'nosniff',
            'Content-Security-Policy' => "default-src 'none'; img-src 'self'; object-src 'none'; sandbox",
        ]);
    }

    // ----------------------------------------------------------- verifikasi

    public function verify(VerifyDocumentRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data(new DocumentResource(
            $this->service->verify($this->service->find($id), true, $request->validated('note'))
        ));
    }

    public function reject(VerifyDocumentRequest $request, string $id): JsonResponse
    {
        $note = $request->validated('note') ?? throw ZakatException::conflict('Penolakan wajib disertai alasan.');

        return ApiResponse::data(new DocumentResource($this->service->verify($this->service->find($id), false, $note)));
    }

    // --------------------------------------------------------------- relasi

    public function relations(string $id): JsonResponse
    {
        return ApiResponse::data($this->service->find($id)->relations);
    }

    public function relation(StoreDocumentRelationRequest $request, string $id): JsonResponse
    {
        return ApiResponse::data($this->service->relate($this->service->find($id), $request->validated()), status: 201);
    }

    public function deleteRelation(string $id, string $relationId): Response
    {
        $this->service->removeRelation($this->service->find($id), $relationId);

        return ApiResponse::noContent();
    }
}
