<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use App\Services\MergeRelationships;

class MergeController extends Controller
{
    /**
     * @throws \Exception
     */
    public function __invoke(Request $request): RedirectResponse
    {
        (new MergeRelationships($request->input('oldId'), $request->input('newId'), $request->input('model')))
            ->merge();

        return redirect()
            ->route('identities.edit', $request->input('oldId'))
            ->with('success', __('hiko.merged'));
    }
}
