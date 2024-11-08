<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use Illuminate\Http\Response;
use App\Http\Controllers\Controller;
use App\Models\ProfessionCategory;
use App\Models\GlobalProfessionCategory;

class AjaxProfessionCategoryController extends Controller
{
    public function index()
    {
        $categories = tenancy()->initialized
            ? ProfessionCategory::all(['id', 'name'])
            : GlobalProfessionCategory::all(['id', 'name']);
    
        return response()->json($categories->map(function ($category) {
            return [
                'id' => $category->id,
                'label' => $category->name,
            ];
        }));
    }    

    public function store(Request $request)
    {
        $validated = $request->validate([
            'cs' => ['max:255', 'required_without:en'],
            'en' => ['max:255', 'required_without:cs'],
        ]);

        $professionCategory = ProfessionCategory::create([
            'name' => [
                'cs' => $validated['cs'],
                'en' => $validated['en'],
            ]
        ]);

        // Return the new category data as JSON
        return response()->json([
            'id' => $professionCategory->id,
            'name' => $professionCategory->getTranslation('name', config('hiko.metadata_default_locale')),
        ]);
    }
}
