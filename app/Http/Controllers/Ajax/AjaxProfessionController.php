<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Models\Profession;
use App\Models\GlobalProfession;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;

class AjaxProfessionController extends Controller
{
    public function __invoke(Request $request): array
    {
        if (empty($request->query('search'))) {
            return [];
        }

        $search = Str::lower($request->query('search'));
        $results = [];

        // Use accent-insensitive collation to match diacritics-insensitively
        $localProfessions = Profession::whereRaw("LOWER(name) COLLATE utf8mb4_general_ci like ?", ['%' . $search . '%'])
            ->select('id', 'name')
            ->take(25)
            ->get()
            ->map(function ($profession) {
                return [
                    'id'    => 'local-' . $profession->id,
                    'value' => 'local-' . $profession->id,
                    'label' => "{$profession->name} (Local)",
                ];
            });

        $results = $localProfessions->toArray();

        // Fetch global professions within central context (MySQL/MariaDB Compatible)
        Tenancy::central(function () use ($search, &$results) {
            try {
                // Check if the database is MariaDB
                $dbVersion = DB::select("SELECT VERSION() AS version")[0]->version;
                $isMariaDB = Str::contains(strtolower($dbVersion), 'mariadb');

                // Use COLLATE on the extracted JSON value
                $jsonQuery = $isMariaDB
                    ? "LOWER(JSON_VALUE(name, '$.en')) COLLATE utf8mb4_general_ci like ?"
                    : "LOWER(JSON_UNQUOTE(name->'$.en')) COLLATE utf8mb4_general_ci like ?";

                $globalProfessions = GlobalProfession::whereRaw($jsonQuery, ['%' . $search . '%'])
                    ->select('id', 'name')
                    ->take(25)
                    ->get()
                    ->map(function ($profession) {
                        return [
                            'id'    => 'global-' . $profession->id,
                            'value' => 'global-' . $profession->id,
                            'label' => "{$profession->name} (Global)",
                        ];
                    });

                $results = array_merge($results, $globalProfessions->toArray());

            } catch (\Exception $e) {
                Log::error("Error fetching global professions: " . $e->getMessage());
            }
        });

        return $results;
    }
}
