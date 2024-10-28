<?php

namespace App\Imports;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ProfessionsImport
{
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/profession.json')) {
            return 'Soubor neexistuje';
        }

        $data = json_decode(Storage::disk('local')->get('imports/profession.json'));

        if (json_last_error() !== JSON_ERROR_NONE) {
            return 'Chybný formát souboru: ' . json_last_error_msg();
        }

        foreach ($data as $item) {
            if (is_object($item)) {
                DB::table($item->palladio === '0' ? 'professions' : 'profession_categories')
                    ->insert($this->prepare($item));
            }
        }

        return 'Import profesí byl úspěšný';
    }

    protected function prepare($data): array
    {
        $lastProfessionId = DB::table('professions')->orderByDesc('id')->value('id') ?? 0;
        $professionId = $lastProfessionId + 1;

        $lastCategorytId = DB::table('profession_categories')->orderByDesc('id')->value('id') ?? 0;
        $categoryId = $lastCategorytId + 1;

        return [
            'id' => $data->palladio === '0' ? $professionId : $categoryId,
            'created_at' => now(),
            'updated_at' => now(),
            'name' => json_encode([
                'cs' => $data->namecz,
                'en' => $data->name,
            ], JSON_UNESCAPED_UNICODE),
        ];
    }
}
