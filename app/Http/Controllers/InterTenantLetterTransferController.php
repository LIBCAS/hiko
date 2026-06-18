<?php

namespace App\Http\Controllers;

use App\Http\Requests\ApproveInterTenantLetterTransferRequest;
use App\Http\Requests\RejectInterTenantLetterTransferRequest;
use App\Http\Requests\SaveInterTenantLetterTransferMappingsRequest;
use App\Http\Requests\StoreInterTenantTransferRequest;
use App\Models\InterTenantTransferRequest;
use App\Models\Tenant;
use App\Services\InterTenantDependencyCopyService;
use App\Services\InterTenantLetterTransferData;
use App\Services\InterTenantLetterTransferService;
use App\Services\LetterFilterService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Throwable;

class InterTenantLetterTransferController extends Controller
{
    public function index()
    {
        $tenantId = tenancy()->tenant->id;

        return view('pages.inter-tenant-transfers.index', [
            'title' => __('hiko.inter_tenant_transfers'),
            'incoming' => InterTenantTransferRequest::query()
                ->with(['sourceTenant', 'targetTenant'])
                ->where('target_tenant_id', $tenantId)
                ->latest()
                ->get(),
            'outgoing' => InterTenantTransferRequest::query()
                ->with(['sourceTenant', 'targetTenant'])
                ->where('source_tenant_id', $tenantId)
                ->latest()
                ->get(),
        ]);
    }

    public function preview(Tenant $targetTenant, LetterFilterService $filters, InterTenantLetterTransferData $data)
    {
        abort_if((int) $targetTenant->id === (int) tenancy()->tenant->id, 422);

        $activeFilters = session()->get('lettersTableFilters', []);
        $limit = config('inter_tenant_transfers.max_letters', 200);
        $letters = $filters->filteredQuery($activeFilters)->orderBy('id')->limit($limit + 1)->get();

        if ($letters->isEmpty()) {
            return redirect()->route('letters')->with('error', __('hiko.transfer_empty_selection'));
        }

        if ($letters->count() > $limit) {
            return redirect()->route('letters')->with('error', __('hiko.transfer_selection_too_large', ['limit' => $limit]));
        }

        $payload = $data->load(tenancy()->tenant, $letters->pluck('id')->all());

        return view('pages.inter-tenant-transfers.preview', [
            'title' => __('hiko.transfer_preview'),
            'targetTenant' => $targetTenant,
            'payload' => $payload,
            'filters' => $activeFilters,
            'sourceDomain' => tenancy()->tenant->domains()->value('domain'),
        ]);
    }

    public function store(StoreInterTenantLetterTransferRequest $request, InterTenantLetterTransferData $data)
    {
        $validated = $request->validated();
        abort_if((int) $validated['target_tenant_id'] === (int) tenancy()->tenant->id, 422);

        $data->load(tenancy()->tenant, $validated['letter_ids']);
        $user = $request->user();

        $transfer = InterTenantTransferRequest::create([
            'source_tenant_id' => tenancy()->tenant->id,
            'target_tenant_id' => $validated['target_tenant_id'],
            'entity_type' => 'letters',
            'status' => InterTenantTransferRequest::STATUS_PENDING,
            'requested_by_user_id' => $user->id,
            'requested_by_name' => $user->name,
            'requested_by_email' => $user->email,
            'source_record_ids' => array_values($validated['letter_ids']),
            'filters' => session()->get('lettersTableFilters', []),
        ]);

        return redirect()->route('inter-tenant-transfers.show', $transfer)
            ->with('success', __('hiko.transfer_requested'));
    }

