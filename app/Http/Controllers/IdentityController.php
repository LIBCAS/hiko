<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Http\Requests\IdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\Log;

class IdentityController extends Controller
{
    public function index()
    {
        $identities = Identity::with(['professions', 'profession_categories', 'letters'])->get();
        $labels = [
            'name' => __('hiko.name'),
            'surname' => __('hiko.surname'),
            'type' => __('hiko.type'),
        ];

        return view('pages.identities.index', [
            'title' => __('hiko.identities'),
            'labels' => $labels,
            'identities' => $identities,
        ]);
    }
    public function create()
    {
        $identity = new Identity();
        $identity->related_names = [];
    
        return view('pages.identities.form', [
            'title' => __('hiko.create_identity'),
            'method' => 'POST',
            'action' => route('identities.store'),
            'label' => __('hiko.create'),
            'canRemove' => false,
            'canMerge' => false,
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => 'person',  // Default type for new identity
            'selectedProfessions' => [], // Initialize as empty array
            'selectedCategories' => [],  // Initialize as empty array
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
        $validated['related_names'] = json_encode($validated['related_names'] ?? []);
    
        $identity->update($validated);
        $this->syncRelations($identity, $validated);
    
        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
    }
    
    public function store(IdentityRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $validated['related_names'] = json_encode($validated['related_names'] ?? []);
    
        // Remove non-existent columns in the identities table
        unset($validated['category'], $validated['profession']);
    
        if ($validated['type'] !== 'person') {
            unset($validated['surname'], $validated['forename'], $validated['general_name_modifier']);
        }
    
        // Create the new Identity record
        $identity = Identity::create($validated);
    
        // Sync professions and categories if provided
        $this->syncRelations($identity, $request->validated());
    
        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
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
            $cleanProfessionId = (int) str_replace(['global-', 'local-'], '', $professionId);

            if ($isGlobal) {
                Tenancy::central(function () use (&$globalIds, $cleanProfessionId) {
                    if (GlobalProfession::find($cleanProfessionId)) {
                        $globalIds[] = $cleanProfessionId;
                    }
                });
            } else {
                if (Profession::find($cleanProfessionId)) {
                    $localIds[] = $cleanProfessionId;
                }
            }
        }

        $tenantPivotTable = tenancy()->tenant->table_prefix . '__identity_profession';
        $identity->professions()->syncWithoutDetaching($localIds);
        $identity->professions()->wherePivot('global_profession_id', '!=', null)->detach();

        foreach ($globalIds as $globalId) {
            DB::table($tenantPivotTable)->insertOrIgnore([
                'identity_id' => $identity->id,
                'profession_id' => null,
                'global_profession_id' => $globalId,
                'position' => null,
            ]);
        }
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
            $localProfessions = Profession::all()->map(fn($profession) => [
                'value' => 'local-' . $profession->id,
                'label' => $profession->name ? "{$profession->name} (Local)" : "No Name (Local)",
            ])->toArray();
            $professions = array_merge($professions, $localProfessions);
        }

        Tenancy::central(function () use (&$professions) {
            $globalProfessions = GlobalProfession::all()->map(fn($profession) => [
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
    
        // Check if tenancy is initialized to use tenant-specific table
        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantTable = tenancy()->tenant->table_prefix . '__profession_categories';
            $categories = DB::table($tenantTable)->get()->map(fn($category) => [
                'value' => $category->id,
                'label' => json_decode($category->name)->{config('app.locale')},
            ])->toArray();
        } else {
            // Fallback to global table if no tenant-specific table is initialized
            $categories = ProfessionCategory::all()->map(fn($category) => [
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
