<?php

namespace App\Http\Controllers;

use App\Models\Place;
use App\Models\Letter;
use App\Models\Keyword;
use App\Models\Identity;
use App\Models\Location;
use App\Models\Profession;
use App\Models\KeywordCategory;
use App\Models\ProfessionCategory;
use App\Services\LocalIdentityGlobalCopyService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;

class DevToolsController extends Controller
{
    public function cache()
    {
        Artisan::call('config:cache');
        Artisan::call('route:cache');
        Artisan::call('view:cache');
    }

    public function clear()
    {
        Artisan::call('optimize:clear');
    }

    public function flushSearchIndex()
    {
        Letter::all()->unsearchable();
        Identity::all()->unsearchable();
        Keyword::all()->unsearchable();
        KeywordCategory::all()->unsearchable();
        Place::all()->unsearchable();
        Profession::all()->unsearchable();
        ProfessionCategory::all()->unsearchable();
        Location::all()->unsearchable();
    }

    public function buildSearchIndex()
    {
        Letter::all()->searchable();
        Identity::all()->searchable();
        Keyword::all()->searchable();
        KeywordCategory::all()->searchable();
        Place::all()->searchable();
        Profession::all()->searchable();
        ProfessionCategory::all()->searchable();
        Location::all()->searchable();
    }

    public function symlink()
    {
        Artisan::call('storage:link');
    }

    public function copyLocalIdentitiesToGlobal(Request $request, LocalIdentityGlobalCopyService $service)
    {
        @set_time_limit(0);

        $dryRun = $request->boolean('dry_run', true);
        if (!$dryRun && $request->query('confirm') !== 'copy-local-identities-to-global') {
            abort(400, 'Real execution requires confirm=copy-local-identities-to-global. Dry-run is the default.');
        }

        $tenants = $request->query('tenant', []);
        if (is_string($tenants)) {
            $tenants = explode(',', $tenants);
        }

        $stats = $service->run([
            'dry_run' => $dryRun,
            'tenants' => (array)$tenants,
            'chunk' => (int)$request->query('chunk', 500),
        ]);

        return response()->json([
            'dry_run' => $dryRun,
            'tenant_filter' => array_values(array_filter(array_map('trim', (array)$tenants))),
            'stats' => $stats,
            'real_execution_url_hint' => route('dev.copy-local-identities-to-global', [
                'dry_run' => 0,
                'confirm' => 'copy-local-identities-to-global',
            ]),
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
