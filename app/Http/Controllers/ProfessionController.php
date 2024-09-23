<?php

namespace App\Http\Controllers;

use App\Models\Profession;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use App\Exports\ProfessionsExport;
use App\Models\ProfessionCategory;
use Illuminate\View\View;
use Maatwebsite\Excel\Facades\Excel;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProfessionController extends Controller
{
    protected array $rules = [
        'cs' => ['max:255', 'required_without:en'],
        'en' => ['max:255', 'required_without:cs'],
        'category' => ['nullable', 'exists:profession_categories,id'],
    ];

    public function index(): View
    {
        // Retrieve tenant-specific professions
        $tenantProfessions = Profession::all(); 
    
        // Retrieve global professions
        $globalProfessions = DB::table('global_professions')->get();
    
        // Combine both tenant-specific and global professions
        $professions = $tenantProfessions->concat($globalProfessions);
    
        return view('pages.professions.index', [
            'title' => __('hiko.professions'),
            'professions' => $professions, // Pass the merged professions to the view
        ]);
    }    

    public function create(): View
    {
        $profession = new Profession;

        return view('pages.professions.form', [
            'title' => __('hiko.new_profession'),
            'profession' => $profession,
            'action' => route('professions.store'),
            'label' => __('hiko.create'),
            'category' => $this->getCategory($profession),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.create' : 'professions.edit';

        $validated = $request->validate($this->rules);

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        if (isset($validated['category'])) {
            $profession->profession_category()->associate($validated['category']);
        }

        $profession->save();

        return redirect()
            ->route($redirectRoute, $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function edit(Profession $profession): View
    {
        return view('pages.professions.form', [
            'title' => __('hiko.profession') . ': ' . $profession->id,
            'profession' => $profession,
            'method' => 'PUT',
            'action' => route('professions.update', $profession),
            'label' => __('hiko.edit'),
            'category' => $this->getCategory($profession),
        ]);
    }

    public function update(Request $request, Profession $profession): RedirectResponse
    {
        $redirectRoute = $request->action === 'create' ? 'professions.create' : 'professions.edit';

        $validated = $request->validate($this->rules);

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
        ]);

        $profession->profession_category()->dissociate();

        if (isset($validated['category'])) {
            $profession->profession_category()->associate($validated['category']);
        }

        $profession->save();

        return redirect()
            ->route($redirectRoute, $profession->id)
            ->with('success', __('hiko.saved'));
    }

    public function destroy(Profession $profession): RedirectResponse
    {
        $profession->delete();

        return redirect()
            ->route('professions')
            ->with('success', __('hiko.removed'));
    }

    public function export(): BinaryFileResponse
    {
        return Excel::download(new ProfesionsExport, 'professions.xlsx');
    }

    protected function getCategory(Profession $profession): ?array
    {
        if (!$profession->profession_category && !request()->old('category')) {
            return null;
        }

        $id = request()->old('category') ? request()->old('category') : $profession->profession_category->id;

        $category = request()->old('category')
            ? ProfessionCategory::where('id', '=', request()->old('category'))->get()[0]
            : $profession->profession_category;

        return [
            'id' => $id,
            'label' => $category->getTranslation('name', config('hiko.metadata_default_locale')),
        ];
    }
}
