<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfessionsImport
{
    public function import()
    {
        if (!Storage::disk('local')->exists('imports/profession.json')) {
            return 'Soubor neexistuje';
        }

        collect(json_decode(Storage::disk('local')->get('imports/profession.json')))
            ->each(function ($item) {
                DB::table(
                    $item->palladio === '0' ? 'professions' : 'profession_categories'
                )
                    ->insert($this->prepare($item));
            });

        return 'Import profesí byl úspěšný';
    }

    protected function prepare($data)
    {
        return [
            'id' => $data->id,
            'created_at' => now(),
            'updated_at' => now(),
            'name' => json_encode([
                'cs' => $data->namecz,
                'en' => $data->name,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
