<?php

namespace Database\Seeders;

use App\Models\Identity;
use App\Models\User;
use App\Models\Place;
use App\Models\Keyword;
use App\Models\Location;
use App\Models\Profession;
use App\Models\KeywordCategory;
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

        $profession_category = ProfessionCategory::factory()->create([
            'name' => [
                'cs' => 'humanitní vědy',
                'en' => 'humanities ',
            ]
        ]);

        $identities = Identity::factory()->count(5)->create();

        $professions = Profession::all();

        $identities->each(function ($identity) use ($professions, $profession_category) {
            $identity->professions()->attach(
                $professions->random(rand(1, 2))->pluck('id')->toArray()
            );

            $identity->profession_categories()->attach(1);
        });

        $category_one = KeywordCategory::factory()->create([
            'name' => [
                'cs' => 'ideologie',
                'en' => 'ideology',
            ]
        ]);

        $category_two = KeywordCategory::factory()->create([
            'name' => [
                'cs' => 'věda a umění',
                'en' => 'sciences and arts',
            ]
        ]);

        $keyword = Keyword::factory()->create([
            'name' => [
                'cs' => 'estetika',
                'en' => 'aesthetics',
            ],
        ]);

        $keyword->keyword_category()->associate($category_two);
        $keyword->save();

        $keyword = Keyword::factory()->create([
            'name' => [
                'cs' => 'humanismus',
                'en' => 'humanism',
            ],
        ]);

        $keyword->keyword_category()->associate($category_one);
        $keyword->save();

        $keyword = Keyword::factory()->create([
            'name' => [
                'cs' => 'antropologie',
                'en' => 'anthropology',
            ],
        ]);

        $keyword->keyword_category()->associate($category_two);
        $keyword->save();

        Place::factory()->create([
            'name' => 'Prague',
            'country' => 'Czech Republic',
            'longitude' => 14.42076,
            'latitude' => 50.08804,
            'geoname_id' => 3067696,
        ]);

        Place::factory()->create([
            'name' => 'Tartu',
            'country' => 'Estonia',
            'longitude' => 26.716666666667,
            'latitude' => 58.383333333333,
            'geoname_id' => 588335,
            'note' => 'on this time the Russian Empire; historical name Dorpat or Děrpt'
        ]);
    }
}
