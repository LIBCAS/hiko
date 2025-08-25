<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\IdentityRequest;
use App\Models\Identity;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\Response;

class IdentityController extends Controller
{
    public static int $maxPerPage = 100;
    public static int $defaultPerPage = 20;

    public function index(Request $request)
    {
        $perPage = (int) $request->query('per_page', self::$defaultPerPage);
        $page = (int) $request->query('page', 1);

        $perPage = max(1, min(self::$maxPerPage, $perPage));
        $page = max(1, $page);

        $identities = Identity::with([
                'localProfessions',
                'globalProfessions',
            ])
            ->paginate($perPage, ['*'], 'page', $page);

        return response()->json($identities);
    }

    public function show($id)
    {
        $identity = Identity::with([
            'localProfessions',
            'globalProfessions',
        ])->findOrFail($id);
        return response()->json($identity);
    }

    public function store(IdentityRequest $request)
    {
        $validated = $request->validated();

        $validated['related_names'] = json_encode($validated['related_names'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $validated['related_identity_resources'] = json_encode($validated['related_identity_resources'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        unset($validated['category'], $validated['profession']);

        if ($validated['type'] !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }

        Log::info('API V2: Creating Identity', ['data' => $validated]);

        $identity = Identity::create($validated);

        $this->syncRelations($identity, $request->validated());

        return response()->json($identity, Response::HTTP_CREATED);
    }

    public function update(IdentityRequest $request, $id)
    {
        $identity = Identity::findOrFail($id);

        $validated = $request->validated();
        $validated['related_names'] = json_encode($validated['related_names'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $validated['related_identity_resources'] = json_encode($validated['related_identity_resources'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        unset($validated['category'], $validated['profession']);

        if ($validated['type'] !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }

        Log::info('API V2: Updating Identity', ['id' => $identity->id, 'data' => $validated]);

        $identity->update($validated);

        $this->syncRelations($identity, $request->validated());

        return response()->json($identity);
    }

    public function destroy($id)
    {
        $identity = Identity::findOrFail($id);
        $identity->delete();

        return response()->json(['message' => 'Entity deleted successfully.']);
    }

    /**
     * Syncs the relations for the given identity based on the validated data.
     *
     * @param Identity $identity
     * @param array $validated
     */
    protected function syncRelations(Identity $identity, array $validated): void
    {
        $localIds = collect($validated['local_professions'] ?? [])->map(fn($id) => (int) $id);
        $globalIds = collect($validated['global_professions'] ?? [])->map(fn($id) => (int) $id);

        // Fall back to combined professions input
        if (empty($localIds) && empty($globalIds) && !empty($validated['profession'])) {
            foreach ($validated['profession'] as $professionId) {
                $isGlobal = str_starts_with($professionId, 'global-');
                $cleanId = (int) str_replace(['global-', 'local-'], '', $professionId);

                if ($isGlobal) {
                    $globalIds->push($cleanId);
                } else {
                    $localIds->push($cleanId);
                }
            }
        }

        $tenantPivotTable = tenancy()->tenant->table_prefix . '__identity_profession';

        DB::transaction(function () use ($identity, $localIds, $globalIds, $tenantPivotTable) {
            DB::table($tenantPivotTable)->where('identity_id', $identity->id)->delete();

            if ($localIds->isNotEmpty()) {
                $localData = $localIds->mapWithKeys(fn($id) => [$id => [
                    'profession_id' => $id,
                    'global_profession_id' => null,
                    'position' => null,
                ]])->toArray();
                $identity->professions()->attach($localData);
            }

            if ($globalIds->isNotEmpty()) {
                $globalData = $globalIds->map(fn($id) => [
                    'identity_id' => $identity->id,
                    'profession_id' => null,
                    'global_profession_id' => $id,
                    'position' => null,
                ])->toArray();
                DB::table($tenantPivotTable)->insert($globalData);
            }

            if (!empty($validated['category'])) {
                $identity->profession_categories()->sync($validated['category']);
            }
        });
    }
}
