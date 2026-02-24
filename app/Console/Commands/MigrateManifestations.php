<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Tenant;
use App\Models\Letter;
use App\Models\Location;
use App\Models\Manifestation;
use Illuminate\Support\Facades\DB;

class MigrateManifestations extends Command
{
    protected $signature = 'migrate:manifestations';
    protected $description = 'Migrate letter copies JSON to manifestations table';

    public function handle()
    {
        $tenants = Tenant::all();

        foreach ($tenants as $tenant) {
            $this->info("Processing tenant: {$tenant->name}");
            tenancy()->initialize($tenant);

            $letters = Letter::whereNotNull('copies')->get();

            $bar = $this->output->createProgressBar($letters->count());

            DB::transaction(function () use ($letters, $bar) {
                foreach ($letters as $letter) {
                    $copies = $letter->copies; // Is already an array due to casts

                    if (!is_array($copies)) {
                        $bar->advance();
                        continue;
                    }

                    foreach ($copies as $copy) {
                        // Resolve Locations
                        $repoId = $this->resolveLocationId($copy['repository'] ?? null, 'repository');
                        $archiveId = $this->resolveLocationId($copy['archive'] ?? null, 'archive');
                        $collectionId = $this->resolveLocationId($copy['collection'] ?? null, 'collection');

                        // Create Manifestation
                        Manifestation::create([
                            'letter_id' => $letter->id,
                            'repository_id' => $repoId,
                            'archive_id' => $archiveId,
                            'collection_id' => $collectionId,
                            'signature' => $copy['signature'] ?? null,
                            'type' => $copy['type'] ?? null,
                            'preservation' => $copy['preservation'] ?? null,
                            'copy' => $copy['copy'] ?? null,
                            'l_number' => $copy['l_number'] ?? null,
                            'manifestation_notes' => $copy['manifestation_notes'] ?? null,
                            'location_note' => $copy['location_note'] ?? null,
                        ]);
                    }
                    $bar->advance();
                }
            });

            $bar->finish();
            $this->newLine();
            tenancy()->end();
        }

        $this->info('Migration completed.');
    }

    private function resolveLocationId(?string $name, string $type): ?int
    {
        if (empty($name)) return null;

        $name = trim($name);

        // Find or create local location
        $location = Location::firstOrCreate(
            ['name' => $name, 'type' => $type],
            ['name' => $name, 'type' => $type]
        );

        return $location->id;
    }
}
