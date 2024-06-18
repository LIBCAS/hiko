<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LetterResource extends JsonResource
{
    public function toArray($request): array
    {
        $record = $request->basic === '1'
            ? $this->getBasicRecord()
            : $this->getDetailedRecord();

        if ($request->media === '1') {
            $record['media'] = $this->getPublishedMedia();
        }

        return $record;
    }

    protected function getBasicRecord(): array
    {
        $identities = $this->identities->groupBy('pivot.role');
        $places = $this->places->groupBy('pivot.role');

        return [
            'uuid' => $this->uuid,
            'dates' => [
                'date' => $this->pretty_date,
                'computed' => $this->date_computed,
            ],
            'signatures' => $this->getSignatures(),
            'authors' => $this->pluckNames($identities, 'author'),
            'recipients' => $this->pluckNames($identities, 'recipient'),
            'origins' => $this->pluckNames($places, 'origin'),
            'destinations' => $this->pluckNames($places, 'destination'),
        ];
    }

    protected function getDetailedRecord(): array
    {
        $identities = $this->identities->groupBy('pivot.role');
        $places = $this->places->groupBy('pivot.role');

        return [
            'name' => $this->name,
            'uuid' => $this->uuid,
            'dates' => $this->getDetailedDates(),
            'authors' => $this->getDetailedRoleData($identities, 'author', [
                'inferred' => $this->author_inferred,
                'uncertain' => $this->author_uncertain,
                'note' => $this->author_note,
            ]),
            'recipients' => $this->getDetailedRoleData($identities, 'recipient', [
                'inferred' => $this->recipient_inferred,
                'uncertain' => $this->recipient_uncertain,
                'note' => $this->recipient_note,
            ]),
            'origins' => $this->getDetailedRoleData($places, 'origin', [
                'inferred' => $this->origin_inferred,
                'uncertain' => $this->origin_uncertain,
                'note' => $this->origin_note,
            ]),
            'destinations' => $this->getDetailedRoleData($places, 'destination', [
                'inferred' => $this->destination_inferred,
                'uncertain' => $this->destination_uncertain,
                'note' => $this->destination_note,
            ]),
            'mentioned' => [
                'items' => $this->pluckNames($identities, 'mentioned'),
                'note' => $this->people_mentioned_note,
            ],
            'keywords' => $this->getKeywords(),
            'copies' => (array)$this->copies,
            'related_resources' => (array)$this->related_resources,
            'abstract' => $this->getTranslations('abstract'),
            'explicit' => $this->explicit,
            'incipit' => $this->incipit,
            'content' => $this->content,
            'copyright' => $this->copyright,
            'note' => $this->notes_public,
            'languages' => $this->getLanguages(),
        ];
    }

    protected function getPublishedMedia(): array
    {
        return collect($this->getMedia())->filter(function ($media) {
            return $media->getCustomProperty('status') === 'publish';
        })->map(function ($media) {
            return [
                'thumb' => route('image', [$this, $media, 'size' => 'thumb']),
                'full' => route('image', [$this, $media, 'size' => 'full']),
                'description' => $media->getCustomProperty('description'),
            ];
        })->toArray();
    }

    protected function getSignatures(): array
    {
        return collect($this->copies)->pluck('signature')->toArray();
    }

    protected function pluckNames($collection, $role): array
    {
        return isset($collection[$role])
            ? $collection[$role]->pluck('name')->toArray()
            : [];
    }

    protected function getDetailedDates(): array
    {
        return [
            'date' => $this->pretty_date,
            'date_range' => $this->date_is_range ? $this->pretty_range_date : '',
            'date_marked' => $this->date_marked,
            'date_uncertain' => (bool)$this->date_uncertain,
            'date_approximate' => (bool)$this->date_approximate,
            'date_inferred' => (bool)$this->date_inferred,
            'date_note' => $this->date_note,
        ];
    }

    protected function getDetailedRoleData($collection, $role, array $additionalData): array
    {
        return array_merge([
            'items' => isset($collection[$role])
                ? $collection[$role]->map(function ($item) {
                    return [
                        'name' => $item->name,
                        'marked' => $item->pivot->marked,
                        'salutation' => $item->pivot->salutation ?? null,
                    ];
                })->toArray()
                : [],
        ], $additionalData);
    }

    protected function getKeywords(): array
    {
        return collect($this->keywords)
            ->map(function ($kw) {
                return $kw->getTranslations('name');
            })
            ->toArray();
    }

    protected function getLanguages(): array
    {
        return collect(explode(';', $this->languages))
            ->reject(function ($language) {
                return empty($language);
            })->toArray();
    }
}
