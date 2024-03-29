<?php

namespace App\Imports;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class KeywordsImport
{
    /**
     * @throws FileNotFoundException
     */
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/keyword.json')) {
            return 'Soubor neexistuje';
        }

        $keywords = collect(json_decode(Storage::disk('local')->get('imports/keyword.json')));
        
        $uniqueKeywords = $keywords->unique('namecz');

        $uniqueKeywords->each(function ($kw) {
            $data = $this->prepare($kw);
            $data['keyword_category_id'] = $kw->categories ? (int) $kw->categories : null;

            DB::table('keywords')->insert($data);
        });

        return 'Import klíčových slov byl úspěšný';
    }

    protected function prepare($data): array
    {
        $lastCategoryId = DB::table('keyword_categories')->max('id') ?? 0;
        $lastKeywordId = DB::table('keywords')->max('id') ?? 0;

        $newKeywordId = $lastKeywordId + 1;
        $categoryId = $data->is_category ? $lastCategoryId + 1 : ($data->categories ?? null);

        if ($categoryId && !DB::table('keyword_categories')->where('id', $categoryId)->exists()) {
            $categoryId = null;
        }

        return [
            'id' => $data->is_category ? $lastCategoryId + 1 : $newKeywordId,
            'created_at' => now(),
            'updated_at' => now(),
            'name' => json_encode([
                'cs' => $data->namecz,
                'en' => $data->name,
            ], JSON_UNESCAPED_UNICODE),
            'keyword_category_id' => $categoryId,
        ];
    }
}
