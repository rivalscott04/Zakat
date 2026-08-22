<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\ListRequest;
use App\Http\Requests\StoreNotificationTemplateRequest;
use App\Http\Resources\NotificationTemplateResource;
use App\Models\NotificationTemplate;
use App\Services\NotificationTemplateService;
use App\Support\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

/** PRD 16U §44. */
class NotificationTemplateController extends Controller
{
    public function __construct(private readonly NotificationTemplateService $templates) {}

    public function index(ListRequest $request): JsonResponse
    {
        $filters = $request->validated() + ['channel' => $request->query('channel')];

        return ApiResponse::data(NotificationTemplateResource::collection($this->templates->list($filters)));
    }

    public function store(StoreNotificationTemplateRequest $request): JsonResponse
    {
        return ApiResponse::data(new NotificationTemplateResource($this->templates->create($request->validated())), status: 201);
    }

    public function show(string $id): JsonResponse
    {
        return ApiResponse::data(new NotificationTemplateResource(NotificationTemplate::query()->findOrFail($id)));
    }

    public function update(StoreNotificationTemplateRequest $request, string $id): JsonResponse
    {
        $template = NotificationTemplate::query()->findOrFail($id);

        return ApiResponse::data(new NotificationTemplateResource($this->templates->update($template, $request->validated())));
    }

    public function activate(string $id): JsonResponse
    {
        $template = NotificationTemplate::query()->findOrFail($id);

        return ApiResponse::data(new NotificationTemplateResource($this->templates->activate($template)));
    }

    public function deactivate(string $id): JsonResponse
    {
        $template = NotificationTemplate::query()->findOrFail($id);

        return ApiResponse::data(new NotificationTemplateResource($this->templates->deactivate($template)));
    }

    /** PRD 16K §26 — pratinjau sekaligus pengecekan variabel sebelum disimpan. */
    public function preview(Request $request, string $id): JsonResponse
    {
        $template = NotificationTemplate::query()->findOrFail($id);
        $variables = (array) $request->input('variables', []);

        return ApiResponse::data([
            'placeholders' => $this->templates->placeholders($template->content),
            'subject' => $this->templates->render($template->subject ?? '', $variables),
            'content' => $this->templates->render($template->content, $variables),
        ]);
    }
}
