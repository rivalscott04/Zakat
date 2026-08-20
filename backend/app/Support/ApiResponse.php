<?php

namespace App\Support;

use App\Enums\ErrorCode;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;
use Illuminate\Http\Resources\Json\ResourceCollection;

/** PRD 00 §17 — bentuk response tunggal untuk seluruh API. */
final class ApiResponse
{
    /** @param array<string, mixed> $meta */
    public static function data(mixed $data, array $meta = [], int $status = 200): JsonResponse
    {
        if ($data instanceof ResourceCollection || $data instanceof JsonResource) {
            /** @var JsonResponse $response */
            $response = $data->response();
            $response->setStatusCode($status);

            if ($meta !== []) {
                $payload = $response->getData(true);
                $payload['meta'] = array_merge($payload['meta'] ?? [], $meta);
                $response->setData($payload);
            }

            return static::withMeta($response);
        }

        return static::withMeta(new JsonResponse(['data' => $data, 'meta' => (object) $meta], $status));
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    /** @param array<string, array<int, string>> $errors */
    public static function error(ErrorCode $code, string $message, array $errors = [], ?int $status = null): JsonResponse
    {
        return new JsonResponse([
            'message' => $message,
            'errors' => (object) $errors,
            'code' => $code->value,
            'request_id' => RequestId::current(),
        ], $status ?? $code->httpStatus());
    }

    /** Pastikan envelope sukses selalu punya key `meta` (PRD 00 §17). */
    private static function withMeta(JsonResponse $response): JsonResponse
    {
        $payload = $response->getData(true);

        if (is_array($payload) && array_key_exists('data', $payload) && ! array_key_exists('meta', $payload)) {
            $payload['meta'] = (object) [];
            $response->setData($payload);
        }

        return $response;
    }
}
