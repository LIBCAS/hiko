<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class GlobalIdentityStrictMergeController extends Controller
{
    public function index()
    {
        return view('pages.identities.global-strict-merge', [
            'title' => __('hiko.global_identity_strict_merging'),
        ]);
    }

    public function preview(Request $request)
    {
        $ids = collect(explode(',', (string)$request->query('ids', '')))
            ->map(fn($id) => (int)$id)
            ->filter(fn(int $id) => $id > 0)
            ->unique()
            ->values()
            ->all();

        return view('pages.identities.global-strict-merge-preview', [
            'title' => __('hiko.global_identity_strict_merge_preview'),
            'ids' => $ids,
        ]);
    }
}
