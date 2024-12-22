<?php

namespace App\Http\Resources;

use Illuminate\Http\Resources\Json\JsonResource;

class LetterResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return array
     */
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

    /**
     * Get the basic record structure.
     *
     * @return array
     */
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

    /**
     * Get the detailed record structure.
     *
     * @return array
     */
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
            'metadata' => [
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
                'date_note' => $this->date_note,
                'author_inferred' => $this->author_inferred,
                'author_uncertain' => $this->author_uncertain,
                'author_note' => $this->author_note,
                'recipient_inferred' => $this->recipient_inferred,
                'recipient_uncertain' => $this->recipient_uncertain,
                'recipient_note' => $this->recipient_note,
                'origin_inferred' => $this->origin_inferred,
                'origin_uncertain' => $this->origin_uncertain,
                'origin_note' => $this->origin_note,
                'destination_inferred' => $this->destination_inferred,
                'destination_uncertain' => $this->destination_uncertain,
                'destination_note' => $this->destination_note,
                'language_detected' => $this->language_detected,
                'full_text' => $this->full_text,
                // Add other metadata fields as needed
            ],
        ];
    }

    /**
     * Get the published media.
     *
     * @return array
     */
    protected function getPublishedMedia(): array
    {
        if (!tenant()) {
            return [];
        }

        // getMedia() already returns a collection of Media from the correct table
        $mediaCollection = $this->getMedia()->filter(function ($media) {
            return $media->getCustomProperty('status') === 'publish';
        });

        return $mediaCollection->map(function ($media) {
            return [
                'thumb' => route('image', [$this, $media, 'size' => 'thumb']),
                'full' => route('image', [$this, $media, 'size' => 'full']),
                'description' => $media->getCustomProperty('description'),
            ];
        })->toArray();
    }

    /**
     * Get the signatures from copies.
     *
     * @return array
     */
    protected function getSignatures(): array
    {
        return collect($this->copies)->pluck('signature')->toArray();
    }

    /**
     * Pluck names based on role from a collection.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $role
     * @return array
     */
    protected function pluckNames($collection, $role): array
    {
        return isset($collection[$role])
            ? $collection[$role]->pluck('name')->toArray()
            : [];
    }

    /**
     * Get detailed dates.
     *
     * @return array
     */
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

    /**
     * Get detailed role data with additional metadata.
     *
     * @param \Illuminate\Support\Collection $collection
     * @param string $role
     * @param array $additionalData
     * @return array
     */
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

    /**
     * Get keywords with translations.
     *
     * @return array
     */
    protected function getKeywords(): array
    {
        return collect($this->keywords)
            ->map(function ($kw) {
                return $kw->getTranslations('name');
            })
            ->toArray();
    }

    /**
     * Get languages as an array.
     *
     * @return array
     */
    protected function getLanguages(): array
    {
        return collect(explode(';', $this->languages))
            ->reject(function ($language) {
                return empty($language);
            })->toArray();
    }
}
