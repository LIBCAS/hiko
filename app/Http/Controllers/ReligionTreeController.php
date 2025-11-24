<?php

namespace App\Http\Controllers;

use App\Models\Religion;
use App\Services\ReligionTreeService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ReligionTreeController extends Controller
{
    public function __construct(private ReligionTreeService $svc) {}

    /** Lazy-load children for jsTree */
    public function tree(Request $request)
    {
        $id = $request->get('id', 'root');
        $locale = app()->getLocale();

        if ($id === 'root' || $id === '#') {
            $rows = DB::select("
                SELECT r.id, r.is_active, r.sort_order,
                    COALESCE(tloc.name, tcs.name, r.name, '#') AS label,
                    EXISTS(SELECT 1 FROM religion_closure c
                        WHERE c.ancestor_id = r.id AND c.depth = 1) AS has_children
                FROM religions r
                LEFT JOIN religion_translations tloc ON tloc.religion_id = r.id AND tloc.locale = ?
                LEFT JOIN religion_translations tcs  ON tcs.religion_id  = r.id AND tcs.locale  = 'cs'
                WHERE NOT EXISTS (SELECT 1 FROM religion_closure c WHERE c.descendant_id = r.id AND c.depth = 1)
                ORDER BY r.sort_order, label
            ", [$locale]);
        } else {
            $rows = DB::select("
                SELECT child.id, child.is_active, child.sort_order,
                    COALESCE(tloc.name, tcs.name, child.name, '#') AS label,
                    EXISTS(SELECT 1 FROM religion_closure c2
                        WHERE c2.ancestor_id = child.id AND c2.depth = 1) AS has_children
                FROM religion_closure c
                JOIN religions child ON child.id = c.descendant_id
                LEFT JOIN religion_translations tloc ON tloc.religion_id = child.id AND tloc.locale = ?
                LEFT JOIN religion_translations tcs  ON tcs.religion_id  = child.id AND tcs.locale  = 'cs'
                WHERE c.ancestor_id = ? AND c.depth = 1
                ORDER BY child.sort_order, label
            ", [$locale, (int) $id]);
        }

        return collect($rows)->map(fn($r) => [
            'id'       => (string) $r->id,
            'text'     => $r->label ?? '#',
            'children' => (bool) $r->has_children,
            'icon'     => $r->is_active ? 'fa fa-circle' : 'fa fa-ban',
            'li_attr'  => ['class' => $r->is_active ? '' : 'is-inactive'],
        ]);
    }

    /** Full tree for jsTree */
    public function treeFull(Request $request)
    {
        $locale = app()->getLocale();

        // one pass: compute label (with locale fallback), parent_id, has_children
        $rows = DB::select("
            SELECT
                r.id,
                r.is_active,
                r.sort_order,
                COALESCE(tloc.name, tcs.name, r.name, '#') AS label,
                -- immediate parent is the ancestor with depth = 1
                (
                SELECT rc.ancestor_id
                FROM religion_closure rc
                WHERE rc.descendant_id = r.id AND rc.depth = 1
                LIMIT 1
                ) AS parent_id,
                EXISTS (
                    SELECT 1
                    FROM religion_closure c
                    WHERE c.ancestor_id = r.id AND c.depth = 1
                ) AS has_children
            FROM religions r
            LEFT JOIN religion_translations tloc
            ON tloc.religion_id = r.id AND tloc.locale = ?
            LEFT JOIN religion_translations tcs
            ON tcs.religion_id  = r.id AND tcs.locale  = 'cs'
            ORDER BY r.sort_order, label
        ", [$locale]);

        // jsTree wants a flat array [{ id, parent, text, ... }]
        $data = array_map(function ($r) {
            return [
                'id'       => (string) $r->id,
                'parent'   => $r->parent_id ? (string) $r->parent_id : '#', // roots have '#'
                'text'     => $r->label ?? '#',
                'icon'     => false,
                'li_attr'  => ['class' => $r->is_active ? '' : 'is-inactive'],
                'data'     => ['has_children' => (bool) $r->has_children, 'is_active' => (bool) $r->is_active],
            ];
        }, $rows);

        return response()->json($data);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'parent_id' => ['nullable', 'integer'],
        ]);

        if (!empty($data['parent_id'])) {
            $exists = DB::table('religions')->where('id', $data['parent_id'])->exists();
            if (!$exists) {
                return response()->json(['message' => 'Parent not found'], 422);
            }
        }

        $tempLabel = '#' . time();
        $node = $this->svc->create($tempLabel, $data['parent_id'] ?? null, 0, null);

        DB::table('religion_translations')->upsert([
            ['religion_id' => $node->id, 'locale' => 'cs', 'created_at' => now(), 'updated_at' => now()],
            ['religion_id' => $node->id, 'locale' => 'en', 'created_at' => now(), 'updated_at' => now()],
        ], ['religion_id', 'locale']);

        return response()->json(['id' => $node->id, 'name' => $tempLabel]);
    }

    public function update($id, Request $request)
    {
        $node = Religion::findOrFail($id);

        // Validate scalars only; do slug uniqueness check manually
        $data = $request->validate([
            'name'          => ['sometimes', 'string', 'max:255'],
            'is_active'     => ['sometimes', 'boolean'],
            'toggle_active' => ['sometimes', 'boolean'],
            'sort_order'    => ['sometimes', 'integer', 'min:0'],
            'slug'          => ['sometimes', 'string', 'max:120'],
        ]);

        if (($data['toggle_active'] ?? false) === true) {
            $data['is_active'] = ! $node->is_active;
            unset($data['toggle_active']);
        }

        if (isset($data['slug']) && $data['slug'] !== $node->slug) {
            $exists = DB::table('religions')
                ->where('slug', $data['slug'])
                ->where('id', '<>', $node->id)
                ->exists();
            if ($exists) {
                return response()->json(['message' => 'Slug already exists'], 422);
            }
        }

        if (array_key_exists('is_active', $data)) {
            $this->svc->setActiveRecursive($node->id, (bool) $data['is_active']);
            $fresh = Religion::findOrFail($node->id);
            return response()->json($fresh);
        }

        $updated = $this->svc->update($node, $data);
        return response()->json($updated);
    }

    public function move($id, Request $request)
    {
        // NOTE: DnD is disabled in UI; endpoint kept hardened
        $data = $request->validate([
            'new_parent_id' => ['nullable', 'integer', 'different:id'],
            'new_position'  => ['nullable', 'integer', 'min:0'],
        ]);

        $newParent = $data['new_parent_id'] ?? null;

        if ($newParent) {
            $exists = DB::table('religions')->where('id', $newParent)->exists();
            if (!$exists) {
                return response()->json(['message' => 'Parent not found'], 422);
            }
        }

        $this->svc->move((int) $id, $newParent);
        return response()->noContent();
    }

    public function destroy($id, Request $request)
    {
        $religionId = (int) $id;

        // 1) Only allow deleting leaves
        $hasChildren = DB::table('religion_closure')
            ->where('ancestor_id', $religionId)
            ->where('depth', 1)
            ->exists();
        if ($hasChildren) {
            return response()->json([
                'message' => __('hiko.cannot_delete_node_with_children'),
                'reason'  => 'has_children'
            ], 422);
        }

        // 2) Check all tenants for identities referencing this religion
        $tenants = DB::table('tenants')
            ->select('tenants.id', 'tenants.name', 'tenants.table_prefix')
            ->get();

        $domainsByTenant = DB::table('domains')
            ->select('tenant_id', 'domain')
            ->orderBy('id')
            ->get()
            ->groupBy('tenant_id')
            ->map(fn($g) => optional($g->first())->domain);

        $blocking = [];

        foreach ($tenants as $tenant) {
            $prefix = $tenant->table_prefix . '__';
            $pivot  = $prefix . 'identity_religion';
            $idents = $prefix . 'identities';

            if (!DB::getSchemaBuilder()->hasTable($pivot) || !DB::getSchemaBuilder()->hasTable($idents)) {
                continue;
            }

            $rows = DB::table($pivot . ' AS ir')
                ->join($idents . ' AS i', 'i.id', '=', 'ir.identity_id')
                ->where('ir.religion_id', $religionId)
                ->where('i.type', 'person')
                ->pluck('i.id');

            if ($rows->isNotEmpty()) {
                $domain = $domainsByTenant[$tenant->id] ?? null;
                $urls = $rows->map(function ($identityId) use ($domain) {
                    return $domain
                        ? 'https://' . $domain . '/identities/' . $identityId . '/edit'
                        : '/identities/' . $identityId . '/edit';
                })->values()->all();

                $blocking[$tenant->id] = [
                    'tenant' => [
                        'id'     => $tenant->id,
                        'name'   => $tenant->name,
                        'prefix' => $tenant->table_prefix,
                        'domain' => $domain,
                    ],
                    'urls' => $urls,
                ];
            }
        }

        if (!empty($blocking)) {
            return response()->json([
                'message'  => __('hiko.cannot_delete_religion_references'),
                'reason'   => 'in_use',
                'blocking' => $blocking,
            ], 422);
        }

        // 4) Clean tenant pivots just in case
        foreach ($tenants as $tenant) {
            $pivot = $tenant->table_prefix . '__identity_religion';
            if (DB::getSchemaBuilder()->hasTable($pivot)) {
                DB::table($pivot)->where('religion_id', $religionId)->delete();
            }
        }

        // 5) Delete (cascades closure + translations)
        $node = Religion::findOrFail($religionId);
        DB::transaction(fn() => $node->delete());

        return response()->noContent();
    }
}
