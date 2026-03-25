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
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'dates' => [
                'date' => $this->pretty_date,
                'computed' => $this->date_computed,
            ],
            'signatures' => $this->getSignatures(),
            'authors' => $this->pluckRoleNames($this->collectIdentityRoleItems('author')),
            'recipients' => $this->pluckRoleNames($this->collectIdentityRoleItems('recipient')),
            'origins' => $this->pluckRoleNames($this->collectPlaceRoleItems('origin')),
            'destinations' => $this->pluckRoleNames($this->collectPlaceRoleItems('destination')),
        ];
    }

    protected function getDetailedRecord(): array
    {
        $letterAttrs = $this->getAttributes();

        return [
            'id' => $this->id,
            'name' => $this->name,
            'uuid' => $this->uuid,
            'dates' => $this->getDetailedDates(),
            'date_year' => $this->date_year,
            'date_month' => $this->date_month,
            'date_day' => $this->date_day,
            'date_marked' => $this->date_marked,
            'date_uncertain' => $this->date_uncertain,
            'date_approximate' => $this->date_approximate,
            'date_inferred' => $this->date_inferred,
            'date_is_range' => $this->date_is_range,
            'date_note' => $this->date_note,
            'range_year' => $this->range_year,
            'range_month' => $this->range_month,
            'range_day' => $this->range_day,
            'authors' => $this->collectIdentityRoleItems('author'),
            'author_inferred' => $this->author_inferred,
            'author_uncertain' => $this->author_uncertain,
            'author_note' => $this->author_note,
            'recipients' => $this->collectIdentityRoleItems('recipient'),
            'recipient_inferred' => $this->recipient_inferred,
            'recipient_uncertain' => $this->recipient_uncertain,
            'recipient_note' => $this->recipient_note,
            'origins' => $this->collectPlaceRoleItems('origin'),
            'origin_inferred' => $this->origin_inferred,
            'origin_uncertain' => $this->origin_uncertain,
            'origin_note' => $this->origin_note,
            'destinations' => $this->collectPlaceRoleItems('destination'),
            'destination_inferred' => $this->destination_inferred,
            'destination_uncertain' => $this->destination_uncertain,
            'destination_note' => $this->destination_note,
            'mentioned' => $this->collectIdentityRoleItems('mentioned'),
            'people_mentioned_note' => $this->people_mentioned_note,
            'keywords' => collect($this->localKeywords)->map(function ($keyword) {
                $names = json_decode($keyword->getAttributes()['name'], true) ?: [];
                $id = (int) $keyword->id;

                return [
                    'id' => $id,
                    'scope' => 'local',
                    'reference' => "local-{$id}",
                    'name_cs' => $names['cs'] ?? '',
                    'name_en' => $names['en'] ?? '',
                    'type' => 'L.',
                ];
            })->merge(
                collect($this->globalKeywords)->map(function ($keyword) {
                    $names = json_decode($keyword->getAttributes()['name'], true) ?: [];
                    $id = (int) $keyword->id;

                    return [
                        'id' => $id,
                        'scope' => 'global',
                        'reference' => "global-{$id}",
                        'name_cs' => $names['cs'] ?? '',
                        'name_en' => $names['en'] ?? '',
                        'type' => 'G.',
                    ];
                })
            )->values(),
            'copies' => $this->copies,
            'related_resources' => json_decode($letterAttrs['related_resources'], true) ?: [],
            'abstract' => json_decode($letterAttrs['abstract'], true) ?: [],
            'incipit' => $this->incipit,
            'explicit' => $this->explicit,
            'languages' => explode(';', $this->languages),
            'note' => $this->notes_public,
            'content' => $this->content,
            'copyright' => $this->copyright,
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

    protected function pluckRoleNames(array $items): array
    {
        return array_values(array_map(fn ($item) => $item['name'] ?? '', $items));
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

    protected function mapRoleItems($collection, string $scope): array
    {
        return collect($collection)->map(function ($item) use ($scope) {
            $id = (int) $item->id;

            return [
                'id' => $id,
                'scope' => $scope,
                'reference' => "{$scope}-{$id}",
                'name' => $item->name,
                'marked' => $item->pivot->marked ?? null,
                'salutation' => $item->pivot->salutation ?? null,
            ];
        })->values()->toArray();
    }

    protected function collectIdentityRoleItems(string $role): array
    {
        $local = match ($role) {
            'author' => $this->authors,
            'recipient' => $this->recipients,
            'mentioned' => $this->mentioned,
            default => collect(),
        };
        $global = match ($role) {
            'author' => $this->globalAuthors,
            'recipient' => $this->globalRecipients,
            'mentioned' => $this->globalMentioned,
            default => collect(),
        };

        return array_values(array_merge(
            $this->mapRoleItems($local, 'local'),
            $this->mapRoleItems($global, 'global')
        ));
    }

    protected function collectPlaceRoleItems(string $role): array
    {
        $local = $role === 'origin' ? $this->origins : $this->destinations;
        $global = $role === 'origin' ? $this->globalOrigins : $this->globalDestinations;

        return array_values(array_merge(
            $this->mapRoleItems($local, 'local'),
            $this->mapRoleItems($global, 'global')
        ));
    }
}
