<?php

namespace App\Exports;

use App\Models\GlobalIdentity;
use App\Models\Identity;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class IdentitiesExport implements FromCollection, WithMapping, WithHeadings
{
    public function collection()
    {
        // Get Local Identities
        $localIdentities = Identity::with([
            'professions',
            'globalProfessions',
            'profession_categories'
        ])->get()->map(function ($identity) {
            $identity->source_type = 'Local';
            return $identity;
        });

        // Get Global Identities
        $globalIdentities = GlobalIdentity::with([
            'professions', // Note: GlobalIdentity model uses 'professions' for global_identity_profession
        ])->get()->map(function ($identity) {
            $identity->source_type = 'Global';
            // Polyfill globalProfessions property to match local structure for mapping below
            $identity->globalProfessions = $identity->professions;
            return $identity;
        });

        // Merge
        return $localIdentities->merge($globalIdentities);
    }

    public function headings(): array
    {
        return [
            'id',
            'name',
            'type',
            'surname',
            'forename',
            'related_names',
            'nationality',
            'gender',
            'birth_year',
            'death_year',
            'professions',
            'categories',
            'viaf_id',
            'note',
            'source'
        ];
    }

    public function map($identity): array
    {
        $professions = [];

        // Handle Local Professions (only exists on Local Identity)
        if ($identity->source_type === 'Local' && $identity->professions) {
            foreach ($identity->professions as $profession) {
                $name = $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                $professions[] = "{$name} (Local)";
            }
        }

        // Handle Global Professions (exists on both Local and Global Identity)
        if ($identity->globalProfessions) {
            foreach ($identity->globalProfessions as $profession) {
                $name = $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                $professions[] = "{$name} (Global)";
            }
        }

        // Handle Categories (Local only has access to profession_categories via relationship)
        $categories = [];
        if ($identity->source_type === 'Local' && $identity->profession_categories) {
            foreach ($identity->profession_categories as $category) {
                $categories[] = $category->getTranslation('name', config('hiko.metadata_default_locale'));
            }
        }

        return [
            $identity->id,
            $identity->name,
            $identity->type,
            $identity->surname,
            $identity->forename,
            $this->formatRelatedNames($identity->related_names),
            $identity->nationality,
            $identity->gender,
            $identity->birth_year,
            $identity->death_year,
            implode('|', $professions),
            implode('|', $categories),
            $identity->viaf_id,
            $identity->note,
            $identity->source_type,
        ];
    }

    protected function formatRelatedNames($relatedNames): string
    {
        if (is_null($relatedNames)) {
            return '';
        }

        $relatedNamesArray = is_array($relatedNames) ? $relatedNames : json_decode($relatedNames, true);

        if (json_last_error() !== JSON_ERROR_NONE || !is_array($relatedNamesArray)) {
            return '';
        }

        return implode(' | ', array_map(fn($name) =>
            trim("{$name['surname']} {$name['forename']} {$name['general_name_modifier']}"),
            $relatedNamesArray
        ));
    }
}
