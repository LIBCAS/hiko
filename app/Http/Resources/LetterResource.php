<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LetterResource extends JsonResource
{
    public function toArray($request)
    {
        $record = $request->basic === '1'
            ? $this->getBasicRecord()
            : $this->getDetailedRecord();

        if ($request->media === '1') {
            foreach ($this->getMedia() as $media) {
                if ($media->getCustomProperty('status') === 'publish') {
                    $record['media'][] = [
                        'thumb' => route('image', [$this, $media, 'size' => 'thumb']),
                        'full' => route('image', [$this, $media, 'size' => 'full']),
                        'description' => $media->getCustomProperty('description'),
                    ];
                }
            }
        }

        return $record;
    }

    protected function getBasicRecord()
    {
        $identities = $this->identities->groupBy('pivot.role');
        $places = $this->places->groupBy('pivot.role');

        return [
            'uuid' => $this->uuid,
            'dates' => [
                'date' => $this->pretty_date,
                'computed' => $this->date_computed,
            ],
            'authors' => isset($identities['author'])
                ? $identities['author']->pluck('name')->toArray()
                : [],
            'recipients' => isset($identities['recipient'])
                ? $identities['recipient']->pluck('name')->toArray()
                : [],
            'origins' => isset($places['origin'])
                ? $places['origin']->pluck('name')->toArray()
                : [],
            'destinations' => isset($places['destination'])
                ? $places['destination']->pluck('name')->toArray()
                : [],
        ];
    }

    protected function getDetailedRecord()
    {
        $identities = $this->identities->groupBy('pivot.role');
        $places = $this->places->groupBy('pivot.role');

        return [
            'name' => $this->name,
            'uuid' => $this->uuid,
            'dates' => [
                'date' => $this->pretty_date,
                'date_range' => $this->date_is_range ? $this->pretty_range_date : '',
                'date_marked' => $this->date_marked,
                'date_uncertain' => (bool) $this->date_uncertain,
                'date_approximate' => (bool) $this->date_approximate,
                'date_inferred' => (bool) $this->date_inferred,
                'date_note' => $this->date_note,
            ],
            'authors' => [
                'items' => isset($identities['author'])
                    ? $identities['author']->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'marked' => $item->pivot->marked,
                        ];
                    })->toArray()
                    : [],
                'inferred' => (bool) $this->author_inferred,
                'uncertain' => (bool) $this->author_uncertain,
                'note' => $this->author_note,
            ],
            'recipients' => [
                'items' => isset($identities['recipient'])
                    ? $identities['recipient']->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'marked' => $item->pivot->marked,
                            'salutation' => $item->pivot->salutation,
                        ];
                    })->toArray()
                    : [],
                'inferred' => (bool) $this->recipient_inferred,
                'uncertain' => (bool) $this->recipient_uncertain,
                'note' => $this->recipient_note,
            ],
            'origins' => [
                'items' => isset($places['origin'])
                    ? $places['origin']->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'marked' => $item->pivot->marked,
                        ];
                    })->toArray()
                    : [],
                'inferred' => (bool) $this->origin_inferred,
                'uncertain' => (bool) $this->origin_uncertain,
                'note' => $this->origin_note,
            ],
            'destinations' => [
                'items' => isset($places['destination'])
                    ? $places['destination']->map(function ($item) {
                        return [
                            'name' => $item->name,
                            'marked' => $item->pivot->marked,
                        ];
                    })->toArray()
                    : [],
                'inferred' => (bool) $this->destination_inferred,
                'uncertain' => (bool) $this->destination_uncertain,
                'note' => $this->destination_note,
            ],
            'mentioned' => [
                'items' => isset($identities['mentioned'])
                    ? $identities['mentioned']->pluck('name')->toArray()
                    : [],
                'note' => $this->people_mentioned_note,
            ],
            'keywords' => collect($this->keywords)
                ->map(function ($kw) {
                    return $kw->getTranslations('name');
                })
                ->toArray(),
            'copies' => (array) $this->copies,
            'related_resources' => (array) $this->related_resources,
            'abstract' => $this->getTranslations('abstract'),
            'explicit' => $this->explicit,
            'incipit' => $this->incipit,
            'content' => $this->content,
            'copyright' => $this->copyright,
            'note' => $this->notes_public,
            'languages' => collect(explode(';', $this->languages))
                ->reject(function ($language) {
                    return empty($language);
                })->toArray(),
        ];
    }
}
