<?php

namespace App\Http\Controllers\Ajax;

use App\Models\Profession;
use App\Models\GlobalProfession;
use Illuminate\Support\Str;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Stancl\Tenancy\Facades\Tenancy;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request): array
    {
        $search = $request->query('search');
        if (empty($search)) {
            return [];
        }

        $locale = config('hiko.metadata_default_locale');

        // Fetch tenant-specific professions (case-insensitive search)
        $tenantProfessions = Profession::whereRaw('LOWER(name) like ?', ['%' . strtolower($search) . '%'])
            ->select('id', 'name')
            ->take(10)
            ->get()
            ->map(function ($profession) {
                $profession->source = 'local';
                return $profession;
            });

        // Fetch global professions (case-insensitive search)
        $globalProfessions = collect();
        Tenancy::central(function () use (&$globalProfessions, $search, $locale) {
            $globalProfessions = GlobalProfession::whereRaw("LOWER(name->'$.{$locale}') like ?", ['%' . strtolower($search) . '%'])
                ->select('id', 'name')
                ->take(10)
                ->get();
        });

        // Map over $globalProfessions outside the closure to set 'source'
        $globalProfessions = $globalProfessions->map(function ($profession) {
            $profession->source = 'global';
            return $profession;
        });

        // Merge collections
        $professions = $tenantProfessions->merge($globalProfessions);

        // Limit total results to 10 if needed
        $professions = $professions->take(10);

        // Map to array with prefixed IDs to avoid conflicts
        return $professions->map(function ($profession) use ($locale) {
            // Determine the name based on the data type
            if ($profession->source === 'local') {
                // Tenant profession: name is a string
                $name = $profession->name;
            } else {
                // Global profession: name is JSON, cast to array
                if (is_array($profession->name)) {
                    $name = $profession->name[$locale] ?? 'No Name';
                } else {
                    $nameArray = json_decode($profession->name, true);
                    $name = $nameArray[$locale] ?? 'No Name';
                }
            }

            $sourceLabel = $profession->source === 'global' ? ' (Global)' : ' (Local)';
            $label = $name . $sourceLabel;

            $prefixedId = $profession->source . '-' . $profession->id;

            return [
                'id' => $prefixedId,
                'value' => $prefixedId,
                'label' => $label,
            ];
        })->toArray();
    }
}
