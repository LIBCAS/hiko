<?php

namespace App\Exports;

use App\Models\Letters;
use Maatwebsite\Excel\Concerns\FromCollection;

class LettersExport implements FromCollection
{
    public function collection()
    {
        return Letters::all();
    }
}
