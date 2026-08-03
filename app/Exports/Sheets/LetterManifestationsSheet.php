<?php

namespace App\Exports\Sheets;

use Generator;
use Maatwebsite\Excel\Concerns\WithColumnWidths;

class LetterManifestationsSheet extends AbstractLetterSheet implements WithColumnWidths
{
    public function title(): string
    {
        return 'Manifestations';
    }

    public function headings(): array
    {
        return [
            'Letter ID', 'Manifestation ID', 'Signature', 'Document Type', 'Preservation',
            'Mode of Production', 'Letter Number', 'Manifestation Note', 'Location Note',
            'Repository Scope', 'Repository ID', 'Repository Reference', 'Repository Name',
            'Archive Scope', 'Archive ID', 'Archive Reference', 'Archive Name',
            'Collection Scope', 'Collection ID', 'Collection Reference', 'Collection Name',
            'Created At', 'Updated At',
        ];
    }

    public function generator(): Generator
    {
        $relations = [
            'manifestations.repository',
            'manifestations.archive',
            'manifestations.collection',
            'manifestations.globalRepository',
            'manifestations.globalArchive',
            'manifestations.globalCollection',
        ];

        foreach ($this->letters($relations) as $letter) {
            foreach ($letter->manifestations->sortBy('id') as $manifestation) {
                $repository = $this->location(
                    $manifestation->repository,
                    $manifestation->globalRepository
                );
                $archive = $this->location(
                    $manifestation->archive,
                    $manifestation->globalArchive
                );
                $collection = $this->location(
                    $manifestation->collection,
                    $manifestation->globalCollection
                );

                yield [
                    $letter->id,
                    $manifestation->id,
                    $manifestation->signature,
                    $manifestation->type,
                    $manifestation->preservation,
                    $manifestation->copy,
                    $manifestation->l_number,
                    $manifestation->manifestation_notes,
                    $manifestation->location_note,
                    ...$repository,
                    ...$archive,
                    ...$collection,
                    $this->dateTime($manifestation->created_at),
                    $this->dateTime($manifestation->updated_at),
                ];
            }
        }
    }

    private function location(?object $local, ?object $global): array
    {
        $location = $local ?? $global;
        $scope = $local ? 'local' : ($global ? 'global' : '');

        return [
            $scope,
            $location?->id,
            $location ? $this->reference($scope, $location->id) : '',
            $location?->name,
        ];
    }

    public function columnWidths(): array
    {
        return [
            'C' => 25,
            'H' => 45,
            'I' => 45,
            'M' => 35,
            'Q' => 35,
            'U' => 35,
        ];
    }
}
