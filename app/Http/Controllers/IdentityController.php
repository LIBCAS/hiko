<?php

namespace App\Http\Controllers;

use App\Models\Identity;
use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use App\Exports\IdentitiesExport;
use App\Http\Requests\IdentityRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Stancl\Tenancy\Facades\Tenancy;
use Symfony\Component\HttpFoundation\BinaryFileResponse;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;

class IdentityController extends Controller
{
    /**
     * Display a listing of the identities.
     *
     * @return View
     */
    public function index(): View
    {
        try {
            // Retrieve all identities with their relationships
            $identities = Identity::with(['professions', 'profession_categories', 'letters'])->get();

            return view('pages.identities.index', [
                'title' => __('hiko.identities'),
                'labels' => $this->getTypes(),
                'identities' => $identities, // Pass identities to the view
            ]);
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@index: {$e->getMessage()}");
            return redirect()->back()->with('error', __('hiko.unexpected_error'));
        }
    }

    /**
     * Show the form for creating a new identity.
     *
     * @return View
     */
    public function create(): View
    {
        try {
            // Fetch available categories based on tenancy
            $availableCategories = $this->isTenancyInitialized()
                ? ProfessionCategory::all()
                : GlobalProfessionCategory::all();

            // Fetch available professions
            $professionsList = $this->getProfessionsList();

            return view('pages.identities.form', [
                'title' => __('hiko.new_identity'),
                'action' => route('identities.store'),
                'label' => __('hiko.create'),
                'canRemove' => false,
                'canMerge' => false,
                'identity' => new Identity(),
                'types' => $this->getTypes(),
                'selectedType' => 'person',
                'selectedProfessions' => [],
                'selectedCategories' => [],
                'professionsList' => $professionsList,
                'categoriesList' => $this->getCategoriesList(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@create: {$e->getMessage()}");
            return redirect()->back()->with('error', __('hiko.unexpected_error'));
        }
    }

    /**
     * Store a newly created identity in storage.
     *
     * @param  \App\Http\Requests\IdentityRequest  $request
     * @return RedirectResponse
     */
    public function store(IdentityRequest $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        try {
            // Create the identity
            $identity = Identity::create($validated);

            // Attach professions (tenant-specific or global)
            if (isset($validated['profession']) && !empty($validated['profession'])) {
                $this->attachProfessions($identity, $validated['profession']);
            }

            // Attach profession categories
            if (isset($validated['profession_category']) && !empty($validated['profession_category'])) {
                $this->attachProfessionCategories($identity, $validated['profession_category']);
            }

            return redirect()
                ->route($redirectRoute, $identity->id)
                ->with('success', __('hiko.saved'));
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@store: {$e->getMessage()}");
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('hiko.error_saving'));
        }
    }

    /**
     * Show the form for editing the specified identity.
     *
     * @param  \App\Models\Identity  $identity
     * @return View
     */
    public function edit(Identity $identity): View
    {
        try {
            $hasLetters = $identity->letters()->exists();

            // Fetch available categories based on tenancy
            $availableCategories = $this->isTenancyInitialized()
                ? ProfessionCategory::all()
                : GlobalProfessionCategory::all();

            // Fetch available professions
            $professionsList = $this->getProfessionsList();

            // Prepare selected professions and categories
            $selectedProfessions = $this->getSelectedProfessions($identity);
            $selectedCategories = $this->getSelectedCategories($identity);

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
                'selectedProfessions' => $selectedProfessions,
                'selectedCategories' => $selectedCategories,
                'professionsList' => $professionsList,
                'categoriesList' => $this->getCategoriesList(),
            ]);
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@edit: {$e->getMessage()}");
            return redirect()->back()->with('error', __('hiko.unexpected_error'));
        }
    }

    /**
     * Update the specified identity in storage.
     *
     * @param  \App\Http\Requests\IdentityRequest  $request
     * @param  \App\Models\Identity  $identity
     * @return RedirectResponse
     */
    public function update(IdentityRequest $request, Identity $identity): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'identities.create' : 'identities.edit';

        $validated = $request->validated();

