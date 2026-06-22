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

    public function resetLocalIdentitiesToGlobal(Request $request, LocalIdentityGlobalCopyService $service)
    {
        @set_time_limit(0);

        $dryRun = $request->boolean('dry_run', true);
        if (!$dryRun && $request->query('confirm') !== 'reset-local-identities-to-global') {
            abort(400, 'Real reset requires confirm=reset-local-identities-to-global. Dry-run is the default.');
        }

        $type = trim((string)$request->query('type', ''));
        if ($type !== '' && !in_array($type, ['person', 'institution'], true)) {
            abort(422, 'The type parameter must be person or institution.');
        }

        $stats = $service->reset([
            'dry_run' => $dryRun,
            'type' => $type === '' ? null : $type,
        ]);

        return response()->json([
            'dry_run' => $dryRun,
            'type' => $type === '' ? null : $type,
            'stats' => $stats,
            'warning' => $type === ''
                ? 'This operation unlinks all local/global identity references and deletes all global identity data.'
                : "This operation unlinks and deletes global identity data of type {$type}.",
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function removeUndatedDuplicateGlobalIdentities(Request $request, LocalIdentityGlobalCopyService $service)
    {
        @set_time_limit(0);

        $dryRun = $request->boolean('dry_run', true);
        if (!$dryRun && $request->query('confirm') !== 'remove-undated-duplicate-global-identities') {
            abort(400, 'Real cleanup requires confirm=remove-undated-duplicate-global-identities. Dry-run is the default.');
        }

        $stats = $service->removeUndatedDuplicateGroups(['dry_run' => $dryRun]);

        return response()->json([
            'dry_run' => $dryRun,
            'stats' => $stats,
            'warning' => 'This operation removes fully undated duplicate name/type groups and unique fully undated global identities.',
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }

    public function removeAllUndatedGlobalIdentities(Request $request, LocalIdentityGlobalCopyService $service)
    {
        @set_time_limit(0);

        $dryRun = $request->boolean('dry_run', true);
        if (!$dryRun && $request->query('confirm') !== 'remove-all-undated-global-identities') {
            abort(400, 'Real strict cleanup requires confirm=remove-all-undated-global-identities. Dry-run is the default.');
        }

        $stats = $service->removeAllUndatedGlobalIdentities(['dry_run' => $dryRun]);

        return response()->json([
            'dry_run' => $dryRun,
            'stats' => $stats,
            'warning' => 'Strict cleanup removes every global identity with both dates unknown, including records in mixed name/type groups.',
        ], 200, [], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    }
}
