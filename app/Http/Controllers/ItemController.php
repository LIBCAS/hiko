<?php

namespace App\Http\Controllers;

use App\Models\Item;
use App\Http\Requests\StoreItemRequest;
use App\Http\Requests\UpdateItemRequest;
use Illuminate\Http\Request;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Storage;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query();

        if ($request->filled('title')) {
            $query->where('title', 'like', '%' . $request->input('title') . '%');
        }

        if ($request->filled('author')) {
            $query->where('author', 'like', '%' . $request->input('author') . '%');
        }

        if ($request->filled('year')) {
            $query->where('year', $request->input('year'));
        }

        $items = $query->paginate(10);
        return view('items.index', compact('items'));
    }

    public function create()
    {
        return view('items.create');
    }

    public function store(StoreItemRequest $request)
    {
        if ($request->hasFile('document')) {
            $file = $request->file('document');
            $path = Storage::putFile('documents', $file);

            $gemini_result = DocumentService::processHandwrittenLetter($path);

            $item = Item::create([
                'title' => $request->input('title'),
                'full_text' => $gemini_result['full_text'],
                'metadata' => $gemini_result['metadata'],
                'summary' => $gemini_result['summary'],
                'language' => $gemini_result['language'],
            ]);
            return redirect()->route('items.index')->with('success', 'Item created.');
        } else {
            return redirect()->back()->withErrors(["document" => "Missing document"]);
        }
    }

    public function show(Item $item)
    {
        return view('items.show', compact('item'));
    }

    public function edit(Item $item)
    {
        return view('items.edit', compact('item'));
    }

    public function update(UpdateItemRequest $request, Item $item)
    {
        $item->update($request->validated());
        return redirect()->route('items.index')->with('success', 'Item updated.');
    }

    public function destroy(Item $item)
    {
        $item->delete();
        return redirect()->route('items.index')->with('success', 'Item deleted.');
    }
}
