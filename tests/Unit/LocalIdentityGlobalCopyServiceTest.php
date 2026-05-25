<?php

namespace Tests\Unit;

use App\Services\LocalIdentityGlobalCopyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalIdentityGlobalCopyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'hiko-test__identity_religion',
            'hiko-test__identity_profession',
            'hiko-test__identities',
            'global_identity_religion',
            'global_identity_profession',
            'global_identities',
            'tenants',
        ] as $table) {
            Schema::dropIfExists($table);
        }

        Schema::create('tenants', function (Blueprint $table) {
            $table->id();
            $table->string('table_prefix');
        });

        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->string('general_name_modifier')->nullable();
            $table->text('alternative_names')->nullable();
            $table->text('related_names')->nullable();
            $table->string('type');
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('birth_year')->nullable();
            $table->string('death_year')->nullable();
            $table->text('related_identity_resources')->nullable();
            $table->string('viaf_id')->nullable();
            $table->text('note')->nullable();
            $table->text('admin_notes')->nullable();
        });

        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('global_profession_id');
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->unique(['global_identity_id', 'global_profession_id']);
        });

        Schema::create('global_identity_religion', function (Blueprint $table) {
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('religion_id');
            $table->primary(['global_identity_id', 'religion_id']);
        });

        Schema::create('hiko-test__identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('surname')->nullable();
            $table->string('forename')->nullable();
            $table->string('general_name_modifier')->nullable();
            $table->text('alternative_names')->nullable();
            $table->text('related_names')->nullable();
            $table->string('type');
            $table->string('nationality')->nullable();
            $table->string('gender')->nullable();
            $table->string('birth_year')->nullable();
            $table->string('death_year')->nullable();
            $table->text('related_identity_resources')->nullable();
            $table->string('viaf_id')->nullable();
            $table->unsignedBigInteger('global_identity_id')->nullable();
            $table->text('note')->nullable();
        });

        Schema::create('hiko-test__identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('profession_id')->nullable();
            $table->integer('position')->nullable();
            $table->unsignedBigInteger('global_profession_id')->nullable();
        });

        Schema::create('hiko-test__identity_religion', function (Blueprint $table) {
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('religion_id');
            $table->primary(['identity_id', 'religion_id']);
        });
    }

    #[Test]
    public function it_matches_empty_variants_copies_pivots_appends_notes_and_links_local_identities(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $globalId = DB::table('global_identities')->insertGetId([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'note' => 'Existing note',
            'admin_notes' => 'hiko-test2#7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'general_name_modifier' => '   ',
            'alternative_names' => '[]',
            'related_names' => '[]',
            'type' => 'person',
            'nationality' => '',
            'gender' => null,
            'birth_year' => null,
            'death_year' => null,
            'related_identity_resources' => '[]',
            'viaf_id' => '123',
            'note' => 'Local note',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identity_profession')->insert([
            'identity_id' => 1,
            'global_profession_id' => 25,
            'position' => 3,
        ]);
        DB::table('hiko-test__identity_religion')->insert([
            'identity_id' => 1,
            'religion_id' => 9,
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(1, $stats['global_matched']);
        $this->assertSame(0, $stats['global_created']);
        $this->assertSame(1, $stats['local_links_updated']);
        $this->assertSame(1, $stats['professions_inserted']);
        $this->assertSame(1, $stats['religions_inserted']);

        $this->assertDatabaseCount('global_identities', 1);
        $this->assertDatabaseHas('global_identities', [
            'id' => $globalId,
            'note' => 'Existing note' . LocalIdentityGlobalCopyService::NOTE_SEPARATOR . 'Local note',
            'admin_notes' => 'hiko-test2#7, hiko-test#1',
        ]);
        $this->assertDatabaseHas('hiko-test__identities', [
            'id' => 1,
            'global_identity_id' => $globalId,
        ]);
        $this->assertDatabaseHas('global_identity_profession', [
            'global_identity_id' => $globalId,
            'global_profession_id' => 25,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('global_identity_religion', [
            'global_identity_id' => $globalId,
            'religion_id' => 9,
        ]);
        $this->assertDatabaseHas('hiko-test__identity_profession', [
            'identity_id' => 1,
            'global_profession_id' => 25,
            'position' => 3,
        ]);
        $this->assertDatabaseHas('hiko-test__identity_religion', [
            'identity_id' => 1,
            'religion_id' => 9,
        ]);
    }

    #[Test]
    public function different_related_identity_resources_create_a_separate_global_identity(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        DB::table('global_identities')->insert([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'related_identity_resources' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'related_identity_resources' => '[{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
            'note' => 'Different resource',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(1, $stats['global_created']);
        $this->assertSame(0, $stats['global_matched']);
        $this->assertDatabaseCount('global_identities', 2);
        $this->assertDatabaseHas('global_identities', [
            'related_identity_resources' => '[{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
            'note' => 'Different resource',
            'admin_notes' => 'hiko-test#1',
        ]);
    }
}
