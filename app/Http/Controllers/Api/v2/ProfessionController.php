<?php

namespace App\Http\Controllers\Api\v2;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfessionRequest;
use App\Models\Profession;
use Illuminate\Http\Request;

class ProfessionController extends Controller
{
    public function index(Request $request)
    {
        return Profession::paginate(
            min(max((int) $request->query('per_page', 20), 1), 100)
        );
    }

    public function show($id)
    {
        return Profession::findOrFail($id);
    }

    public function store(ProfessionRequest $request)
    {
        $validated = $request->validated();

        if ($request->failsDuplicateCheck()) {
            return response()->json(['message' => 'Such entity already exists.'], 409);
        }

        $profession = Profession::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return response()->json($profession, 201);
    }

    public function update(ProfessionRequest $request, $id)
    {
        $profession = Profession::findOrFail($id);
        $validated = $request->validated();

        if ($request->failsDuplicateCheck($profession->id)) {
            return response()->json(['message' => 'Such entity already exists.'], 422);
        }

        $profession->update([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ],
            'profession_category_id' => $validated['profession_category_id'] ?? null,
        ]);

        return response()->json($profession);
    }

    public function destroy($id)
    {
        $profession = Profession::findOrFail($id);
        $profession->delete();

        return response()->json(['message' => 'Entity deleted successfully']);
    }
}