    public function show(
        InterTenantTransferRequest $transfer,
        InterTenantLetterTransferData $data,
        InterTenantDependencyCopyService $copyService,
        InterTenantLetterTransferService $transferService
    )
    {
        $tenantId = (int) tenancy()->tenant->id;
        abort_unless(in_array($tenantId, [(int) $transfer->source_tenant_id, (int) $transfer->target_tenant_id], true), 403);

        $transfer->load(['sourceTenant.domains', 'targetTenant.domains']);
        $payload = null;
        $loadError = null;

        if ($transfer->isPending()) {
            try {
                $payload = $data->load($transfer->sourceTenant, $transfer->source_record_ids);
            } catch (Throwable $e) {
                $loadError = $e->getMessage();
            }
        }

        $identityAutoMappings = $payload && $tenantId === (int) $transfer->target_tenant_id
            ? $copyService->identityAutoMappings($payload, tenancy()->tenant)
            : [];
        $savedMappings = [];
        $mappingWarnings = [];

        if ($payload && $tenantId === (int) $transfer->target_tenant_id && is_array($transfer->mappings)) {
            $restored = $transferService->restoreDraftMappings(
                $payload['dependencies'],
                tenancy()->tenant,
                $transfer->mappings
            );
            $savedMappings = $restored['mappings'];
            $mappingWarnings = $restored['warnings'];
        }

        return view('pages.inter-tenant-transfers.show', [
            'title' => __('hiko.transfer_request') . ' #' . $transfer->id,
            'transfer' => $transfer,
            'payload' => $payload,
            'loadError' => $loadError,
            'isTarget' => $tenantId === (int) $transfer->target_tenant_id,
            'sourceDomain' => $transfer->sourceTenant->domains->first()?->domain,
            'targetDomain' => $transfer->targetTenant->domains->first()?->domain,
            'identityAutoMappings' => $identityAutoMappings,
            'savedMappings' => $savedMappings,
            'mappingWarnings' => $mappingWarnings,
        ]);
    }

    public function copyDependencyPreview(
        InterTenantTransferRequest $transfer,
        string $type,
        int $sourceId,
        InterTenantDependencyCopyService $service
    ) {
        abort_unless((int) $transfer->target_tenant_id === (int) tenancy()->tenant->id, 403);

        try {
            return response()->json(
                $service->preview($transfer, tenancy()->tenant, $type, $sourceId)
            );
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function copyDependency(
        Request $request,
        InterTenantTransferRequest $transfer,
        string $type,
        int $sourceId,
        InterTenantDependencyCopyService $service
    ) {
        abort_unless((int) $transfer->target_tenant_id === (int) tenancy()->tenant->id, 403);
        $validated = $request->validate([
            'category_id' => ['nullable', 'integer'],
        ]);

        try {
            $created = $service->copy(
                $transfer,
                tenancy()->tenant,
                $request->user(),
                $type,
                $sourceId,
                $validated['category_id'] ?? null
            );
            $record = DB::connection('tenant')
                ->table(tenancy()->tenant->table_prefix . '__' . $type)
                ->find($created['id']);

            return response()->json([
                'message' => __('hiko.transfer_dependency_copied'),
                'option' => $this->mappingSearchResult($type, 'local', $record),
                'action' => $created['action'],
            ]);
        } catch (Throwable $e) {
            report($e);

            return response()->json(['message' => $e->getMessage()], 422);
        }
    }

    public function approve(
        ApproveInterTenantLetterTransferRequest $request,
        InterTenantTransferRequest $transfer,
        InterTenantLetterTransferService $service
    ) {
        abort_unless((int) $transfer->target_tenant_id === (int) tenancy()->tenant->id, 403);

        try {
            $service->approve($transfer, tenancy()->tenant, $request->user(), (array) $request->validated('mappings', []));
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inter-tenant-transfers.show', $transfer)
            ->with('success', __('hiko.transfer_completed'));
    }

    public function saveMappings(
        SaveInterTenantLetterTransferMappingsRequest $request,
        InterTenantTransferRequest $transfer,
        InterTenantLetterTransferService $service
    ) {
        abort_unless((int) $transfer->target_tenant_id === (int) tenancy()->tenant->id, 403);

        try {
            $service->saveDraftMappings(
                $transfer,
                tenancy()->tenant,
                (array) $request->validated('mappings', [])
            );
        } catch (Throwable $e) {
            report($e);

            return back()->withInput()->with('error', $e->getMessage());
        }

        return redirect()->route('inter-tenant-transfers.show', $transfer)
            ->with('success', __('hiko.transfer_mappings_saved'));
    }

    public function reject(RejectInterTenantLetterTransferRequest $request, InterTenantTransferRequest $transfer)
    {
        abort_unless((int) $transfer->target_tenant_id === (int) tenancy()->tenant->id, 403);
        $validated = $request->validated();
        $user = $request->user();

        DB::connection('mysql')->transaction(function () use ($transfer, $validated, $user) {
            $locked = InterTenantTransferRequest::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isPending(), 403);
            $locked->update([
                'status' => InterTenantTransferRequest::STATUS_REJECTED,
                'decision_reason' => $validated['reason'] ?? null,
                'decided_by_user_id' => $user->id,
                'decided_by_name' => $user->name,
                'decided_by_email' => $user->email,
                'decided_at' => now(),
            ]);
        });

        return back()->with('success', __('hiko.transfer_rejected'));
    }

    public function cancel(Request $request, InterTenantTransferRequest $transfer)
    {
        abort_unless((int) $transfer->source_tenant_id === (int) tenancy()->tenant->id, 403);
        $user = $request->user();

        DB::connection('mysql')->transaction(function () use ($transfer, $user) {
            $locked = InterTenantTransferRequest::query()->whereKey($transfer->id)->lockForUpdate()->firstOrFail();
            abort_unless($locked->isPending(), 403);
            $locked->update([
                'status' => InterTenantTransferRequest::STATUS_CANCELLED,
                'decided_by_user_id' => $user->id,
                'decided_by_name' => $user->name,
                'decided_by_email' => $user->email,
                'decided_at' => now(),
            ]);
        });

        return back()->with('success', __('hiko.transfer_cancelled'));
    }

    public function searchMapping(Request $request, string $type): array
    {
        abort_unless(in_array($type, ['identities', 'places', 'keywords', 'locations'], true), 404);

        $search = trim((string) $request->query('search'));
        if ($search === '') {
            return [];
        }

        $localTable = tenancy()->tenant->table_prefix . '__' . $type;
        $columns = match ($type) {
            'identities' => ['id', 'name', 'type', 'birth_year', 'death_year'],
            'places' => ['id', 'name', 'country', 'division'],
            'keywords' => ['id', 'name'],
            'locations' => ['id', 'name', 'type'],
        };

        $localQuery = DB::connection('tenant')->table($localTable)
            ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($search) . '%']);

        if ($type === 'identities') {
            $sourceType = $request->query('source_type');
            if (!in_array($sourceType, \App\Enums\IdentityType::values(), true)) {
                return [];
            }
            $localQuery->where('type', $sourceType);
        }

        if ($type === 'locations') {
            $locationType = $request->query('location_type');
            if (!in_array($locationType, \App\Enums\LocationType::values(), true)) {
                return [];
            }
            $localQuery->where('type', $locationType);
        }

        $results = $localQuery->limit(20)->get($columns)
            ->map(fn ($row) => $this->mappingSearchResult($type, 'local', $row));

        if (in_array($type, ['places', 'keywords', 'locations'], true)) {
            $globalQuery = DB::connection('tenant')->table('global_' . $type)
                ->whereRaw('LOWER(name) LIKE ?', ['%' . mb_strtolower($search) . '%']);

            if ($type === 'locations') {
                $globalQuery->where('type', $locationType);
            }

            $results = $results->merge(
                $globalQuery->limit(20)->get($columns)
                    ->map(fn ($row) => $this->mappingSearchResult($type, 'global', $row))
            );
        }

        return $results->sortBy('label', SORT_NATURAL | SORT_FLAG_CASE)->take(20)->values()->all();
    }

