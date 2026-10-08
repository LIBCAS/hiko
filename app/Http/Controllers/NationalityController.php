<?php

namespace App\Http\Controllers;

use App\Models\Nationality;
use App\Http\Requests\NationalityRequest;
use Illuminate\Database\QueryException;

class NationalityController extends Controller
{
    public function index()
    {
        return view('pages.nationalities.index', [
            'nationalities' => Nationality::orderedForLocale(),
            'title' => __('hiko.nationalities'),
        ]);
    }

    public function store(NationalityRequest $request)
    {
        $this->assertLock($request);
        Nationality::create(['name' => $request->validated()]);
        return redirect()->route('nationalities.index')->with('success', __('hiko.saved'));
    }

    public function update(NationalityRequest $request, Nationality $nationality)
    {
        $this->assertLock($request);
        $nationality->update(['name' => $request->validated()]);
        return redirect()->route('nationalities.index')->with('success', __('hiko.saved'));
    }

    public function destroy(\Illuminate\Http\Request $request, Nationality $nationality)
    {
        $this->assertLock($request);
        try {
            $nationality->delete();
        } catch (QueryException $e) {
            if (($e->errorInfo[1] ?? null) !== 1451) throw $e;
            return back()->withErrors(['nationality' => __('hiko.nationality_in_use')]);
        }
        return redirect()->route('nationalities.index')->with('success', __('hiko.removed'));
    }

    public function expansions()
    {
        return view('pages.nationalities.expansions', [
            'nationalities' => Nationality::orderedForLocale(),
            'title' => __('hiko.nationality_expansions'),
            'expansions' => \Illuminate\Support\Facades\DB::table('nationality_search_expansions')
                ->orderBy('source_nationality_id')->orderBy('target_nationality_id')->get(),
        ]);
    }

    public function storeExpansion(\App\Http\Requests\NationalityExpansionRequest $request)
    {
        $this->assertLock($request);
        \Illuminate\Support\Facades\DB::table('nationality_search_expansions')->insertOrIgnore($request->validated());
        return redirect()->route('nationalities.expansions.index')->with('success', __('hiko.saved'));
    }

    public function destroyExpansion(\Illuminate\Http\Request $request, Nationality $source, Nationality $target)
    {
        $this->assertLock($request);
        \Illuminate\Support\Facades\DB::table('nationality_search_expansions')
            ->where('source_nationality_id', $source->id)->where('target_nationality_id', $target->id)->delete();
        return redirect()->route('nationalities.expansions.index')->with('success', __('hiko.removed'));
    }

    private function assertLock($request): void
    {
        $lock = app(\App\Services\PageLockService::class)->assertOwned(['scope' => 'global', 'resource_type' => 'nationalities_admin'], $request->user());
        abort_unless($lock['ok'], 423, __('hiko.page_lock_not_owned'));
    }
}
