<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Sushi;

    protected $connection = 'tenant';

    public function getRows(): array
    {
        $languages = json_decode(file_get_contents(resource_path() . '/data/languages.json'), true);

        return collect($languages)
            ->map(function ($item, $code) {
                return [
                    'code' => $code,
                    'name' => $item['name'],
                ];
            })
            ->values()
            ->map(function ($item, $index) {
                return [
                    'id' => $index,
                    'code' => $item['code'],
                    'name' => $item['name'],
                ];
            })
            ->toArray();
    }
}
