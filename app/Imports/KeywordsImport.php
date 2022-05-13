<?php

namespace App\Imports;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KeywordsImport
{
    public function import()
    {
        if (!Storage::disk('local')->exists('imports/keyword.json')) {
            return 'Soubor neexistuje';
        }

        $keywords = collect(json_decode(Storage::disk('local')->get('imports/keyword.json')))
            ->groupBy('is_category');

        $keywords['1']->each(function ($category) {
            DB::table('keyword_categories')
                ->insert($this->prepare($category));
        });

        $keywords['0']->each(function ($kw) {
            $data = $this->prepare($kw);
            $data['keyword_category_id'] = $kw->categories ? (int) $kw->categories : null;

            DB::table('keywords')
                ->insert($data);
        });
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
