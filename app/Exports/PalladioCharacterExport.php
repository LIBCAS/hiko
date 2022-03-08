<?php

namespace App\Exports;

use App\Models\Letter;
use App\Models\Identity;
use Maatwebsite\Excel\Concerns\WithMapping;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\FromCollection;

class PalladioCharacterExport implements FromCollection, WithMapping, WithHeadings
{
    public $role;
    public $mainCharacter;

    public function __construct($role)
    {
        $this->role = $role;
        $this->mainCharacter = Identity::find(config('hiko.main_character'))
            ->select('id', 'surname', 'birth_year')
            ->first();
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

        return [
            $this->getAge($this->mainCharacter->birth_year, $letter->date_year),
            $sideCharacter->name,
            $sideCharacter->gender,
            $sideCharacter->nationality,
            $this->getAge($sideCharacter->birth_year, $letter->date_year),
            'Profession (O)', // profese korespondenčního partnera
            'Profession category (O)', // kategorie profesí korespondenčního partnera
            $this->getDate($letter),
            $letter->date_year,
            $letter->date_month,
            "Place of {$this->mainCharacter->surname[0]}", // místo pobytu osobnosti
            "Place of {$this->mainCharacter->surname[0]} (coordinates)", // to samé jako výše
            'Place of O', // místo korespondenčního partnera
            'Place of O (coordinates)', // to samé jako výše
            strtolower(str_replace(';', '|', $letter->languages)),
            'Keywords',
            'Keywords categories',
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
                $subquery->select('identities.id', 'name', 'role', 'birth_year', 'gender', 'nationality')
                    ->whereIn('role', ['author', 'recipient', 'mentioned'])
                    ->orderBy('position');
            },
            'places' => function ($subquery) {
                $subquery->select('places.id', 'name', 'role', 'latitude', 'longitude')
                    ->whereIn('role', ['origin', 'destination'])
                    ->orderBy('position');
            },
            'keywords' => function ($subquery) {
                $subquery->select('keywords.id', 'name');
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

    protected function getAge($birth, $year)
    {
        return empty($birth) || empty($year)
            ? ''
            : (int) $year - (int) $birth;
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
}
