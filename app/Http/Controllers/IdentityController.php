<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Exports\IdentitiesExport;
use App\Models\ProfessionCategory;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use App\Http\Requests\IdentityRequest;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Str;

class IdentityController extends Controller
{
    public function index(): View
    {
        // Retrieve tenant-specific letters (tenant-specific table is handled dynamically by tenancy)
        $letters = Identity::all(); 

        return view('pages.identities.index', [
            'title' => __('hiko.identities'),
            'labels' => $this->getTypes(),
        ]);
    }

    public function create(): View
    {
        return view(
            'pages.identities.form',
            array_merge(
                [
                    'title' => __('hiko.new_identity'),
                    'action' => route('identities.store'),
                    'label' => __('hiko.create'),
                    'canRemove' => false,
                    'canMerge' => false,
                ],
                $this->viewData(new Identity)
            )
        );
    }

    public function store(IdentityRequest $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        $identity = Identity::create($validated);
    
        // Attach professions (both tenant and global)
        if (isset($validated['profession']) && !empty($validated['profession'])) {
            collect($validated['profession'])->each(function ($professionId, $index) use ($identity) {
                // Determine if the profession is global or local
                $isGlobal = GlobalProfession::find($professionId) !== null;
    
                if ($isGlobal) {
                    // Attach global profession
                    $identity->globalProfessions()->attach($professionId, ['position' => $index]);
                } else {
                    // Attach tenant-specific profession
                    $identity->professions()->attach($professionId, ['position' => $index]);
                }
            });
        }

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route($redirectRoute, $identity->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Identity $identity): View
    {
        $hasLetters = $identity->letters->isNotEmpty();

        return view(
            'pages.identities.form',
            array_merge(
                [
                    'title' => __('hiko.identity') . ': ' . $identity->id,
                    'method' => 'PUT',
                    'action' => route('identities.update', $identity),
                    'label' => __('hiko.edit'),
                    'canRemove' => !$hasLetters,
                    'canMerge' => $hasLetters,
                ],
                $this->viewData($identity)
            )
        );
    }

    public function update(IdentityRequest $request, Identity $identity): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        $identity->update($validated);
    
        // Sync professions (both tenant and global)
        $identity->professions()->detach();
        $identity->globalProfessions()->detach();
    
        if (isset($validated['profession']) && !empty($validated['profession'])) {
            collect($validated['profession'])->each(function ($professionId, $index) use ($identity) {
                // Determine if the profession is global or local
                $isGlobal = GlobalProfession::find($professionId) !== null;
    
                if ($isGlobal) {
                    // Attach global profession
                    $identity->globalProfessions()->attach($professionId, ['position' => $index]);
                } else {
                    // Attach tenant-specific profession
                    $identity->professions()->attach($professionId, ['position' => $index]);
                }
            });
        }

        $this->attachProfessionsAndCategories($identity, $validated);

        return redirect()
            ->route($redirectRoute, $identity->id)
            ->with('success', __('hiko.saved'));
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

    protected function viewData(Identity $identity): array
    {
        return [
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => $this->getSelectedType($identity),
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
        ];
    }

    protected function attachProfessionsAndCategories(Identity $identity, $validated)
    {
        $identity->professions()->detach();
        $identity->profession_categories()->detach();
    
        if (isset($validated['profession']) && !empty($validated['profession'])) {
            foreach ($validated['profession'] as $index => $prefixedId) {
                [$source, $id] = explode('-', $prefixedId, 2);
    
                if ($source === 'local') {
                    $profession = Profession::find($id);
                } elseif ($source === 'global') {
                    // Fetch global profession
                    Tenancy::central(function () use (&$globalProfession, $id) {
                        $globalProfession = GlobalProfession::find($id);
                    });
    
                    // Copy to tenant's professions table
                    if ($globalProfession) {
                        $profession = Profession::firstOrCreate(
                            ['global_profession_id' => $globalProfession->id],
                            ['name' => $globalProfession->name]
                        );
                    } else {
                        continue; // Skip if not found
                    }
                } else {
                    // Assume local if no source
                    $profession = Profession::find($id);
                }
    
                if ($profession) {
                    // Attach to identity
                    $identity->professions()->attach($profession->id, ['position' => $index]);
                }
            }
        }
        /*
        if (isset($validated['category']) && !empty($validated['category'])) {
            collect($validated['category'])
                ->each(function ($category, $index) use ($identity) {
                    $identity->profession_categories()->attach($category, ['position' => $index]);
                });
        }*/
    }

    protected function getTypes(): array
    {
        return ['person', 'institution'];
    }

    protected function getSelectedProfessions(Identity $identity): array
    {
        $selectedIds = request()->old('profession') ?: $identity->professions->pluck('id')->toArray();
    
        $localIds = [];
        $globalIds = [];
    
        foreach ($selectedIds as $id) {
            if (Str::startsWith($id, 'local-')) {
                $localIds[] = Str::after($id, 'local-');
            } elseif (Str::startsWith($id, 'global-')) {
                $globalIds[] = Str::after($id, 'global-');
            } else {
                // If no prefix, assume local
                $localIds[] = $id;
            }
        }
    
        $locale = app()->getLocale();
    
        // Fetch local professions
        $tenantProfessions = Profession::whereIn('id', $localIds)->get()->map(function ($profession) {
            $profession->source = 'local';
            return $profession;
        });
    
        // Fetch global professions
        $globalProfessions = collect();
        if (!empty($globalIds)) {
            Tenancy::central(function () use (&$globalProfessions, $globalIds) {
                $globalProfessions = GlobalProfession::whereIn('id', $globalIds)->get()->map(function ($profession) {
                    $profession->source = 'global';
                    return $profession;
                });
            });
        }
    
        // Merge and format
        $selectedProfessions = $tenantProfessions->merge($globalProfessions)->map(function ($profession) use ($locale) {
            // Determine the name based on the data type
            if ($profession->source === 'local') {
                $name = $profession->name;
            } else {
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
                'value' => $prefixedId,
                'label' => $label,
            ];
        })->toArray();
    
        return $selectedProfessions;
    }    

    protected function getSelectedCategories(Identity $identity): array
    {
        if (!request()->old('category') && !$identity->profession_categories) {
            return [];
        }

        $categories = request()->old('category')
            ? ProfessionCategory::whereIn('id', request()->old('category'))
                ->orderByRaw('FIELD(id, ' . implode(',', request()->old('category')) . ')')->get()
            : $identity->profession_categories->sortBy('pivot.position');

        return $categories
            ->map(function ($category) {
                return [
                    'value' => $category->id,
                    'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
                ];
            })
            ->toArray();
    }

    protected function getSelectedType(Identity $identity): string
    {
        if (!request()->old('type') && !$identity->type) {
            return 'person';
        }

        return request()->old('type')
            ? request()->old('type')
            : $identity->type;
    }
}
