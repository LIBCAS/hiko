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

        $json = Storage::disk('local')->get('imports/place.json');
        $places = json_decode($json);

        if (!$places) {
            return 'Chyba při dekódování JSON';
        }

        $importCount = 0;
        $lastId = DB::table('places')->max('id');

        foreach ($places as $place) {
            try {
                $lastId++;
                $identityId = DB::table('places')->insertGetId([
                        'name' => $place->name,
                        'created_at' => now(),
                        'country' => $place->country,
                        'note' => $place->note,
                        'latitude' => is_numeric($place->latitude) ? $place->latitude : null,
                        'longitude' => is_numeric($place->longitude) ? $place->longitude : null,
                        'id' => $lastId,
                ]);

                $importCount++;

            } catch (QueryException $ex) {
                dump($ex->getMessage());
            }
        }


        if ($importCount > 0) {
            return "Import identit byl úspěšný. Počet importovaných záznamů: $importCount";
        } else {
            return 'Žádné záznamy nebyly importovány';
        }
    }
}
