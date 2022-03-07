<?php

namespace App\Exports;

use App\Models\Letter;
use Maatwebsite\Excel\Concerns\FromCollection;

class PalladioCharacterExport implements FromCollection
{
    public $role;

    public function __construct($role)
    {
        $this->role = $role;
    }

    public function collection()
    {
        return Letter::all();
    }
}
