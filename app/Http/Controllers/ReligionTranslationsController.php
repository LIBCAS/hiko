<?php

namespace App\Http\Controllers;

use App\Services\ReligionTreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

class ReligionTranslationsController extends Controller
{
    public function __construct(private ReligionTreeService $svc) {}

    public function show($id)
    {
        $rows = DB::table('religion_translations')
            ->where('religion_id', $id)
            ->get()
            ->keyBy('locale');

        return response()->json([
            'cs' => [
                'name' => optional($rows->get('cs'))->name,
                'slug' => optional($rows->get('cs'))->slug,
                'path' => optional($rows->get('cs'))->path_text,
            ],
            'en' => [
                'name' => optional($rows->get('en'))->name,
                'slug' => optional($rows->get('en'))->slug,
                'path' => optional($rows->get('en'))->path_text,
            ],
        ]);
    }

    public function update($id, Request $request)
    {
        $data = $request->validate([
            'cs' => ['required', 'array'],
            'cs.name' => ['nullable', 'string', 'max:255'],
            'cs.slug' => ['nullable', 'string', 'max:160'],
            'en' => ['required', 'array'],
            'en.name' => ['nullable', 'string', 'max:255'],
            'en.slug' => ['nullable', 'string', 'max:160'],
        ]);

        // normalize empty strings to null
        foreach (['cs', 'en'] as $loc) {
            foreach (['name', 'slug'] as $k) {
                if (array_key_exists($k, $data[$loc])) {
                    $v = trim((string)($data[$loc][$k] ?? ''));
                    $data[$loc][$k] = ($v === '') ? null : $v;
                }
            }
        }

        DB::transaction(function () use ($id, $data) {
            foreach (['cs', 'en'] as $loc) {
                DB::table('religion_translations')->upsert([
                    'religion_id' => (int) $id,
                    'locale'      => $loc,
                    'name'        => $data[$loc]['name'] ?? null,
                    'slug'        => $data[$loc]['slug'] ?? null,
                    'updated_at'  => now(),
                    'created_at'  => now(),
                ], ['religion_id', 'locale'], ['name', 'slug', 'updated_at']);

                // Recompute path for this locale for the whole subtree
                $descIds = $this->svc->descendantIds((int) $id);
                $this->svc->recomputeLocalePaths($descIds, $loc, 'cs');
            }
        });

        return response()->noContent();
    }
}
