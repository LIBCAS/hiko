<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Http\Requests\IdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class IdentityController extends Controller
{
    public function index(Identity $identity)
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

    public function edit(Identity $identity)
    {
        $identity->related_names = is_array($identity->related_names)
            ? $identity->related_names
            : json_decode($identity->related_names, true) ?? [];
    
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
        ]);
    }    

    public function update(IdentityRequest $request, Identity $identity): RedirectResponse
    {
        $validated = $request->validated();
        
        $validated['related_names'] = json_encode($validated['related_names'] ?? []);

        $identity->update($validated);

        if (isset($validated['profession']) && is_array($validated['profession'])) {
            $this->syncProfessions($identity, $validated['profession']);
        }

        if (isset($validated['category']) && is_array($validated['category'])) {
            $identity->profession_categories()->sync($validated['category']);
        }

        return redirect()
            ->route('identities.edit', $identity->id)
            ->with('success', __('hiko.saved'));
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
    
        // Define tenant-specific pivot table name with prefix
        $tenantPivotTable = tenancy()->tenant->table_prefix . '__identity_profession';
    
        // Detach relevant entries for clean sync
        $identity->professions()->detach($localIds);
        $identity->professions()->wherePivot('global_profession_id', '!=', null)->detach();
    
        // Attach local professions
        foreach ($localIds as $localId) {
            $identity->professions()->attach($localId, ['global_profession_id' => null]);
        }
    
        // Insert global professions with profession_id set to NULL
        foreach ($globalIds as $globalId) {
            try {
                \DB::table($tenantPivotTable)->insert([
                    'identity_id' => $identity->id,
                    'profession_id' => null,  // Set explicitly to NULL
                    'global_profession_id' => $globalId,
                    'position' => null,       // Adjust as necessary
                ]);
            } catch (\Exception $e) {
            }
        }
    
        Log::info("Profession sync completed for Identity ID {$identity->id}");
    }    
     
    protected function getTypes(): array
    {
        return ['person', 'institution'];
    }

    protected function getSelectedProfessions(Identity $identity): array
    {
        $selectedProfessions = [];
    
        // Fetch local professions
        foreach ($identity->professions as $localProfession) {
            $selectedProfessions[] = [
                'value' => 'local-' . $localProfession->id,
                'label' => $localProfession->name . ' (Local)',
            ];
        }
    
        // Check if tenancy is initialized and fetch global professions accordingly
        if (tenancy()->initialized && tenancy()->tenant) {
            $tenantTablePrefix = tenancy()->tenant->table_prefix . '__identity_profession';
    
            // Fetch global professions in the central context
            Tenancy::central(function () use ($identity, &$selectedProfessions, $tenantTablePrefix) {
                $globalProfessionIds = \DB::table($tenantTablePrefix)
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

    protected function getSelectedCategories(Identity $identity): array
    {
        $selectedIds = old('profession_category', $identity->profession_categories->pluck('id')->toArray());
        $categories = ProfessionCategory::whereIn('id', $selectedIds)->get();

        return $categories->map(function ($category) {
            return [
                'value' => $category->id,
                'label' => $category->getTranslation('name', config('app.locale')),
            ];
        })->toArray();
    }

    protected function getProfessionLabel($profession, $type): ?string
    {
        return $profession ? "{$profession->name} ({$type})" : "No Name ({$type})";
    }

    protected function getGlobalProfessionLabel($professionId): ?string
    {
        $label = null;
        Tenancy::central(function () use (&$label, $professionId) {
            $globalProfession = GlobalProfession::find($professionId);
            $label = $globalProfession ? "{$globalProfession->name} (Global)" : "No Name (Global)";
        });

        return $label;
    }

    protected function getProfessionsList(): array
    {
        $professions = [];
    
        // Fetch local professions if tenant context is initialized
        if (tenancy()->initialized) {
            $localProfessions = Profession::all()->map(function ($profession) {
                return [
                    'value' => 'local-' . $profession->id,
                    'label' => $profession->name ? "{$profession->name} (Local)" : "No Name (Local)",
                ];
            });
            $professions = array_merge($professions, $localProfessions->toArray());
        }
    
        // Fetch global professions in the central database context
        Tenancy::central(function () use (&$professions) {
            $globalProfessions = GlobalProfession::all()->map(function ($profession) {
                return [
                    'value' => 'global-' . $profession->id,
                    'label' => $profession->name ? "{$profession->name} (Global)" : "No Name (Global)",
                ];
            });
            $professions = array_merge($professions, $globalProfessions->toArray());
        });
    
        return $professions;
    }

    protected function getCategoriesList(): array
    {
        return ProfessionCategory::all()->map(function ($category) {
            return [
                'value' => $category->id,
                'label' => $category->getTranslation('name', config('app.locale')),
            ];
        })->toArray();
    }
}
