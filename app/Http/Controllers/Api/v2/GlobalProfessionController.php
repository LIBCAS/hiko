<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Models\GlobalProfession;
use Illuminate\Http\Request;

class GlobalProfessionController extends Controller
{
    public function index(Request $request)
    {
        return GlobalProfession::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return GlobalProfession::findOrFail($id);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'profession_category_id' => 'nullable|exists:global_profession_categories,id',
        ]);

        $profession = GlobalProfession::create($validated);
        return response()->json($profession, 201);
    }

    public function update(Request $request, $id)
    {
        $profession = GlobalProfession::findOrFail($id);

        $validated = $request->validate([
            'name' => 'sometimes|required|string',
            'profession_category_id' => 'nullable|exists:global_profession_categories,id',
        ]);

        $profession->update($validated);
        return response()->json($profession);
    }

    public function destroy($id)
    {
        $profession = GlobalProfession::findOrFail($id);
        $profession->delete();

        return response()->json(['message' => 'Deleted successfully']);
    }
}
