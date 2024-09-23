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
        $professions = GlobalProfession::with('category')->paginate(20);
        return view('admin.global_professions.index', compact('professions'));
    }

    public function create()
    {
        $categories = GlobalProfessionCategory::all();
        return view('admin.global_professions.create', compact('categories'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'category_id' => 'required|exists:global_profession_categories,id',
        ]);

        GlobalProfession::create($request->all());

        return redirect()->route('admin.global-professions.index')->with('success', 'Global Profession created successfully.');
    }

    // Similarly implement show, edit, update, destroy...
}
