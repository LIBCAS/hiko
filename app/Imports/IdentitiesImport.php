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
        $lastId = DB::table('identities')->max('id');

        foreach ($identities as $identity) {
            try {
                $lastId++;
                $identityId = DB::table('identities')->insertGetId([
                    'id' => $lastId,
                    'name' => $identity->name,
                    'created_at' => $identity->created_at === '0000-00-00 00:00:00' ? now() : $identity->created_at,
                    'updated_at' => $identity->updated_at === '0000-00-00 00:00:00' ? now() : $identity->updated_at,
                    'surname' => $identity->surname,
                    'forename' => $identity->forename,
                    'general_name_modifier' => $identity->general_name_modifier,
                    'birth_year' => $identity->birth_year,
                    'death_year' => $identity->death_year,
                    'note' => $identity->note,
                    'related_identity_resources' => $identity->related_identity_resources ? json_encode($identity->related_identity_resources) : null,
                    'nationality' => $identity->nationality,
                    'alternative_names' => is_array($identity->alternative_names) ? json_encode($identity->alternative_names) : $identity->alternative_names,
                    'gender' => $identity->gender,
                    'type' => $identity->type === 'institution' ? 'institution' : 'person',
                ]);

                $importCount++;

                $this->attach($identityId, $identity->profession_short, 'profession_category');
                $this->attach($identityId, $identity->profession_detailed, 'profession');
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