    private function mappingSearchResult(string $type, string $scope, object $row): array
    {
        $label = $row->name;
        if ($type === 'keywords') {
            $name = json_decode($row->name, true);
            $label = $name[app()->getLocale()] ?? $name['cs'] ?? $name['en'] ?? $row->name;
        } elseif ($type === 'identities') {
            $dates = trim(($row->birth_year ?? '') . ' - ' . ($row->death_year ?? ''));
            $label .= $dates !== '-' ? " ({$dates})" : '';
        } elseif ($type === 'places') {
            $label = implode(', ', array_filter([$row->name, $row->division ?? null, $row->country ?? null]));
        }

        $route = match ([$type, $scope]) {
            ['identities', 'local'] => 'identities.edit',
            ['places', 'local'] => 'places.edit',
            ['places', 'global'] => 'global.places.edit',
            ['keywords', 'local'] => 'keywords.edit',
            ['keywords', 'global'] => 'global.keywords.edit',
            ['locations', 'local'] => 'locations.edit',
            ['locations', 'global'] => 'global.locations.edit',
        };

        return [
            'id' => (int) $row->id,
            'scope' => $scope,
            'value' => "{$scope}-{$row->id}",
            'label' => $label . ' (' . __('hiko.' . $scope) . ')',
            'edit_url' => route($route, $row->id),
        ];
    }
}
