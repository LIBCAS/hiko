<?php

namespace App\Http\Controllers;

use App\Services\PageLockService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class PageLockController extends Controller
{
    public function __construct(protected PageLockService $locks)
    {
    }

    public function acquire(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'in:tenant,global'],
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id' => ['nullable'],
            'force' => ['nullable', 'boolean'],
        ]);
        $data = $this->normalizeResourceId($data);

        $result = $this->locks->acquire(
            $data,
            $request->user(),
            (bool) ($data['force'] ?? false)
        );

        if (($result['status'] ?? null) === 'forbidden') {
            return response()->json($result, 403);
        }

        return response()->json($result);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'in:tenant,global'],
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id' => ['nullable'],
        ]);
        $data = $this->normalizeResourceId($data);

        $result = $this->locks->heartbeat($data, $request->user());
        return response()->json($result);
    }

    public function release(Request $request): JsonResponse
    {
        $data = $request->validate([
            'scope' => ['required', 'string', 'in:tenant,global'],
            'resource_type' => ['required', 'string', 'max:100'],
            'resource_id' => ['nullable'],
        ]);
        $data = $this->normalizeResourceId($data);

        $result = $this->locks->release($data, $request->user());
        return response()->json($result);
    }

    protected function normalizeResourceId(array $data): array
    {
        if (!array_key_exists('resource_id', $data) || $data['resource_id'] === null || $data['resource_id'] === '') {
            $data['resource_id'] = null;
            return $data;
        }

        if (!is_scalar($data['resource_id'])) {
            abort(422, 'Invalid resource_id.');
        }

        $resourceId = (string) $data['resource_id'];
        if (mb_strlen($resourceId) > 100) {
            abort(422, 'Invalid resource_id.');
        }

        $data['resource_id'] = $resourceId;
        return $data;
    }
}
