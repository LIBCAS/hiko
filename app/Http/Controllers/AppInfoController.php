<?php

namespace App\Http\Controllers;

use App\Http\Requests\TenantApplicationNameRequest;

class AppInfoController extends Controller
{
    public function __invoke()
    {
        $tenant = tenancy()->tenant;

        return view('pages.app.index', [
            'title' => __('hiko.application_info'),
            'tenant' => $tenant,
            'applicationNames' => $tenant->applicationDisplayNames(),
        ]);
    }

    public function updateApplicationName(TenantApplicationNameRequest $request)
    {
        $tenant = tenancy()->tenant;

        $tenant->setApplicationDisplayNames(
            $request->validated('application_name_cs'),
            $request->validated('application_name_en'),
        );
        $tenant->save();

        return redirect()
            ->route('app')
            ->with('success', __('hiko.saved'));
    }
}
