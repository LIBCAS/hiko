<?php

namespace App\Exports;

use App\Models\Identity;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class IdentitiesExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        return Identity::all();
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'type',
            'surname',
            'forename',
            'alternative_names',
            'nationality',
            'gender',
            'birth_year',
            'death_year',
            'professions',
            'categories',
            'viaf_id',
            'note',
        ];
    }

    public function map($identity): array
    {
        if ($identity->professions) {
            $professions = $identity->professions
                ->sortBy('pivot.position')
                ->map(function ($profession) {
                    return implode('-', array_values($profession->getTranslations('name')));
                })
                ->values()
                ->toArray();
        }

        if ($identity->profession_categories) {
            $categories = $identity->profession_categories
                ->sortBy('pivot.position')
                ->map(function ($profession) {
                    return implode('-', array_values($profession->getTranslations('name')));
                })
                ->values()
                ->toArray();
        }

        return [
            $identity->id,
            $identity->type,
            $identity->name,
            $identity->surname,
            $identity->forename,
            $identity->alternative_names ? implode('|', $identity->alternative_names) : [],
            $identity->nationality,
            $identity->gender,
            $identity->birth_year,
            $identity->death_year,
            isset($professions) ? implode('|', $professions) : '',
            isset($categories) ? implode('|', $categories) : '',
            $identity->viaf_id,
            $identity->note,
        ];
    }
}
