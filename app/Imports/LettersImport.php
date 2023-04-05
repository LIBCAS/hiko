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
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/letter.json')) {
            return 'Soubor neexistuje';
        }

        $users = User::select('name', 'id')->get();

        collect(json_decode(Storage::disk('local')->get('imports/letter.json')))
            ->each(function ($letter) use ($users) {
                DB::table('letters')
                    ->insert([
                        'id' => $letter->id,
                        'created_at' => $letter->created === '0000-00-00 00:00:00' ? now() : $letter->created,
                        'updated_at' => $letter->modified === '0000-00-00 00:00:00' ? now() : $letter->modified,
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
                        'copies' => $letter->copies ?: null,
                        'related_resources' => $letter->related_resources ?: null,
                        'abstract' => json_encode([
                            'en' => $letter->abstract ?: '',
                            'cs' => '',
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


                $this->attachKeywords($letter->keywords, $letter->id);
                $this->attachIdentities($letter->l_author, $letter->authors_meta, 'author', $letter->id);
                $this->attachIdentities($letter->recipient, $letter->authors_meta, 'recipient', $letter->id);
                $this->attachPlaces($letter->places_meta, $letter->id);
                $this->attachMentioned($letter->people_mentioned, $letter->id);
                $this->attachusers($letter->history, $letter->id, $users);
            });

        return 'Import dopisů byl úspěšný';
    }

    protected function attachIdentities($identities, $meta, $role, $letterId)
    {
        collect(json_decode($meta))
            ->each(function ($identity) use ($identities, $letterId, $role) {
                $index = array_search($identity->id, $identities);
                if ($index !== false) {
                    try {
                        DB::table('identity_letter')->insert([
                            'identity_id' => $identity->id,
                            'letter_id' => $letterId,
                            'role' => $role,
                            'position' => array_search($identity->id, $identities),
                            'marked' => $identity->marked ?? '',
                            'salutation' => $identity->salutation ?? '',
                        ]);
                    } catch (\Illuminate\Database\QueryException $ex) {
                        dump($ex->getMessage());
                    }
                }
            });
    }

    protected function attachPlaces($meta, $letterId)
    {
        collect(json_decode($meta))
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
