<?php

namespace App\Imports;

use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class IdentitiesImport
{
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/person.json')) {
            return 'Soubor neexistuje';
        }

        collect(json_decode(Storage::disk('local')->get('imports/person.json')))
            ->reject(function ($identity) {
                return empty($identity->name);
            })
            ->each(function ($identity) {
                DB::table('identities')
                    ->insert([
                        'name' => $identity->name,
                        'created_at' => $identity->created === '0000-00-00 00:00:00' ? now() : $identity->created,
                        'updated_at' => $identity->modified === '0000-00-00 00:00:00' ? now() : $identity->modified,
                        'surname' => $identity->surname,
                        'forename' => $identity->forename,
                        'birth_year' => $identity->birth_year,
                        'death_year' => $identity->death_year,
                        'note' => $identity->note,
                        'viaf_id' => $identity->viaf,
                        'nationality' => $identity->nationality,
                        'gender' => $identity->gender,
                        'type' => $identity->type === 'institution' ? 'institution' : 'person',
                        'id' => $identity->id,
                    ]);

                $this->attach($identity->id, $identity->profession_short, 'profession_category');
                $this->attach($identity->id, $identity->profession_detailed, 'profession');
            });

        return 'Import identit byl úspěšný';
    }

    protected function attach($identityId, $professions, $key)
    {
        collect(array_filter(explode(';', $professions)))
            ->each(function ($profession, $index) use ($identityId, $key) {

                try {
                    DB::table("identity_{$key}")
                        ->insert([
                            'identity_id' => $identityId,
                            "{$key}_id" => $profession,
                            'position' => $index,
                        ]);
                } catch (QueryException $ex) {
                    dump($ex->getMessage());
                }
            });
    }
}
