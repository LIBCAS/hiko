<?php

namespace Tests\Unit;

use App\Services\LocalIdentityGlobalCopyService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class LocalIdentityGlobalCopyServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'hiko-test__identity_letter',
            'hiko-test__identity_religion',
            'hiko-test__identity_profession',
            'hiko-test__identities',
            'global_identity_keyword',
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

        Schema::create('global_identity_keyword', function (Blueprint $table) {
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('keyword_id');
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

        Schema::create('hiko-test__identity_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id')->nullable();
        });
    }

    #[Test]
    public function it_matches_by_person_name_and_both_dates_and_accumulates_metadata(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $globalId = DB::table('global_identities')->insertGetId([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'general_name_modifier' => 'Dr.',
            'type' => 'person',
            'nationality' => 'Czech',
            'gender' => 'M',
            'birth_year' => '1889',
            'death_year' => '1961',
            'related_names' => '[{"surname":"Vrbová","forename":"Jana","general_name_modifier":""}]',
            'related_identity_resources' => '[{"title":"VIAF","link":"https://viaf.org/1"}]',
            'note' => '[hiko-test2#7]: Existing note',
            'admin_notes' => 'hiko-test2#7',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'general_name_modifier' => 'Prof.',
            'alternative_names' => '[]',
            'related_names' => '[{"surname":"Vrba","forename":"Johann","general_name_modifier":""}]',
            'type' => 'person',
            'nationality' => 'Slovak|czech',
            'gender' => 'male',
            'birth_year' => ' 1889 ',
            'death_year' => '1961',
            'related_identity_resources' => '[{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
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
            'general_name_modifier' => 'Dr.; Prof.',
            'nationality' => 'Czech, Slovak',
            'gender' => 'M',
            'related_names' => '[{"surname":"Vrbová","forename":"Jana","general_name_modifier":""},{"surname":"Vrba","forename":"Johann","general_name_modifier":""}]',
            'related_identity_resources' => '[{"title":"VIAF","link":"https://viaf.org/1"},{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
            'note' => '[hiko-test2#7]: Existing note' . LocalIdentityGlobalCopyService::NOTE_SEPARATOR . '[hiko-test#1]: Local note',
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
    public function different_related_identity_resources_do_not_prevent_a_match(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        DB::table('global_identities')->insert([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1889',
            'death_year' => '1961',
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
            'birth_year' => '1889',
            'death_year' => '1961',
            'related_identity_resources' => '[{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
            'note' => 'Different resource',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(0, $stats['global_created']);
        $this->assertSame(1, $stats['global_matched']);
        $this->assertDatabaseCount('global_identities', 1);
        $this->assertDatabaseHas('global_identities', [
            'related_identity_resources' => '[{"title":"Wikidata","link":"https://www.wikidata.org/wiki/Q1"}]',
            'note' => '[hiko-test#1]: Different resource',
        ]);
    }

    #[Test]
    #[DataProvider('compatibleDateExamples')]
    public function it_applies_the_approved_date_compatibility_rules(
        ?string $globalBirth,
        ?string $globalDeath,
        ?string $localBirth,
        ?string $localDeath,
        bool $shouldMatch,
        ?string $expectedBirth,
        ?string $expectedDeath
    ): void {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);
        $globalId = DB::table('global_identities')->insertGetId([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => $globalBirth,
            'death_year' => $globalDeath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => $localBirth,
            'death_year' => $localDeath,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame($shouldMatch ? 1 : 0, $stats['global_matched']);
        $this->assertSame($shouldMatch ? 0 : 1, $stats['global_created']);
        $this->assertDatabaseCount('global_identities', $shouldMatch ? 1 : 2);

        if ($shouldMatch) {
            $this->assertDatabaseHas('global_identities', [
                'id' => $globalId,
                'birth_year' => $expectedBirth,
                'death_year' => $expectedDeath,
            ]);
            $this->assertDatabaseHas('hiko-test__identities', [
                'id' => 1,
                'global_identity_id' => $globalId,
            ]);
        }
    }

    public static function compatibleDateExamples(): array
    {
        return [
            'same death, births unknown' => [null, '1699', null, '1699', true, null, '1699'],
            'same birth, deaths unknown' => ['1699', null, '1699', null, true, '1699', null],
            'same death fills birth' => [null, '1799', '1699', '1799', true, '1699', '1799'],
            'same birth fills death' => ['1699', '1799', '1699', null, true, '1699', '1799'],
            'conflicting death' => ['1699', '1799', '1699', '1780', false, null, null],
            'conflicting birth' => ['1698', '1780', '1699', '1780', false, null, null],
        ];
    }

    #[Test]
    public function records_without_any_known_date_do_not_match_automatically(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        foreach ([1, 2] as $id) {
            DB::table('hiko-test__identities')->insert([
                'id' => $id,
                'name' => 'Vrba, Jan',
                'surname' => 'Vrba',
                'forename' => 'Jan',
                'type' => 'person',
                'birth_year' => null,
                'death_year' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(2, $stats['global_created']);
        $this->assertSame(0, $stats['global_matched']);
        $this->assertSame(2, $stats['local_incomplete_match_data']);
        $this->assertDatabaseCount('global_identities', 2);
    }

    #[Test]
    public function ambiguous_compatible_globals_are_not_guessed(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        foreach (['1799', '1780'] as $deathYear) {
            DB::table('global_identities')->insert([
                'name' => 'Vrba, Jan',
                'surname' => 'Vrba',
                'forename' => 'Jan',
                'type' => 'person',
                'birth_year' => '1699',
                'death_year' => $deathYear,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1699',
            'death_year' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(1, $stats['ambiguous_date_matches']);
        $this->assertSame(0, $stats['global_matched']);
        $this->assertSame(1, $stats['global_created']);
        $this->assertDatabaseCount('global_identities', 3);
    }

    #[Test]
    public function institutions_are_left_untouched(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);
        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'University',
            'type' => 'institution',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->run();

        $this->assertSame(1, $stats['local_skipped_non_person']);
        $this->assertDatabaseCount('global_identities', 0);
        $this->assertDatabaseHas('hiko-test__identities', [
            'id' => 1,
            'global_identity_id' => null,
        ]);
    }

    #[Test]
    public function reset_unlinks_and_deletes_global_identity_data(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);
        $globalId = DB::table('global_identities')->insertGetId([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1889',
            'death_year' => '1961',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'global_identity_id' => $globalId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hiko-test__identity_letter')->insert(['global_identity_id' => $globalId]);
        DB::table('global_identity_profession')->insert([
            'global_identity_id' => $globalId,
            'global_profession_id' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('global_identity_religion')->insert([
            'global_identity_id' => $globalId,
            'religion_id' => 9,
        ]);
        DB::table('global_identity_keyword')->insert([
            'identity_id' => $globalId,
            'keyword_id' => 4,
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->reset();

        $this->assertSame(1, $stats['local_identity_links']);
        $this->assertSame(1, $stats['identity_letter_links']);
        $this->assertDatabaseCount('global_identities', 0);
        $this->assertDatabaseCount('global_identity_profession', 0);
        $this->assertDatabaseCount('global_identity_religion', 0);
        $this->assertDatabaseCount('global_identity_keyword', 0);
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 1, 'global_identity_id' => null]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 1, 'global_identity_id' => null]);
    }

    #[Test]
    public function reset_can_delete_only_institutions_and_preserve_people(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $personId = DB::table('global_identities')->insertGetId([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1889',
            'death_year' => '1961',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $institutionId = DB::table('global_identities')->insertGetId([
            'name' => 'University',
            'type' => 'institution',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            [
                'id' => 1,
                'name' => 'Vrba, Jan',
                'surname' => 'Vrba',
                'forename' => 'Jan',
                'type' => 'person',
                'global_identity_id' => $personId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'University',
                'surname' => null,
                'forename' => null,
                'type' => 'institution',
                'global_identity_id' => $institutionId,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('hiko-test__identity_letter')->insert([
            ['id' => 1, 'global_identity_id' => $personId],
            ['id' => 2, 'global_identity_id' => $institutionId],
        ]);
        DB::table('global_identity_profession')->insert([
            [
                'global_identity_id' => $personId,
                'global_profession_id' => 25,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'global_identity_id' => $institutionId,
                'global_profession_id' => 26,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('global_identity_keyword')->insert([
            ['identity_id' => $personId, 'keyword_id' => 4],
            ['identity_id' => $institutionId, 'keyword_id' => 5],
        ]);

        $stats = app(LocalIdentityGlobalCopyService::class)->reset(['type' => 'institution']);

        $this->assertSame('institution', $stats['type']);
        $this->assertSame(1, $stats['global_identities']);
        $this->assertDatabaseHas('global_identities', ['id' => $personId, 'type' => 'person']);
        $this->assertDatabaseMissing('global_identities', ['id' => $institutionId]);
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 1, 'global_identity_id' => $personId]);
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 2, 'global_identity_id' => null]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 1, 'global_identity_id' => $personId]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 2, 'global_identity_id' => null]);
        $this->assertDatabaseHas('global_identity_profession', ['global_identity_id' => $personId]);
        $this->assertDatabaseMissing('global_identity_profession', ['global_identity_id' => $institutionId]);
        $this->assertDatabaseHas('global_identity_keyword', ['identity_id' => $personId]);
        $this->assertDatabaseMissing('global_identity_keyword', ['identity_id' => $institutionId]);
    }

    #[Test]
    public function cleanup_removes_fully_undated_duplicate_groups_and_unique_undated_records(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $removableIds = [];
        foreach ([null, '0'] as $deathYear) {
            $removableIds[] = DB::table('global_identities')->insertGetId([
                'name' => 'Unknown, Jan',
                'surname' => 'Unknown',
                'forename' => 'Jan',
                'type' => 'person',
                'birth_year' => $deathYear === '0' ? '0' : null,
                'death_year' => $deathYear,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $preservedMixedIds = [];
        foreach ([null, '1699'] as $birthYear) {
            $preservedMixedIds[] = DB::table('global_identities')->insertGetId([
                'name' => 'Known, Jan',
                'surname' => 'Known',
                'forename' => 'Jan',
                'type' => 'person',
                'birth_year' => $birthYear,
                'death_year' => null,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }

        $uniqueUndatedId = DB::table('global_identities')->insertGetId([
            'name' => 'Unique, Jan',
            'surname' => 'Unique',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '0',
            'death_year' => '0',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            [
                'id' => 1,
                'name' => 'Unknown, Jan',
                'surname' => 'Unknown',
                'forename' => 'Jan',
                'type' => 'person',
                'global_identity_id' => $removableIds[0],
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => 2,
                'name' => 'Known, Jan',
                'surname' => 'Known',
                'forename' => 'Jan',
                'type' => 'person',
                'global_identity_id' => $preservedMixedIds[0],
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);
        DB::table('hiko-test__identity_letter')->insert([
            ['id' => 1, 'global_identity_id' => $removableIds[1]],
            ['id' => 2, 'global_identity_id' => $preservedMixedIds[1]],
        ]);
        DB::table('global_identity_profession')->insert([
            'global_identity_id' => $removableIds[0],
            'global_profession_id' => 25,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('global_identity_keyword')->insert([
            'identity_id' => $preservedMixedIds[0],
            'keyword_id' => 4,
        ]);

        $dryRun = app(LocalIdentityGlobalCopyService::class)
            ->removeUndatedDuplicateGroups(['dry_run' => true]);

        $this->assertSame(1, $dryRun['duplicate_groups']);
        $this->assertSame(1, $dryRun['unique_undated_records']);
        $this->assertSame(3, $dryRun['global_identities']);
        $this->assertDatabaseCount('global_identities', 5);

        $stats = app(LocalIdentityGlobalCopyService::class)->removeUndatedDuplicateGroups();

        $this->assertSame(1, $stats['duplicate_groups']);
        $this->assertSame(1, $stats['unique_undated_records']);
        $this->assertSame(3, $stats['global_identities']);
        foreach ($removableIds as $id) {
            $this->assertDatabaseMissing('global_identities', ['id' => $id]);
        }
        $this->assertDatabaseMissing('global_identities', ['id' => $uniqueUndatedId]);
        foreach ($preservedMixedIds as $id) {
            $this->assertDatabaseHas('global_identities', ['id' => $id]);
        }
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 1, 'global_identity_id' => null]);
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 2, 'global_identity_id' => $preservedMixedIds[0]]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 1, 'global_identity_id' => null]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 2, 'global_identity_id' => $preservedMixedIds[1]]);
        $this->assertDatabaseCount('global_identity_profession', 0);
        $this->assertDatabaseHas('global_identity_keyword', ['identity_id' => $preservedMixedIds[0]]);
    }

    #[Test]
    public function strict_cleanup_removes_all_undated_records_including_members_of_mixed_groups(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $undatedId = DB::table('global_identities')->insertGetId([
            'name' => 'Known, Jan',
            'surname' => 'Known',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '0',
            'death_year' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $datedId = DB::table('global_identities')->insertGetId([
            'name' => 'Known, Jan',
            'surname' => 'Known',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1699',
            'death_year' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $uniqueDatedId = DB::table('global_identities')->insertGetId([
            'name' => 'Dated, Jan',
            'surname' => 'Dated',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => null,
            'death_year' => '1799',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('hiko-test__identities')->insert([
            'id' => 1,
            'name' => 'Known, Jan',
            'surname' => 'Known',
            'forename' => 'Jan',
            'type' => 'person',
            'global_identity_id' => $undatedId,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('hiko-test__identity_letter')->insert([
            'id' => 1,
            'global_identity_id' => $undatedId,
        ]);
        DB::table('global_identity_religion')->insert([
            'global_identity_id' => $undatedId,
            'religion_id' => 9,
        ]);

        $dryRun = app(LocalIdentityGlobalCopyService::class)
            ->removeAllUndatedGlobalIdentities(['dry_run' => true]);

        $this->assertTrue($dryRun['strict']);
        $this->assertSame(1, $dryRun['global_identities']);
        $this->assertDatabaseHas('global_identities', ['id' => $undatedId]);

        $stats = app(LocalIdentityGlobalCopyService::class)->removeAllUndatedGlobalIdentities();

        $this->assertTrue($stats['strict']);
        $this->assertSame(1, $stats['global_identities']);
        $this->assertDatabaseMissing('global_identities', ['id' => $undatedId]);
        $this->assertDatabaseHas('global_identities', ['id' => $datedId]);
        $this->assertDatabaseHas('global_identities', ['id' => $uniqueDatedId]);
        $this->assertDatabaseHas('hiko-test__identities', ['id' => 1, 'global_identity_id' => null]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['id' => 1, 'global_identity_id' => null]);
        $this->assertDatabaseCount('global_identity_religion', 0);
    }
}
