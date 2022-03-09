<?php

namespace App\Exports;

use App\Models\Letter;
use App\Models\Identity;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Concerns\Exportable;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Illuminate\Contracts\Support\Responsable;
use Maatwebsite\Excel\Concerns\FromCollection;

class PalladioCharacterExport implements FromCollection, WithMapping, WithHeadings, Responsable
{
    use Exportable;

    public $role;
    public $mainCharacter;

    private $fileName;
    private $headers = [
        'Content-Type' => 'text/csv',
    ];

    public function __construct($role)
    {
        $this->role = $role;
        $this->mainCharacter = Identity::where('id', '=', config('hiko.main_character'))
            ->select('id', 'surname', 'birth_year')
            ->first();
        $this->fileName = 'palladio-' . Str::slug($this->mainCharacter->surname) . "-{$this->role}.csv";
    }

    public function headings(): array
    {
        return [
            "Age ({$this->mainCharacter->surname[0]})", // věk osobnosti u přijaté i odeslané korespondence
            'Name (O)', // jméno korespondenčního partnera
            'Gender (O)', // pohlaví korespondenčního partnera
            'Nationality (O)', // národnost korespondenčního partnera
            'Age (O)', // věk korespondenčního partnera
            'Profession (O)', // profese korespondenčního partnera
            'Profession category (O)', // kategorie profesí korespondenčního partnera
            'Date of dispatch', // datum odeslání dopisu
            'Year of dispatch', // rok odeslání dopisu
            'Month of dispatch', // měsíc odeslání dopisu
            "Place of {$this->mainCharacter->surname[0]}", // místo pobytu osobnosti
            "Place of {$this->mainCharacter->surname[0]} (coordinates)", // to samé jako výše
            'Place of O', // místo korespondenčního partnera
            'Place of O (coordinates)', // to samé jako výše
            'Languages',
            'Keywords',
            'Keywords categories',
            'People mentioned',
            'Document type',
            'Preservation',
            'Repository',
            'Archive',
            'Collection',
            'Signature',
            'Type of copy',
            'Received/Sent',
        ];
    }

    public function map($letter): array
    {
        $sideCharacter = $this->role === 'author'
            ? $letter->identities->where('role', '=', 'recipient')->first()
            : $letter->identities->where('role', '=', 'author')->first();

        $mainCharacterPlace = $this->role === 'author'
            ? $letter->places->where('role', '=', 'origin')->first()
            : $letter->places->where('role', '=', 'destination')->first();

        $sideCharacterPlace = $this->role === 'author'
            ? $letter->places->where('role', '=', 'destination')->first()
            : $letter->places->where('role', '=', 'origin')->first();

        return [
            $this->getAge($this->mainCharacter, $letter->date_year),
            !empty($sideCharacter) ? $sideCharacter->name : '',
            !empty($sideCharacter) ? $sideCharacter->gender : '',
            !empty($sideCharacter) ? $sideCharacter->nationality : '',
            $this->getAge($sideCharacter, $letter->date_year),
            !empty($sideCharacter)
                ? $sideCharacter->professions->map(function ($profession) {
                    return $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                })->implode('|')
                : '',
            !empty($sideCharacter)
                ? $sideCharacter->profession_categories->map(function ($profession) {
                    return $profession->getTranslation('name', config('hiko.metadata_default_locale'));
                })->implode('|')
                : '',
            $this->getDate($letter),
            $letter->date_year,
            $letter->date_month,
            $mainCharacterPlace->name,
            $this->getLatLong($mainCharacterPlace),
            $sideCharacterPlace->name,
            $this->getLatLong($sideCharacterPlace),
            strtolower(str_replace(';', '|', $letter->languages)),
            $letter->keywords->map(function ($kw) {
                return $kw->getTranslation('name', config('hiko.metadata_default_locale'));
            })->implode('|'),
            $letter->keywords->map(function ($kw) {
                return $kw->keyword_category->getTranslation('name', config('hiko.metadata_default_locale'));
            })->implode('|'),
            collect($letter->identities->where('role', '=', 'mentioned')->pluck('name')->implode('|'))->toArray()[0],
            !empty($letter->copies) ? $letter->copies[0]['type'] : '',
            !empty($letter->copies) ? $letter->copies[0]['preservation'] : '',
            !empty($letter->copies) ? $letter->copies[0]['repository'] : '',
            !empty($letter->copies) ? $letter->copies[0]['archive'] : '',
            !empty($letter->copies) ? $letter->copies[0]['collection'] : '',
            !empty($letter->copies) ? $letter->copies[0]['signature'] : '',
            !empty($letter->copies) ? $letter->copies[0]['copy'] : '',
            $this->role === 'author' ? 'Sent' : 'Received',
        ];
    }

    public function collection()
    {
        return Letter::with([
            'identities' => function ($subquery) {
                $subquery->with('professions', 'profession_categories')
                    ->select('identities.id', 'name', 'role', 'birth_year', 'gender', 'nationality')
                    ->whereIn('role', ['author', 'recipient', 'mentioned'])
                    ->orderBy('position');
            },
            'places' => function ($subquery) {
                $subquery->select('places.id', 'name', 'role', 'latitude', 'longitude')
                    ->whereIn('role', ['origin', 'destination'])
                    ->orderBy('position');
            },
            'keywords' => function ($subquery) {
                $subquery->with('keyword_category')
                    ->select('keywords.id', 'keyword_category_id', 'name');
            },
        ])
            ->whereHas('identities', function ($query) {
                $query
                    ->where('identities.id', '=', $this->mainCharacter->id)
                    ->where('role', '=', $this->role);
            })
            ->select('id', 'date_year', 'date_month', 'date_day', 'copies', 'languages')
            ->get();
    }

    protected function getAge($person, $year)
    {
        return empty($person) || empty($year)
            ? ''
            : (int) $year - (int) $person->birth_year;
    }

    protected function getDate($letter)
    {
        $date = !empty($letter->date_year)
            ? $letter->date_year
            : '';

        if (!empty($letter->date_month) && !empty($letter->date_day)) {
            $date .= '-' . $letter->date_month . '-' . $letter->date_day;
        }

        return $date;
    }

    protected function getLatLong($place)
    {
        return empty($place->latitude) && empty($place->longitude)
            ? ''
            : "{$place->latitude}, {$place->longitude}";
    }
}
