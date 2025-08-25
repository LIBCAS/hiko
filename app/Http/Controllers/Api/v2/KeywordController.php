<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\KeywordRequest;
use App\Models\Keyword;
use Illuminate\Http\Request;

class KeywordController extends Controller
{
    public function index(Request $request)
    {
        return Keyword::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return Keyword::findOrFail($id);
    }

    public function store(KeywordRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $keyword = Keyword::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        return response()->json($keyword, 201);
    }

    public function update(KeywordRequest $request, $id)
    {
        $keyword = Keyword::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($keyword->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $keyword->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'keyword_category_id' => $validated['keyword_category_id'] ?? null,
        ]);

        return response()->json($keyword);
    }

    public function destroy($id)
    {
        $keyword = Keyword::findOrFail($id);
        $keyword->delete();

        return response()->json(['message' => 'Entity deleted successfully']);
    }
}
