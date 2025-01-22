<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LetterResource extends JsonResource
{
    public function toArray($request): array
    {
        $record = $request->input('basic') === '1'
            ? $this->getBasicRecord()
            : $this->getDetailedRecord();

        if ($request->input('media') === '1') {
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
            'authors' => $this->getRoleData($identities, 'author', [
                'inferred' => $this->author_inferred,
                'uncertain' => $this->author_uncertain,
                'note' => $this->author_note,
            ]),
            'recipients' => $this->getRoleData($identities, 'recipient', [
                'inferred' => $this->recipient_inferred,
                'uncertain' => $this->recipient_uncertain,
                'note' => $this->recipient_note,
            ]),
            'origins' => $this->getRoleData($places, 'origin', [
                'inferred' => $this->origin_inferred,
                'uncertain' => $this->origin_uncertain,
                'note' => $this->origin_note,
            ]),
            'destinations' => $this->getRoleData($places, 'destination', [
                'inferred' => $this->destination_inferred,
                'uncertain' => $this->destination_uncertain,
                'note' => $this->destination_note,
            ]),
            'mentioned' => [
                'items' => $this->pluckNames($identities, 'mentioned'),
                'note' => $this->people_mentioned_note,
            ],
            'metadata' => $this->getMetadata(),
        ];
    }

    protected function getPublishedMedia(): array
    {
        return $this->getMedia()
            ->where('custom_properties->status', 'publish')
            ->map(fn($media) => [
                'thumb' => route('image', [$this, $media, 'size' => 'thumb']),
                'full' => route('image', [$this, $media, 'size' => 'full']),
                'description' => $media->getCustomProperty('description'),
            ])
            ->toArray();
    }

    protected function getSignatures(): array
    {
        return collect($this->copies)->pluck('signature')->toArray();
    }

    protected function pluckNames($collection, string $role): array
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
            'date_uncertain' => $this->date_uncertain,
            'date_approximate' => $this->date_approximate,
            'date_inferred' => $this->date_inferred,
            'date_note' => $this->date_note,
        ];
    }

    protected function getRoleData($collection, string $role, array $additionalData): array
    {
        return array_merge([
            'items' => isset($collection[$role])
                ? $collection[$role]->map(fn($item) => [
                    'name' => $item->name,
                    'marked' => $item->pivot->marked,
                    'salutation' => $item->pivot->salutation ?? null,
                ])->toArray()
                : [],
        ], $additionalData);
    }

    protected function getMetadata(): array
    {
        return [
            'date_year' => $this->date_year,
            'date_month' => $this->date_month,
            'date_day' => $this->date_day,
            'date_marked' => $this->date_marked,
            'date_uncertain' => $this->date_uncertain,
            'date_approximate' => $this->date_approximate,
            'date_inferred' => $this->date_inferred,
            'date_is_range' => $this->date_is_range,
            'range_year' => $this->range_year,
            'range_month' => $this->range_month,
            'range_day' => $this->range_day,
            'author_inferred' => $this->author_inferred,
            'author_uncertain' => $this->author_uncertain,
        ];
    }
}
