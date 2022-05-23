<?php

namespace App\Http\Controllers\Api;

use App\Models\Letter;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Resources\LetterResource;
use App\Http\Resources\LetterCollection;

class ApiLetterController extends Controller
{
    public function index(Request $request)
    {
        return new LetterCollection(
            $this->prepareQuery($request)->paginate($this->limit($request))
        );
    }

    public function show($uuid)
    {
        $letter = Letter::where('uuid', $uuid)
            ->where('status', 'publish')
            ->first();

        if (!$letter) {
            abort(404);
        }

        $letter->load([
            'identities' => function ($query) {
                return $query->select(['name']);
            },
            'places' => function ($query) {
                return $query->select(['name']);
            },
            'keywords' => function ($query) {
                return $query->select(['name']);
            },
        ]);

        return new LetterResource($letter);
    }

    protected function prepareQuery(Request $request)
    {
        $query = Letter::with($this->relationships($request))
            ->where('status', 'publish');

        $query = $this->addScopeByRole($query, $request, 'author', 'identities');
        $query = $this->addScopeByRole($query, $request, 'recipient', 'identities');
        $query = $this->addScopeByRole($query, $request, 'origin', 'places');
        $query = $this->addScopeByRole($query, $request, 'destination', 'places');

        if ($request->input('keyword')) {
            $query->whereHas('keywords', function ($subquery) use ($request) {
                $subquery->whereIn('keywords.id', array_map('intval', explode(',', $request->input('keyword'))));
            });
        }

        if ($request->input('after')) {
            $query->after($request->input('after'));
        }

        if ($request->input('before')) {
            $query->before($request->input('before'));
        }

        return $query->orderBy('date_computed', $this->order($request));
    }

    protected function sanitizedIds($ids)
    {
        $ids = explode(',', $ids);
        $ids = array_map('trim', $ids);
        return array_filter($ids);
    }

    protected function addScopeByRole($query, $request, $role, $type)
    {
        if ($request->input($role)) {
            $query->whereHas($type, function ($subquery) use ($request, $role, $type) {
                $subquery
                    ->where('role', $role)
                    ->whereIn("{$type}.id", array_map('intval', explode(',', $request->input($role))));
            });
        }

        return $query;
    }

    protected function relationships(Request $request)
    {
        $with = [];

        if ($request->input('author') || $request->input('recipient')) {
            $with['identities'] = function ($subquery) {
                $subquery->select('identities.id', 'name', 'role')
                    ->whereIn('role', ['author', 'recipient'])
                    ->orderBy('position');
            };
        }

        if ($request->input('origin') || $request->input('destination')) {
            $with['places'] = function ($subquery) {
                $subquery->select('places.id', 'name', 'role')
                    ->whereIn('role', ['origin', 'destination'])
                    ->orderBy('position');
            };
        }

        if ($request->input('keyword')) {
            $with['keywords'] = function ($subquery) {
                $subquery->select('keywords.id', 'name');
            };
        }

        return $with;
    }

    protected function limit(Request $request)
    {
        $limit = (int) $request->input('limit', 10);

        return $limit > 0 && $limit <= 100
            ? $limit
            : 10;
    }

    protected function order(Request $request)
    {
        $order = $request->input('order', 'asc');

        return in_array($order, ['asc', 'desc'])
            ? $order
            : 'asc';
    }
}
