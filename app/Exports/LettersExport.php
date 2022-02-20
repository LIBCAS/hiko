<?php

namespace App\Exports;

use App\Models\Letter;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class LettersExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Letter::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'uuid',
            'year',
            'month',
            'day',
            'date_is_range',
            'range_year',
            'range_month',
            'range_day',
            'date_as_marked',
            'date_uncertain',
            'date_approximate',
            'date_inferred',
            'date_note',
            'authors',
            'author_inferred',
            'author_uncertain',
            'author_note',
            'recipients',
            'recipient_inferred',
            'recipient_uncertain',
            'recipient_note',
            'origin',
            'origin_inferred',
            'origin_uncertain',
            'origin_note',
            'destination',
            'destination_inferred',
            'destination_uncertain',
            'destination_note',
            'mentioned',
            'mentioned_note',
            'abstract_cs',
            'abstract_en',
            'explicit',
            'incipit',
            'content',
            'copyright',
            'keywords',
            'languages',
            'private_note',
            'public_note',
            'copies',
            'related_resources',
            'status',
        ];
    }

    public function map($letter): array
    {
        $identities = $letter->identities
            ? $letter->identities
            ->sortBy('pivot.position')
            ->groupBy('pivot.role')
            ->toArray()
            : [];

        $places = $letter->places
            ? $letter->places
            ->sortBy('pivot.position')
            ->groupBy('pivot.role')
            ->toArray()
            : [];

        return [
            $letter->id,
            $letter->uuid,
            $letter->date_year,
            $letter->date_month,
            $letter->date_day,
            (bool) $letter->date_is_range,
            $letter->range_year,
            $letter->range_month,
            $letter->range_day,
            $letter->date_marked,
            (bool) $letter->date_uncertain,
            (bool) $letter->date_approximate,
            (bool) $letter->date_inferred,
            $letter->date_note,
            isset($identities['author'])
                ? collect($identities['author'])->map(function ($item) {
                    $name = $item['name'];
                    if ($item['pivot']['marked']) {
                        $name .= ", (marked as: {$item['pivot']['marked']})";
                    }
                    return $name;
                })->implode('|')
                : '',
            (bool) $letter->author_inferred,
            (bool) $letter->author_uncertain,
            $letter->author_note,
            isset($identities['recipient'])
                ? collect($identities['recipient'])->map(function ($item) {
                    $name = $item['name'];
                    if ($item['pivot']['marked'] || $item['pivot']['salutation']) {
                        $name .= ", (marked as: {$item['pivot']['marked']}, salutation: {$item['pivot']['salutation']})";
                    }
                    return $name;
                })->implode('|')
                : '',
            (bool) $letter->recipient_inferred,
            (bool) $letter->recipient_uncertain,
            $letter->recipient_note,
            isset($places['origin'])
                ? collect($places['origin'])->map(function ($item) {
                    $name = $item['name'];
                    if ($item['pivot']['marked']) {
                        $name .= ", (marked as: {$item['pivot']['marked']})";
                    }
                    return $name;
                })->implode('|')
                : '',
            (bool) $letter->origin_inferred,
            (bool) $letter->origin_uncertain,
            $letter->origin_note,
            isset($places['destination'])
                ? collect($places['destination'])->map(function ($item) {
                    $name = $item['name'];
                    if ($item['pivot']['marked']) {
                        $name .= ", (marked as: {$item['pivot']['marked']})";
                    }
                    return $name;
                })->implode('|')
                : '',
            (bool) $letter->destination_inferred,
            (bool) $letter->destination_uncertain,
            $letter->destination_note,
            isset($identities['mentioned'])
                ? collect($identities['mentioned'])->pluck('name')->implode('|')
                : '',
            $letter->people_mentioned_note,
            $letter->getTranslation('abstract', 'cs'),
            $letter->getTranslation('abstract', 'en'),
            $letter->explicit,
            $letter->incipit,
            $letter->content,
            $letter->copyright,
            collect($letter->keywords)
                ->map(function ($kw) {
                    return $kw->getTranslation('name', config('hiko.metadata_default_locale'));
                })
                ->implode('|'),
            collect(explode(';', $letter->languages))
                ->reject(function ($language) {
                    return empty($language);
                })->implode('|'),
            $letter->notes_private,
            $letter->notes_public,
            $letter->copies,
            $letter->related_resources,
            $letter->status,
        ];
    }
}
