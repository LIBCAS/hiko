<?php

namespace App\Services;

use App\Models\Religion;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReligionTreeService
{
    /** Create a node under $parentId (or as root if null) */
    public function create(string $name, ?int $parentId, int $sortOrder = 0, ?string $slug = null): Religion
    {
        return DB::transaction(function () use ($name, $parentId, $sortOrder, $slug) {
            $slug = $slug ?: 'r-' . (string) Str::ulid();
            $tempLabel = '#' . substr($slug, -6);

            $religion = Religion::create([
                'name'       => $name ?: $tempLabel,
                'slug'       => $slug,
                'sort_order' => $sortOrder,
                'is_active'  => true,
            ]);

            DB::table('religion_closure')->insert([
                'ancestor_id'   => $religion->id,
                'descendant_id' => $religion->id,
                'depth'         => 0,
            ]);

            if ($parentId) {
                DB::insert("
                    INSERT INTO religion_closure (ancestor_id, descendant_id, depth)
                    SELECT ancestor_id, ?, depth + 1
                    FROM religion_closure
                    WHERE descendant_id = ?
                ", [$religion->id, $parentId]);
            }

            $this->recomputePathText([$religion->id]);

            return $religion;
        });
    }

    /** Rename, toggle, reorder (no move) */
    public function update(Religion $node, array $attrs): Religion
    {
        return DB::transaction(function () use ($node, $attrs) {
            $node->fill($attrs);
            $node->save();

            if (array_key_exists('name', $attrs)) {
                $ids = array_merge([$node->id], $this->descendantIds($node->id)); // include self + descendants
                $this->recomputePathText($ids);
            }

            return $node;
        });
    }

    /** Move subtree rooted at $nodeId under $newParentId and recompute paths */
    public function move(int $nodeId, ?int $newParentId): void
    {
        DB::transaction(function () use ($nodeId, $newParentId) {
            // delete old ancestor links (except self)
            DB::delete("
                DELETE rc FROM religion_closure rc
                JOIN religion_closure sub ON sub.descendant_id = rc.descendant_id
                WHERE sub.ancestor_id = ? AND rc.depth > 0
            ", [$nodeId]);

            if ($newParentId) {
                // add new ancestor links: ancestors(P) × subtree(N)
                DB::insert("
                    INSERT INTO religion_closure (ancestor_id, descendant_id, depth)
                    SELECT pa.ancestor_id, sub.descendant_id, pa.depth + sub.depth + 1
                    FROM religion_closure pa
                    JOIN religion_closure sub ON sub.ancestor_id = ?
                    WHERE pa.descendant_id = ?
                ", [$nodeId, $newParentId]);
            }

            $this->recomputePathText($this->descendantIds($nodeId));
        });
    }

    /** Hard delete (FKs cascade closure + translations). Prefer soft delete in UI policy. */
    public function delete(Religion $node): void
    {
        DB::transaction(function () use ($node) {
            $node->delete();
        });
    }

    /** Recompute path_text + lower_path_text for given descendants (root→leaf order) */
    public function recomputePathText(array $descendantIds): void
    {
        if (empty($descendantIds)) return;

        $ids = array_values(array_unique(array_map('intval', $descendantIds)));
        $idsCsv = implode(',', $ids);

        // ORDER BY rc.depth DESC builds root→leaf (0 is root, higher is deeper)
        $rows = DB::select("
            SELECT d.id AS id,
                GROUP_CONCAT(a.name ORDER BY rc.depth DESC SEPARATOR ' > ') AS path
            FROM religion_closure rc
            JOIN religions a ON a.id = rc.ancestor_id
            JOIN religions d ON d.id = rc.descendant_id
            WHERE d.id IN ($idsCsv)
            GROUP BY d.id
        ");

        foreach ($rows as $row) {
            DB::table('religions')->where('id', $row->id)->update([
                'path_text'       => $row->path,
                'lower_path_text' => mb_strtolower($row->path ?? '', 'UTF-8'),
                'updated_at'      => now(),
            ]);
        }
    }

    /** Recompute localized path_text + lower_path_text using locale with fallback */
    public function recomputeLocalePaths(array $descendantIds, string $locale, string $fallback = 'cs'): void
    {
        if (empty($descendantIds)) return;

        $ids = array_values(array_unique(array_map('intval', $descendantIds)));
        $idsCsv = implode(',', $ids);

        $rows = DB::select("
            SELECT d.id AS id,
                GROUP_CONCAT(COALESCE(tloc.name, tfb.name)
                    ORDER BY rc.depth DESC SEPARATOR ' > ') AS path
            FROM religion_closure rc
            JOIN religions d ON d.id = rc.descendant_id
            LEFT JOIN religion_translations tloc
                   ON tloc.religion_id = rc.ancestor_id AND tloc.locale = ?
            LEFT JOIN religion_translations tfb
                   ON tfb.religion_id  = rc.ancestor_id AND tfb.locale  = ?
            WHERE d.id IN ($idsCsv)
            GROUP BY d.id
        ", [$locale, $fallback]);

        foreach ($rows as $r) {
            DB::table('religion_translations')
                ->where('religion_id', $r->id)
                ->where('locale', $locale)
                ->update([
                    'path_text'       => $r->path,
                    'lower_path_text' => mb_strtolower($r->path ?? '', 'UTF-8'),
                    'updated_at'      => now(),
                ]);
        }
    }

    /** Get all descendant ids (including root) */
    public function descendantIds(int $rootId): array
    {
        return array_map(
            fn($r) => (int) $r->descendant_id,
            DB::select("SELECT descendant_id FROM religion_closure WHERE ancestor_id = ?", [$rootId])
        );
    }

    /** Set active/inactive recursively for a subtree */
    public function setActiveRecursive(int $rootId, bool $active): int
    {
        $ids = $this->descendantIds($rootId);

        return DB::table('religions')
            ->whereIn('id', $ids)
            ->update([
                'is_active'  => $active ? 1 : 0,
                'updated_at' => now(),
            ]);
    }
}
