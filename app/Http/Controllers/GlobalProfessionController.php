<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\GlobalProfession;
use App\Models\GlobalProfessionCategory;
use Illuminate\Http\Request;

class GlobalProfessionController extends Controller
{
    public function index()
    {
        // Fetch global professions with their categories
        $professions = GlobalProfession::with('category')->paginate(20);
        return view('admin.global_professions.index', compact('professions'));
    }

    public function create()
    {
        // Fetch global categories only for profession creation
        $categories = GlobalProfessionCategory::all();
        return view('admin.global_professions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        // Validate and create a global profession linked to a global category
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:global_profession_categories,id',
        ]);
    
        GlobalProfession::create($request->all());
    
        return redirect()->route('admin.global-professions.index')->with('success', 'Global Profession created successfully.');
    }    
}
