<?php

namespace App\Exports\Sheets;

use Generator;

class LetterIdentitiesSheet extends AbstractLetterSheet
{
    public function title(): string
    {
        return 'Identities';
    }

    public function headings(): array
    {
        return [
            'Letter ID', 'Role', 'Position', 'Scope', 'Identity ID', 'Identity Reference',
            'Name', 'Type', 'Birth Year', 'Death Year', 'Marked / Note', 'Salutation',
            'Linked Global Identity ID', 'Linked Global Identity Reference', 'Linked Global Identity Name',
        ];
    }

    public function generator(): Generator
    {
        foreach ($this->letters(['localIdentities.globalIdentity', 'globalIdentities']) as $letter) {
            $rows = $letter->localIdentities
                ->map(fn ($identity) => ['scope' => 'local', 'identity' => $identity])
                ->concat($letter->globalIdentities->map(
                    fn ($identity) => ['scope' => 'global', 'identity' => $identity]
                ))
                ->sortBy(fn ($row) => sprintf(
                    '%020d-%020d',
                    $row['identity']->pivot->position ?? PHP_INT_MAX,
                    $row['identity']->id
                ));

            foreach ($rows as $row) {
                $identity = $row['identity'];
                $scope = $row['scope'];
                $linkedGlobal = $scope === 'local' ? $identity->globalIdentity : null;

                yield [
                    $letter->id,
                    $identity->pivot->role,
                    $identity->pivot->position,
                    $scope,
                    $identity->id,
                    $this->reference($scope, $identity->id),
                    $identity->name,
                    $identity->type,
                    $identity->birth_year,
                    $identity->death_year,
                    $identity->pivot->marked,
                    $identity->pivot->salutation,
                    $linkedGlobal?->id,
                    $linkedGlobal ? $this->reference('global', $linkedGlobal->id) : '',
                    $linkedGlobal?->name,
                ];
            }
        }
    }
}
