<?php

namespace App\Http\Controllers;

use App\Models\Letter;

class EditLinkController extends Controller
{
    public function __invoke(Letter $letter)
    {
        return redirect()->route('letters.edit', $letter);
    }
}
