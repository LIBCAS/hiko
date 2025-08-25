<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordCategoryRequest;
use App\Models\KeywordCategory;
use Illuminate\Http\Request;

class KeywordCategoryController extends Controller
{
    public function index(Request $request)
    {
        return KeywordCategory::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return KeywordCategory::findOrFail($id);
    }

    public function store(KeywordCategoryRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $category = KeywordCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return response()->json($category, 201);
    }

    public function update(KeywordCategoryRequest $request, $id)
    {
        $category = KeywordCategory::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($category->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $category->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        return response()->json($category);
    }

    public function destroy($id)
    {
        $category = KeywordCategory::findOrFail($id);
        $category->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
