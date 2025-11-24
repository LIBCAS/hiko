<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;

class ReligionSearchController extends Controller
{
    public function search(Request $request)
    {
        return Tenancy::central(function () use ($request) {
            $q = mb_strtolower((string) $request->get('q', ''), 'UTF-8');
            $locale = $request->get('locale', app()->getLocale());
            if ($q === '') return response()->json([]);

            $collation = 'utf8mb4_0900_ai_ci'; // accent-insensitive

            $ids = DB::table('religion_translations as rt')
                ->select('rt.religion_id')
                ->whereIn('rt.locale', ['cs', 'en'])
                ->whereRaw("rt.lower_path_text COLLATE $collation LIKE ?", ["%$q%"])
                ->distinct()
                ->limit(50)
                ->pluck('religion_id');

            // labels in current locale with cs fallback
            $rows = DB::table('religion_translations as loc')
                ->select('loc.religion_id as id', DB::raw('COALESCE(loc.path_text, fb.path_text) as label'))
                ->leftJoin('religion_translations as fb', function ($j) {
                    $j->on('fb.religion_id', '=', 'loc.religion_id')->where('fb.locale', 'cs');
                })
                ->where('loc.locale', $locale)
                ->whereIn('loc.religion_id', $ids)
                ->orderBy('label')
                ->limit(20)
                ->get();

            return $rows;
        });
    }
}
