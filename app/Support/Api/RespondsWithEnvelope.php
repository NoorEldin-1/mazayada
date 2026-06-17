<?php

namespace App\Support\Api;

use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Unified JSON envelope for the mobile API.
 *
 * Success: { "data": ..., "message": string|null, "meta": { ... } }
 * Failure: { "message": string, "errors": { field: [..] } }   (see bootstrap/app.php
 *           withExceptions + the ValidationException renderer — kept identical to
 *           Laravel's native validation shape so the Flutter client parses one format).
 *
 * Lists put their pagination block in meta.pagination.
 */
trait RespondsWithEnvelope
{
    protected function ok(mixed $data = null, ?string $message = null, array $meta = [], int $status = 200): JsonResponse
    {
        return $this->envelope($data, $message, $meta, $status);
    }

    protected function created(mixed $data = null, ?string $message = null, array $meta = []): JsonResponse
    {
        return $this->envelope($data, $message, $meta, 201);
    }

    protected function noContent(): JsonResponse
    {
        return response()->json(null, 204);
    }

    protected function fail(?string $message = null, array $errors = [], int $status = 400): JsonResponse
    {
        $payload = ['message' => $message ?? __('common.api.error')];

        if ($errors !== []) {
            $payload['errors'] = $errors;
        }

        return response()->json($payload, $status);
    }

    /**
     * Wrap a length-aware paginator in the envelope, mapping each item through the
     * given API Resource class and exposing the page info under meta.pagination.
     */
    protected function paginated(LengthAwarePaginator $paginator, string $resourceClass, ?string $message = null, array $meta = []): JsonResponse
    {
        $data = $resourceClass::collection($paginator->getCollection())->resolve(request());

        $meta['pagination'] = [
            'current_page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
            'per_page' => $paginator->perPage(),
            'total' => $paginator->total(),
            'count' => $paginator->count(),
        ];

        return $this->envelope($data, $message, $meta, 200);
    }

    protected function envelope(mixed $data, ?string $message, array $meta, int $status): JsonResponse
    {
        if ($data instanceof JsonResource) {
            $data = $data->resolve(request());
        }

        return response()->json([
            'data' => $data,
            'message' => $message,
            // Cast to object so an empty meta serialises as {} rather than [].
            'meta' => (object) $meta,
        ], $status);
    }
}
