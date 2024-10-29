<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use App\Models\GlobalProfession;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProfessionController extends Controller
{
    protected array $baseRules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
    ];

    public function index(): View
    {
        $professions = tenancy()->initialized
            ? Profession::with('profession_category')->get()
            : GlobalProfession::with('profession_category')->get();

        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
            'professions' => $professions,
        ]);
    }

    public function create(): View
    {
        $availableCategories = tenancy()->initialized
            ? ProfessionCategory::all()
            : GlobalProfessionCategory::all();

        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => new Profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
            'availableCategories' => $availableCategories,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $categoryRule = tenancy()->initialized
            ? 'nullable|exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'nullable|exists:global_profession_categories,id';

        $validated = $request->validate(array_merge($this->baseRules, [
            'category' => [$categoryRule],
        ]));

        if (tenancy()->initialized) {
            $profession = Profession::create([
                'name' => [
                    'cs' => $validated['cs'] ?? null,
                    'en' => $validated['en'] ?? null,
                ],
            ]);

            if (isset($validated['category'])) {
                $category = ProfessionCategory::find($validated['category']);
                if ($category) {
                    $profession->profession_category()->associate($category);
                    $profession->save();
                }
            }
        } else {
            abort(403, 'Unauthorized action.');
        }

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit($profession): View
    {
        if (tenancy()->initialized) {
            $profession = Profession::findOrFail($profession);
            $availableCategories = ProfessionCategory::all();
        } else {
            $profession = GlobalProfession::findOrFail($profession);
            $availableCategories = GlobalProfessionCategory::all();
        }

        $profession->load('identities');

        return view('pages.professions.form', [
            'title' => __('hiko.edit_profession'),
            'profession' => $profession,
            'action' => route('professions.update', $profession->id),
            'method' => 'PUT',
            'label' => __('hiko.save'),
            'availableCategories' => $availableCategories,
        ]);
    }

    public function update(Request $request, $profession): RedirectResponse
    {
        $categoryRule = tenancy()->initialized
            ? 'nullable|exists:' . tenancy()->tenant->table_prefix . '__profession_categories,id'
            : 'nullable|exists:global_profession_categories,id';

        $validated = $request->validate(array_merge($this->baseRules, [
            'category' => [$categoryRule],
        ]));

        if (tenancy()->initialized) {
            $profession = Profession::findOrFail($profession);

            $profession->update([
                'name' => [
                    'cs' => $validated['cs'] ?? null,
                    'en' => $validated['en'] ?? null,
                ],
            ]);

            $profession->profession_category()->dissociate();

            if (isset($validated['category'])) {
                $category = ProfessionCategory::find($validated['category']);
                if ($category) {
                    $profession->profession_category()->associate($category);
                    $profession->save();
                }
            }
        } else {
            abort(403, 'Unauthorized action.');
        }

        return redirect()
            ->route('professions.edit', $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy($profession): RedirectResponse
    {
        if (tenancy()->initialized) {
            $profession = Profession::findOrFail($profession);
            $profession->delete();

            return redirect()
                ->route('professions')
                ->with('success', __('hiko.removed'));
        } else {
            abort(403, 'Unauthorized action.');
        }
    }
}
