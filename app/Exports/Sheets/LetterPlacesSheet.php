<?php

namespace App\Exports\Sheets;

use Generator;

class LetterPlacesSheet extends AbstractLetterSheet
{
    public function title(): string
    {
        return 'Places';
    }

    public function headings(): array
    {
        return [
            'Letter ID', 'Role', 'Position', 'Scope', 'Place ID', 'Place Reference', 'Name', 'Marked / Note',
        ];
    }

    public function generator(): Generator
    {
        foreach ($this->letters(['localPlaces', 'globalPlaces']) as $letter) {
            $rows = $letter->localPlaces
                ->map(fn ($place) => ['scope' => 'local', 'place' => $place])
                ->concat($letter->globalPlaces->map(
                    fn ($place) => ['scope' => 'global', 'place' => $place]
                ))
                ->sortBy(fn ($row) => sprintf(
                    '%020d-%020d',
                    $row['place']->pivot->position ?? PHP_INT_MAX,
                    $row['place']->id
                ));

            foreach ($rows as $row) {
                $place = $row['place'];
                $scope = $row['scope'];

                yield [
                    $letter->id,
                    $place->pivot->role,
                    $place->pivot->position,
                    $scope,
                    $place->id,
                    $this->reference($scope, $place->id),
                    $place->name,
                    $place->pivot->marked,
                ];
            }
        }
    }
}
