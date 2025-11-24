<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AjaxReligionController extends Controller
{
    public function __invoke(Request $request): array
    {
        $term = trim((string) $request->query('search', ''));

        $locale = app()->getLocale();

        // Build base query
        $query = DB::table('religion_translations as rt')
            ->join('religions as r', 'r.id', '=', 'rt.religion_id')
            ->select('rt.religion_id as id', 'rt.path_text')
            ->where('rt.locale', $locale)
            ->where('r.is_active', 1);

        // Apply search filter only if there's a meaningful search term
        if ($term !== '' && $term !== ' ') {
            $needle = mb_strtolower($term, 'UTF-8');
            $query->where(function ($q) use ($needle) {
                $q->whereRaw('LOWER(rt.name) LIKE ?', ["%{$needle}%"])
                    ->orWhereRaw('LOWER(rt.path_text) LIKE ?', ["%{$needle}%"])
                    ->orWhere('rt.lower_path_text', 'like', "%{$needle}%");
            });
        }

        $rows = $query->orderBy('rt.path_text')
            ->limit(50)
            ->get();

        // The repeated-select expects "value" (submitted) and "label" (shown).
        return $rows->map(fn($r) => [
            'id'    => (int) $r->id,
            'value' => (int) $r->id,      // submit the integer religion_id
            'label' => $r->path_text,     // render the full path in the current locale
        ])->values()->toArray();
    }
}
