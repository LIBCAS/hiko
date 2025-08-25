<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\GlobalKeywordCategory;
use Illuminate\Http\Request;

class GlobalKeywordCategoryController extends Controller
{
    public function index(Request $request)
    {
        return GlobalKeywordCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return GlobalKeywordCategory::findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|json',
        ]);

        $category = GlobalKeywordCategory::create($validated);
        return response()->json($category, 201);
    }

    public function update(Request $request, $id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);

        $validated = $request->validate([
            'name' => 'required|json',
        ]);

        $category->update($validated);
        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = GlobalKeywordCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
