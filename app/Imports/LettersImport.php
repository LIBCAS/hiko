<?php

namespace App\Imports;

use App\Models\User;
use App\Models\Letter;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class LettersImport
{
    /**
     * @throws FileNotFoundException
     */
    public function import($prefix): string
    {
        if (!Storage::disk('local')->exists('imports/letter.json')) {
            return 'Soubor neexistuje';
        }

        $json = Storage::disk('local')->get('imports/letter.json');
        $letters = json_decode($json);

        if (!$letters) {
            return 'Chyba při dekódování JSON';
        }

        $tenantId = DB::table('tenants')->where('table_prefix', $prefix)->value('id');

        if (!$tenantId) {
            return 'Tenant s prefixem "' . $prefix . '" neexistuje';
        } else {
            tenancy()->initialize($tenantId);
        }

        $importCount = 0;
        $users = User::select('name', 'id')->get();
        $lastId = DB::table('letters')->max('id');

        foreach ($letters as $letter) {
            try {
                $lastId++;
                $letterId = DB::table('letters')->insertGetId([
                    'created_at' => $letter->created_at === '0000-00-00 00:00:00' ? now() : $letter->created_at,
                    'updated_at' => $letter->updated_at === '0000-00-00 00:00:00' ? now() : $letter->updated_at,
                    'date_year' => $letter->date_year,
                    'date_month' => $letter->date_month,
                    'date_day' => $letter->date_day,
                    'date_marked' => $letter->date_marked,
                    'date_uncertain' => $letter->date_uncertain,
                    'date_approximate' => $letter->date_approximate,
                    'date_inferred' => $letter->date_inferred,
                    'date_is_range' => $letter->date_is_range,
                    'date_note' => $letter->date_note,
                    'range_year' => $letter->range_year,
                    'range_month' => $letter->range_month,
                    'range_day' => $letter->range_day,
                    'author_inferred' => $letter->author_inferred,
                    'author_uncertain' => $letter->author_uncertain,
                    'author_note' => $letter->author_note,
                    'recipient_inferred' => $letter->recipient_inferred,
                    'recipient_uncertain' => $letter->recipient_uncertain,
                    'recipient_note' => $letter->recipient_notes,
                    'destination_inferred' => $letter->dest_inferred,
                    'destination_uncertain' => $letter->dest_uncertain,
                    'destination_note' => $letter->dest_note,
                    'origin_inferred' => $letter->origin_inferred,
                    'origin_uncertain' => $letter->origin_uncertain,
                    'origin_note' => $letter->origin_note,
                    'people_mentioned_note' => $letter->people_mentioned_notes,
                    'copies' => $letter->copies ? json_encode($letter->copies) : null,
                    'related_resources' => $letter->related_resources ? json_encode($letter->related_resources) : null,
                    'abstract' => json_encode([
                        'en' => $letter->abstract->en ?? '',
                        'cs' => $letter->abstract->cs ?? '',
                    ]),
                    'explicit' => $letter->explicit,
                    'incipit' => $letter->incipit,
                    'history' => $letter->history,
                    'copyright' => $letter->copyright,
                    'languages' => collect(explode(';', $letter->languages))
                        ->map(function ($language) {
                            return ucfirst($language);
                        })
                        ->implode(';'),
                    'notes_private' => $letter->notes_private,
                    'notes_public' => $letter->notes_public,
                    'status' => $letter->status === 'publish' ? 'publish' : 'draft',
                    'uuid' => Str::uuid(),
                    'date_computed' => computeDate($letter),
                    ]);

                    $importCount++;

                    $this->attachKeywords($letter->keywords, $letterId);
                    $this->attachIdentities($letter->l_author, $letter->authors_meta, 'author', $letterId);
                    $this->attachIdentities($letter->recipient, $letter->authors_meta, 'recipient', $letterId);
                    $this->attachPlaces($letter->places_meta, $letterId);
                    $this->attachMentioned($letter->people_mentioned, $letterId);
                    $this->attachusers($letter->history, $letterId, $users);

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

    protected function attachIdentities($identities, $meta, $role, $letterId) {
        collect($meta)->each(function ($identity) use ($identities, $letterId, $role) {
            if (is_object($identity) && isset($identity->id)) {
                if ($identity->id == $identities) {
                    try {
                        DB::table('identity_letter')->insert([
                            'identity_id' => $identity->id,
                            'letter_id' => $letterId,
                            'role' => $role,
                            'position' => 0,
                            'marked' => $identity->marked ?? '',
                            'salutation' => $identity->salutation ?? '',
                        ]);
                    } catch (\Illuminate\Database\QueryException $ex) {
                        dump($ex->getMessage());
                    }
                }
            }
        });
    }

    protected function attachPlaces($meta, $letterId)
    {
        collect($meta)
            ->each(function ($place, $index) use ($letterId) {
                try {
                    if (isset($place->type)) {
                        DB::table('letter_place')->insert([
                            'place_id' => $place->id,
                            'letter_id' => $letterId,
                            'role' => $place->type,
                            'position' => $index,
                            'marked' => $place->marked ?? '',
                        ]);
                    }
                } catch (\Illuminate\Database\QueryException $ex) {
                    dump($ex->getMessage());
                }
            });
    }

    protected function attachKeywords($keywords, $letterId)
    {
        collect($keywords)->each(function ($keyword) use ($letterId) {
            try {
                DB::table('keyword_letter')->insert([
                    'keyword_id' => $keyword,
                    'letter_id' => $letterId,
                ]);
            } catch (\Illuminate\Database\QueryException $ex) {
                dump($ex->getMessage());
            }
        });
    }

    protected function attachMentioned($mentioned, $letterId)
    {
        collect($mentioned)
            ->each(function ($identity, $index) use ($letterId) {

                try {
                    DB::table('identity_letter')->insert([
                        'identity_id' => $identity,
                        'letter_id' => $letterId,
                        'role' => 'mentioned',
                        'position' => $index,
                    ]);
                } catch (\Illuminate\Database\QueryException $ex) {
                    dump($ex->getMessage());
                }
            });
    }

    protected function attachUsers($history, $letterId, $users)
    {
        collect(preg_split('/\r\n|\r|\n/', $history))
            ->each(function ($line) use ($letterId, $users) {
                $data = explode(' – ', $line);

                if (isset($data[1])) {
                    $user = $users->where('name', trim($data[1]))->first();

                    if ($user) {
                        Letter::find($letterId)->users()->syncWithoutDetaching($user->id);
                    }
                }
            });
    }
}
