<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Location;
use App\Models\GlobalLocation;
use Illuminate\Support\Facades\DB;
use App\Models\Manifestation;

class LocationsConsistencyCheck extends Component
{
    public $scope = 'all'; // all, local, global
    public $isScanning = false;
    public $issues = [];

    public function scan()
    {
        $this->isScanning = true;
        $this->issues = [];

        // Local
        if (($this->scope === 'local' || $this->scope === 'all') && tenancy()->initialized) {
            $this->checkDuplicates(Location::class, 'local');
            $this->checkOrphans(Location::class, 'local');
        }

        // Global
        if ($this->scope === 'global' || $this->scope === 'all') {
            $this->checkDuplicates(GlobalLocation::class, 'global');
            $this->checkOrphans(GlobalLocation::class, 'global');
        }

        $this->isScanning = false;
    }

    protected function checkDuplicates($modelClass, $type)
    {
        // Find duplicates by Name + Type
        $duplicates = $modelClass::select('name', 'type', DB::raw('count(*) as count'))
            ->groupBy('name', 'type')
            ->having('count', '>', 1)
            ->get();

        foreach ($duplicates as $dup) {
            $records = $modelClass::where('name', $dup->name)->where('type', $dup->type)->get();
            foreach ($records as $record) {
                $this->issues[] = [
                    'type' => $type,
                    'id' => $record->id,
                    'name' => "{$record->name} ({$record->type})",
                    'error' => "Duplicate location found ($dup->count records)",
                ];
            }
        }
    }

    protected function checkOrphans($modelClass, $sourceType)
    {
        $modelClass::chunk(200, function ($locations) use ($sourceType) {
            foreach ($locations as $location) {
                $isUsed = false;    // Check if used in any manifestations

                if ($sourceType === 'local') {
                    $isUsed = Manifestation::where('repository_id', $location->id)
                        ->orWhere('archive_id', $location->id)
                        ->orWhere('collection_id', $location->id)
                        ->exists();
                } else {
                    $isUsed = Manifestation::where('global_repository_id', $location->id)
                        ->orWhere('global_archive_id', $location->id)
                        ->orWhere('global_collection_id', $location->id)
                        ->exists();
                }

                if (!$isUsed) {
                    $this->issues[] = [
                        'type' => __('hiko.' . $location->type),
                        'source' => __('hiko.' . $sourceType),
                        'id' => $location->id,
                        'name' => $location->name,
                        'error' => __('hiko.location_not_used_in_manifestations'),
                    ];
                }
            }
        });
    }

    public function render()
    {
        return view('livewire.locations-consistency-check');
    }
}
