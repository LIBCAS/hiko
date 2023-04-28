<?php

namespace App\Imports;

use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class PlacesImport
{
    /**
     * @throws FileNotFoundException
     */
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/place.json')) {
            return 'Soubor neexistuje';
        }

        collect(json_decode(Storage::disk('local')->get('imports/place.json')))
            ->each(function ($place) {
                DB::table('places')
                    ->insert([
                        'name' => $place->name,
                        'created_at' => now(),
                        'country' => $place->country,
                        'note' => $place->note,
                        'latitude' => is_numeric($place->latitude) ? $place->latitude : null,
                        'longitude' => is_numeric($place->longitude) ? $place->longitude : null,
                        'id' => $place->id,
                    ]);
            });

        return 'Import míst byl úspěšný';
    }
}
