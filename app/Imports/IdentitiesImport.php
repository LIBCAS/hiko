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

        $json = Storage::disk('local')->get('imports/person.json');
        $identities = json_decode($json);

        if (!$identities) {
            return 'Chyba při dekódování JSON';
        }

        $importCount = 0;

        foreach ($identities as $identity) {
            if (empty($identity->name)) {
                continue;
            }

            try {
                DB::table('identities')
                    ->insert([
                        'name' => $identity->name,
                        'created_at' => $identity->created_at === '0000-00-00 00:00:00' ? now() : $identity->created_at,
                        'updated_at' => $identity->updated_at === '0000-00-00 00:00:00' ? now() : $identity->updated_at,
                        'surname' => $identity->surname,
                        'forename' => $identity->forename,
                        'birth_year' => $identity->birth_year,
                        'death_year' => $identity->death_year,
                        'note' => $identity->note,
                        'viaf_id' => $identity->viaf_id,
                        'nationality' => $identity->nationality,
                        'alternative_names' => is_array($identity->alternative_names) ? json_encode($identity->alternative_names) : $identity->alternative_names,
                        'gender' => $identity->gender,
                        'type' => $identity->type === 'institution' ? 'institution' : 'person',
                        'id' => $identity->id,
                    ]);

                $importCount++;
                $this->attach($identity->id, $identity->profession_short, 'profession_category');
                $this->attach($identity->id, $identity->profession_detailed, 'profession');
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

    protected function attach($identityId, $professions, $key)
    {
        $professionArray = array_filter(explode(';', $professions));

        if (empty($professionArray)) {
            return;
        }

        foreach ($professionArray as $index => $profession) {
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
        }
    }
}
