<?php

namespace Database\Seeders;

use App\Models\User;
use App\Models\Keyword;
use App\Models\Location;
use App\Models\Profession;
use Illuminate\Database\Seeder;
use App\Models\ProfessionCategory;

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

        Profession::factory()->create([
            'name' => [
                'cs' => 'umělec',
                'en' => 'artist',
            ]
        ]);

        Profession::factory()->create([
            'name' => [
                'cs' => 'knihovník',
                'en' => 'librarian',
            ]
        ]);

        ProfessionCategory::factory()->create([
            'name' => [
                'cs' => 'humanitní vědy',
                'en' => 'humanities ',
            ]
        ]);

        Keyword::factory()->create([
            'name' => [
                'cs' => 'antropologie',
                'en' => 'anthropology',
            ]
        ]);

        Keyword::factory()->create([
            'name' => [
                'cs' => 'estetika',
                'en' => 'aesthetics',
            ]
        ]);

        Keyword::factory()->create([
            'name' => [
                'cs' => 'humanismus',
                'en' => 'humanism',
            ]
        ]);
    }
}
