<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\GlobalKeyword;
use Illuminate\Http\Request;

class GlobalKeywordController extends Controller
{
    public function index(Request $request)
    {
        return GlobalKeyword::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return GlobalKeyword::findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|json',
            'keyword_category_id' => 'nullable|exists:global_keyword_categories,id',
        ]);

        $keyword = GlobalKeyword::create($validated);
        return response()->json($keyword, 201);
    }

    public function update(Request $request, $id)
    {
        $keyword = GlobalKeyword::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|json',
            'keyword_category_id' => 'nullable|exists:global_keyword_categories,id',
        ]);

        $keyword->update($validated);
        return response()->json($keyword);
    }

    public function destroy($id)
    {
        $keyword = GlobalKeyword::findOrFail($id);
        $keyword->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
