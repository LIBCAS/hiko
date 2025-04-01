<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Http\Requests\IdentityRequest;
use Maatwebsite\Excel\Facades\Excel;
use App\Exports\IdentitiesExport;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\Log;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class IdentityController extends Controller
{
    public function index()
    {
        $labels = [
            'name' => __('hiko.name'),
            'surname' => __('hiko.surname'),
            'type' => __('hiko.type'),
        ];

        $identities = $this->findIdentities();

        return view('pages.identities.index', [
            'title' => __('hiko.identities'),
            'labels' => $labels,
            'identities' => $identities,
        ]);
    }

    protected function findIdentities()
    {
        return Identity::select('id', 'name', 'type')
            ->with(['professions:id,name', 'profession_categories:id,name'])
            ->orderBy('name')
            ->paginate(25);
    }

    public function create()
    {
        $identity = new Identity();
        $identity->related_names = [];

        return view('pages.identities.form', [
            'title' => __('hiko.new_identity'),
            'method' => 'POST',
            'action' => route('identities.store'),
            'label' => __('hiko.create'),
            'canRemove' => false,
            'canMerge' => false,
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => 'person',
            'selectedProfessions' => [],
            'selectedCategories' => [],
            'professionsList' => $this->getProfessionsList(),
            'categoriesList' => $this->getCategoriesList(),
        ]);
    }

    public function edit(Identity $identity)
    {
        // Log raw values before processing
        Log::info('Raw related_names:', ['related_names' => $identity->related_names]);
        Log::info('Raw related_identity_resources:', ['related_identity_resources' => $identity->related_identity_resources]);

        // Ensure related_names is an array
        $identity->related_names = $this->ensureArray($identity->related_names, 'related_names');

        // Ensure related_identity_resources is an array
        $identity->related_identity_resources = $this->ensureArray($identity->related_identity_resources, 'related_identity_resources');

        // Log processed values
        Log::info('Processed related_names:', ['related_names' => $identity->related_names]);
        Log::info('Processed related_identity_resources:', ['related_identity_resources' => $identity->related_identity_resources]);

        $hasLetters = $identity->letters()->exists();

        return view('pages.identities.form', [
            'title' => __('hiko.identity') . ': ' . $identity->id,
            'method' => 'PUT',
            'action' => route('identities.update', $identity),
            'label' => __('hiko.edit'),
            'canRemove' => !$hasLetters,
            'canMerge' => $hasLetters,
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => $identity->type ?? 'person',
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
            'professionsList' => $this->getProfessionsList(),
            'categoriesList' => $this->getCategoriesList(),
            'resources' => $identity->related_identity_resources,
            'relatedNames' => $identity->related_names,
        ]);
    }

    /**
     * Helper method to ensure data is always returned as an array.
     */
    protected function ensureArray($data, $key)
    {
        if (is_string($data)) {
            $decodedData = json_decode($data, true);

            if (isset($decodedData[$key]) && is_string($decodedData[$key])) {
                return json_decode($decodedData[$key], true) ?? [];
            }

            return is_array($decodedData) ? $decodedData : [];
        }

        return is_array($data) ? $data : [];
    }

    public function update(IdentityRequest $request, Identity $identity): RedirectResponse
    {
        $validated = $request->validated();
        $validated['related_names'] = json_encode($validated['related_names'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        $identity->update($validated);
        $this->syncRelations($identity, $validated);

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('identities.create')
                ->with('success', __('hiko.saved_and_new'));
        } else {
            return redirect()
                ->route('identities.edit', $identity->id)
                ->with('success', __('hiko.saved'));
        }
    }

    public function store(IdentityRequest $request): RedirectResponse
    {
        $validated = $request->validated();

        // Convert array fields to JSON strings if necessary
        $validated['related_names'] = json_encode($validated['related_names'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $validated['related_identity_resources'] = json_encode($validated['related_identity_resources'] ?? [], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

        // Remove non-existent columns in the identities table
        unset($validated['category'], $validated['profession']);

        if ($validated['type'] !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }

        // Log the validated data for debugging
        Log::info('Validated data before create:', $validated);

        // Create the new Identity record
        $identity = Identity::create($validated);

        // Sync professions and categories if provided
        $this->syncRelations($identity, $request->validated());

        if ($request->input('action') === 'create') {
            return redirect()
                ->route('identities.create')
                ->with('success', __('hiko.saved_and_new'));
        } else {
            return redirect()
                ->route('identities.edit', $identity->id)
                ->with('success', __('hiko.saved'));
        }
    }

    public function destroy(Identity $identity): RedirectResponse
    {
        $identity->delete();

        return redirect()
            ->route('identities')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new IdentitiesExport, 'identities.xlsx');
    }

    protected function syncRelations(Identity $identity, array $validated)
    {
        if (!empty($validated['profession'])) {
            $this->syncProfessions($identity, $validated['profession']);
        }
        if (!empty($validated['category'])) {
            $identity->profession_categories()->sync($validated['category']);
        }
    }

    protected function syncProfessions(Identity $identity, array $professions): void
    {
        $localIds = [];
        $globalIds = [];
    
        foreach ($professions as $professionId) {
            $isGlobal = str_starts_with($professionId, 'global-');
            $cleanId = (int) str_replace(['global-', 'local-'], '', $professionId);
    
            if ($isGlobal) {
                $globalIds[] = $cleanId;
            } else {
                $localIds[] = $cleanId;
            }
        }
    
        $tenantPivotTable = tenancy()->tenant->table_prefix . '__identity_profession';
    
        DB::transaction(function () use ($identity, $localIds, $globalIds, $tenantPivotTable) {
            // 🧹 Remove all existing local & global professions
            DB::table($tenantPivotTable)
                ->where('identity_id', $identity->id)
                ->delete();
    
            // 🔗 Attach Local Professions
            if (!empty($localIds)) {
                $localData = collect($localIds)->mapWithKeys(function ($id) {
                    return [$id => [
                        'profession_id' => $id,
                        'global_profession_id' => null,
                        'position' => null,
                    ]];
                })->toArray();
    
                $identity->professions()->attach($localData);
            }
    
            // 🔗 Attach Global Professions (profession_id = null is now valid)
            if (!empty($globalIds)) {
                $globalData = collect($globalIds)->map(function ($id) use ($identity) {
                    return [
                        'identity_id' => $identity->id,
                        'profession_id' => null,
                        'global_profession_id' => $id,
                        'position' => null,
                    ];
                })->toArray();
    
                DB::table($tenantPivotTable)->insert($globalData);
            }
        });
    }    

    protected function getTypes(): array
    {
        return ['person', 'institution'];
    }

    protected function getSelectedProfessions(Identity $identity): array
    {
        $selectedProfessions = $identity->professions->map(fn($localProfession) => [
            'value' => 'local-' . $localProfession->id,
            'label' => $localProfession->name . ' (Local)',
        ])->toArray();

        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantTablePrefix = tenancy()->tenant->table_prefix . '__identity_profession';

            Tenancy::central(function () use ($identity, &$selectedProfessions, $tenantTablePrefix) {
                $globalProfessionIds = DB::table($tenantTablePrefix)
                    ->where('identity_id', $identity->id)
                    ->whereNotNull('global_profession_id')
                    ->pluck('global_profession_id');

                $globalProfessions = GlobalProfession::whereIn('id', $globalProfessionIds)->get();

                foreach ($globalProfessions as $globalProfession) {
                    $selectedProfessions[] = [
                        'value' => 'global-' . $globalProfession->id,
                        'label' => $globalProfession->name . ' (Global)',
                    ];
                }
            });
        }

        return $selectedProfessions;
    }

    protected function getProfessionsList(): array
    {
        $professions = [];

        if (tenancy()->initialized) {
            $localProfessions = Profession::select('id', 'name')
                ->orderBy('name')
                ->paginate(25)
                ->map(fn($profession) => [
                    'value' => 'local-' . $profession->id,
                    'label' => $profession->name ? "{$profession->name} (Local)" : "No Name (Local)",
                ])->toArray();
            $professions = array_merge($professions, $localProfessions);
        }

        Tenancy::central(function () use (&$professions) {
            $globalProfessions = GlobalProfession::select('id', 'name')
                ->orderBy('name')
                ->paginate(25)
                ->map(fn($profession) => [
                    'value' => 'global-' . $profession->id,
                    'label' => $profession->name ? "{$profession->name} (Global)" : "No Name (Global)",
                ])->toArray();
            $professions = array_merge($professions, $globalProfessions);
        });

        return $professions;
    }

    protected function getCategoriesList(): array
    {
        $categories = [];

        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantTable = tenancy()->tenant->table_prefix . '__profession_categories';
            $categories = DB::table($tenantTable)->select('id', 'name')
                ->orderBy('name')
                ->paginate(25)
                ->map(fn($category) => [
                    'value' => $category->id,
                    'label' => json_decode($category->name, true)[config('app.locale')] ?? '—',
                ])->toArray();
        } else {
            $categories = ProfessionCategory::select('id', 'name')
                ->orderBy('name')
                ->paginate(25)
                ->map(fn($category) => [
                    'value' => $category->id,
                    'label' => $category->getTranslation('name', config('app.locale')),
                ])->toArray();
        }

        return $categories;
    }

    protected function getSelectedCategories(Identity $identity): array
    {
        $selectedIds = old('profession_category', $identity->profession_categories->pluck('id')->toArray());
        $categories = [];

        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantTable = tenancy()->tenant->table_prefix . '__profession_categories';
            $categories = DB::table($tenantTable)->whereIn('id', $selectedIds)->get()->map(fn($category) => [
                'value' => $category->id,
                'label' => json_decode($category->name)->{config('app.locale')},
            ])->toArray();
        } else {
            $categories = ProfessionCategory::whereIn('id', $selectedIds)->get()->map(fn($category) => [
                'value' => $category->id,
                'label' => $category->getTranslation('name', config('app.locale')),
            ])->toArray();
        }

        return $categories;
    }
}
