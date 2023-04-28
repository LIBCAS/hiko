<?php

namespace App\Models;

use Sushi\Sushi;
use Illuminate\Database\Eloquent\Model;

class Country extends Model
{
    use Sushi;

    public function getRows()
    {
        $countries = json_decode(file_get_contents(resource_path() . '/data/countries.json'), true);

        return collect($countries)->map(function ($item, $index) {
            return [
                'id' => $index,
                'name' => $item['name'],
            ];
        })->toArray();
    }
}
