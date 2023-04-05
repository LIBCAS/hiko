<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Model;

class Language extends Model
{
    use Sushi;

    public function getRows(): array
    {
        $languages = json_decode(file_get_contents(resource_path() . '/data/languages.json'), true);

        return collect(array_values($languages))->map(function ($item, $index) {
            return [
                'id' => $index,
                'name' => $item['name'],
            ];
        })->toArray();
    }
}
