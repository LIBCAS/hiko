<?php

namespace App\Http\Controllers\Ajax;

use App\Http\Controllers\Controller;
use App\Services\LetterFilterOptions;
use Illuminate\Http\Request;

class AjaxLetterFilterOptionsController extends Controller
{
    public function __invoke(Request $request, LetterFilterOptions $options): array
    {
        $validated = $request->validate([
            'field' => ['required', 'string', 'in:' . implode(',', \App\Services\LetterFilterService::ENTITY_FILTERS)],
            'search' => ['nullable', 'string', 'max:255'],
        ]);
        $search = trim($validated['search'] ?? '');

        return $search === '' ? [] : $options->options($validated['field'], search: $search);
    }
}