        try {
            // Update the identity
            $identity->update($validated);

            // Sync professions (detach all and re-attach)
            $identity->professions()->detach();

            if (isset($validated['profession']) && !empty($validated['profession'])) {
                $this->attachProfessions($identity, $validated['profession']);
            }

            // Sync profession categories
            $identity->profession_categories()->detach();

            if (isset($validated['profession_category']) && !empty($validated['profession_category'])) {
                $this->attachProfessionCategories($identity, $validated['profession_category']);
            }

            return redirect()
                ->route($redirectRoute, $identity->id)
                ->with('success', __('hiko.saved'));
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@update: {$e->getMessage()}");
            return redirect()
                ->back()
                ->withInput()
                ->with('error', __('hiko.error_updating'));
        }
    }

    /**
     * Remove the specified identity from storage.
     *
     * @param  \App\Models\Identity  $identity
     * @return RedirectResponse
     */
    public function destroy(Identity $identity): RedirectResponse
    {
        try {
            // Check if identity is associated with any letters
            if ($identity->letters()->exists()) {
                return redirect()
                    ->route('identities.index')
                    ->with('error', __('hiko.cannot_delete_associated'));
            }

            // Delete the identity
            $identity->delete();

            return redirect()
                ->route('identities.index')
                ->with('success', __('hiko.removed'));
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@destroy: {$e->getMessage()}");
            return redirect()
                ->back()
                ->with('error', __('hiko.error_deleting'));
        }
    }

    /**
     * Export identities to an Excel file.
     *
     * @return BinaryFileResponse
     */
    public function export(): BinaryFileResponse
    {
        try {
            return Excel::download(new IdentitiesExport, 'identities.xlsx');
        } catch (\Exception $e) {
            Log::error("Error in IdentityController@export: {$e->getMessage()}");
            return redirect()->back()->with('error', __('hiko.error_exporting'));
        }
    }

    /**
     * Attach professions to the identity.
     *
     * @param  \App\Models\Identity  $identity
     * @param  array  $professions
     * @return void
     */
    protected function attachProfessions(Identity $identity, array $professions): void
    {
        foreach ($professions as $index => $professionId) {
            // Ensure the profession ID is an integer
            if (!is_numeric($professionId)) {
                Log::warning("Invalid profession ID: {$professionId}");
                continue;
            }
    
            $professionId = (int) $professionId;
    
            if ($this->isTenancyInitialized()) {
                // Attach tenant-specific profession
                $profession = Profession::find($professionId);
                if ($profession) {
                    $identity->professions()->attach($profession->id, ['position' => $index]);
                } else {
                    Log::warning("Tenant-specific Profession with ID {$professionId} not found.");
                }
            } else {
                // Attach global profession
                $globalProfession = GlobalProfession::find($professionId);
                if ($globalProfession) {
                    $identity->professions()->attach($globalProfession->id, ['position' => $index]);
                } else {
                    Log::warning("Global Profession with ID {$professionId} not found.");
                }
            }
        }
    }    

    /**
     * Attach profession categories to the identity.
     *
     * @param  \App\Models\Identity  $identity
     * @param  array  $categories
     * @return void
     */
    protected function attachProfessionCategories(Identity $identity, array $categories): void
    {
        foreach ($categories as $index => $categoryId) {
            // Ensure the category ID is an integer
            if (!is_numeric($categoryId)) {
                Log::warning("Invalid profession category ID: {$categoryId}");
                continue;
            }

            $categoryId = (int) $categoryId;

            $identity->profession_categories()->attach($categoryId, ['position' => $index]);
        }
    }

    /**
     * Prepare data for the identity form view.
     *
     * @param  \App\Models\Identity  $identity
     * @return array
     */
    protected function viewData(Identity $identity): array
    {
        return [
            'identity' => $identity,
            'types' => $this->getTypes(),
            'selectedType' => $this->getSelectedType($identity),
            'selectedProfessions' => $this->getSelectedProfessions($identity),
            'selectedCategories' => $this->getSelectedCategories($identity),
            'professionsList' => $this->getProfessionsList(),
            'categoriesList' => $this->getCategoriesList(),
        ];
    }

    /**
     * Get the list of types.
     *
     * @return array
     */
    protected function getTypes(): array
    {
        return ['person', 'institution'];
    }

    /**
     * Get the selected type for the form.
     *
     * @param  \App\Models\Identity  $identity
     * @return string
     */
    protected function getSelectedType(Identity $identity): string
    {
        return old('type', $identity->type ?? 'person');
    }

    /**
     * Get the selected professions for the form.
     *
     * @param  \App\Models\Identity  $identity
     * @return array
     */
    protected function getSelectedProfessions(Identity $identity): array
    {
        $selectedIds = old('profession', $identity->professions->pluck('id')->toArray());

        $selectedProfessions = [];

        foreach ($selectedIds as $professionId) {
            if ($this->isTenancyInitialized()) {
                // Fetch tenant-specific profession
                $profession = Profession::find($professionId);
                if ($profession) {
                    $label = "{$profession->name} (Local)";
                } else {
                    $label = "Unknown (Local)";
                }
            } else {
                // Fetch global profession
                Tenancy::central(function () use (&$globalProfession, $professionId) {
                    $globalProfession = GlobalProfession::find($professionId);
                });

                if (isset($globalProfession)) {
                    $label = "{$globalProfession->name} (Global)";
                } else {
                    $label = "Unknown (Global)";
                }
            }

            if (isset($label)) {
                $selectedProfessions[] = [
                    'value' => $professionId,
                    'label' => $label,
                ];
            }
        }

        return $selectedProfessions;
    }

    /**
     * Get the selected categories for the form.
     *
     * @param  \App\Models\Identity  $identity
     * @return array
     */
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

    /**
     * Get the list of professions for the form.
     *
     * @return array
     */
    protected function getProfessionsList(): array
    {
        $professions = [];

        // Fetch tenant-specific professions
        if ($this->isTenancyInitialized()) {
            $tenantProfessions = Profession::all()->map(function ($profession) {
                return [
                    'value' => $profession->id,
                    'label' => "{$profession->name} (Local)",
                ];
            });

            $professions = array_merge($professions, $tenantProfessions->toArray());
        }

        // Fetch global professions within central tenancy
        Tenancy::central(function () use (&$globalProfessions) {
            $globalProfessions = GlobalProfession::all()->map(function ($profession) {
                return [
                    'value' => $profession->id,
                    'label' => "{$profession->name} (Global)",
                ];
            });
        });

        if (isset($globalProfessions)) {
            $professions = array_merge($professions, $globalProfessions->toArray());
        }

        return $professions;
    }

    /**
     * Get the list of profession categories for the form.
     *
     * @return array
     */
    protected function getCategoriesList(): array
    {
        $categories = ProfessionCategory::all()->map(function ($category) {
            return [
                'value' => $category->id,
                'label' => $category->getTranslation('name', config('app.locale')),
            ];
        });

        return $categories->toArray();
    }

    /**
     * Check if tenancy is initialized.
     *
     * @return bool
     */
    protected function isTenancyInitialized(): bool
    {
        return tenancy()->initialized;
    }
}
