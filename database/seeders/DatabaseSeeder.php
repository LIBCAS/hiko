<?php

namespace Database\Seeders;

use App\Models\Location;
use App\Models\User;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     *
     * @return void
     */
    public function run()
    {
        User::factory()->create([
            'name' => 'Zkušební Administrátor',
            'email' => 'admin@example.com',
            'role' => 'admin',
        ]);

        User::factory()->create([
            'name' => 'Zkušební Editor',
            'email' => 'editor@example.com',
            'role' => 'editor',
        ]);

        User::factory()->create([
            'name' => 'Zkušební Divák',
            'email' => 'guest@example.com',
            'role' => 'guest',
        ]);

        Location::factory()->create([
            'name' => 'Národní Archiv',
            'type' => 'archive',
        ]);

        Location::factory()->create([
            'name' => 'Národní muzeum',
            'type' => 'repository',
        ]);

        Location::factory()->create([
            'name' => 'Handschriften',
            'type' => 'collection',
        ]);
    }
}
