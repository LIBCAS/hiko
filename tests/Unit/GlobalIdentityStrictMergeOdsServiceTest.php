<?php

namespace Tests\Unit;

use App\Services\GlobalIdentityStrictMergeOdsService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GlobalIdentityStrictMergeOdsServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        foreach ([
            'hiko-test__identity_letter',
            'hiko-test__identities',
            'global_identity_profession',
            'global_professions',
            'global_profession_categories',
            'global_identity_keyword',
            'global_identity_religion',
            'religion_translations',
            'religions',
            'global_identities',
            'merge_audit_logs',
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

        Schema::create('global_profession_categories', function (Blueprint $table) {
            $table->id();
            $table->text('name');
        });

        Schema::create('global_professions', function (Blueprint $table) {
            $table->id();
            $table->text('name');
            $table->unsignedBigInteger('profession_category_id')->nullable();
        });

        Schema::create('global_identity_profession', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('global_profession_id');
            $table->integer('position')->nullable();
            $table->timestamps();
            $table->unique(['global_identity_id', 'global_profession_id']);
        });

        Schema::create('global_identity_keyword', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id');
            $table->unsignedBigInteger('keyword_id');
            $table->timestamps();
            $table->unique(['identity_id', 'keyword_id']);
        });

        Schema::create('global_identity_religion', function (Blueprint $table) {
            $table->unsignedBigInteger('global_identity_id');
            $table->unsignedBigInteger('religion_id');
            $table->primary(['global_identity_id', 'religion_id']);
        });

        Schema::create('religions', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->boolean('is_active')->default(true);
        });

        Schema::create('religion_translations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('religion_id');
            $table->string('locale');
            $table->string('path_text')->nullable();
        });

        Schema::create('hiko-test__identities', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id')->nullable();
        });

        Schema::create('hiko-test__identity_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('global_identity_id')->nullable();
        });

        Schema::create('merge_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('entity');
            $table->string('operation');
            $table->string('status');
            $table->text('payload')->nullable();
            $table->text('result')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();
        });
    }

    #[Test]
    public function dry_run_selects_lowest_id_survivor_and_requested_scalar_values(): void
    {
        $this->insertGlobalIdentity([
            'id' => 5,
            'name' => 'Novak, Jan',
            'surname' => 'Novak',
            'forename' => 'Jan',
            'general_name_modifier' => 'ml.',
            'gender' => 'm',
            'birth_year' => '1850',
            'death_year' => '1910',
            'viaf_id' => '111',
        ]);
        $this->insertGlobalIdentity([
            'id' => 9,
            'name' => 'Novak, Jan',
            'surname' => 'Novak',
            'forename' => 'Jan',
            'general_name_modifier' => 'st.',
            'gender' => 'f',
            'birth_year' => '1850',
            'death_year' => '1910',
            'viaf_id' => '222',
        ]);
        $this->insertGlobalIdentity([
            'id' => 12,
            'name' => 'Novak, Jan',
            'surname' => 'Novak',
            'forename' => 'Jan',
            'general_name_modifier' => 'st.',
            'gender' => 'f',
            'birth_year' => '1850',
            'death_year' => '1910',
            'viaf_id' => null,
        ]);

        $report = app(GlobalIdentityStrictMergeOdsService::class)->run([
            ['source_row' => 2, 'id' => 12, 'name' => 'Novak, Jan', 'surname' => 'Novak', 'forename' => 'Jan', 'birth_year' => '1850', 'death_year' => '1910'],
            ['source_row' => 3, 'id' => 9, 'name' => 'Novak, Jan', 'surname' => 'Novak', 'forename' => 'Jan', 'birth_year' => '1850', 'death_year' => '1910'],
            ['source_row' => 4, 'id' => 5, 'name' => 'Novak, Jan', 'surname' => 'Novak', 'forename' => 'Jan', 'birth_year' => '1850', 'death_year' => '1910'],
        ]);

        $this->assertSame(1, $report['summary']['duplicate_groups_detected']);
        $this->assertSame('would_merge', $report['results'][0]['status']);
        $this->assertSame(5, $report['results'][0]['survivor_id']);
        $this->assertSame([9, 12], $report['results'][0]['deleted_ids']);
        $this->assertSame('st.', $report['results'][0]['selected_scalars']['general_name_modifier']);
        $this->assertSame('f', $report['results'][0]['selected_scalars']['gender']);
        $this->assertSame('111', $report['results'][0]['selected_scalars']['viaf_id']);
        $this->assertDatabaseCount('global_identities', 3);
    }

    #[Test]
    public function real_run_merges_selected_batch_and_returns_next_start(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        foreach ([2, 4, 10, 11] as $id) {
            $this->insertGlobalIdentity([
                'id' => $id,
                'name' => $id < 10 ? 'Alpha, Anna' : 'Beta, Bob',
                'surname' => $id < 10 ? 'Alpha' : 'Beta',
                'forename' => $id < 10 ? 'Anna' : 'Bob',
                'birth_year' => $id < 10 ? '1800' : '1810',
                'death_year' => $id < 10 ? '1850' : '1860',
            ]);
        }

        $report = app(GlobalIdentityStrictMergeOdsService::class)->run([
            ['id' => 4, 'name' => 'Alpha, Anna', 'surname' => 'Alpha', 'forename' => 'Anna', 'birth_year' => '1800', 'death_year' => '1850'],
            ['id' => 2, 'name' => 'Alpha, Anna', 'surname' => 'Alpha', 'forename' => 'Anna', 'birth_year' => '1800', 'death_year' => '1850'],
            ['id' => 10, 'name' => 'Beta, Bob', 'surname' => 'Beta', 'forename' => 'Bob', 'birth_year' => '1810', 'death_year' => '1860'],
            ['id' => 11, 'name' => 'Beta, Bob', 'surname' => 'Beta', 'forename' => 'Bob', 'birth_year' => '1810', 'death_year' => '1860'],
        ], [
            'dry_run' => false,
            'record_limit' => 2,
        ]);

        $this->assertSame(2, $report['summary']['duplicate_groups_detected']);
        $this->assertSame(1, $report['summary']['groups_selected']);
        $this->assertSame(1, $report['summary']['groups_merged']);
        $this->assertSame(1, $report['summary']['next_start']);
        $this->assertSame('merged', $report['results'][0]['status']);
        $this->assertSame(2, $report['results'][0]['survivor_id']);
        $this->assertDatabaseHas('global_identities', ['id' => 2, 'name' => 'Alpha, Anna']);
        $this->assertDatabaseMissing('global_identities', ['id' => 4]);
        $this->assertDatabaseHas('global_identities', ['id' => 10, 'name' => 'Beta, Bob']);
        $this->assertDatabaseHas('global_identities', ['id' => 11, 'name' => 'Beta, Bob']);
    }

    #[Test]
    public function it_reports_missing_records_without_merging_the_group(): void
    {
        $this->insertGlobalIdentity([
            'id' => 5,
            'name' => 'Novak, Jan',
            'surname' => 'Novak',
            'forename' => 'Jan',
            'birth_year' => '1850',
            'death_year' => '1910',
        ]);

        $report = app(GlobalIdentityStrictMergeOdsService::class)->run([
            ['id' => 5, 'name' => 'Novak, Jan', 'surname' => 'Novak', 'forename' => 'Jan', 'birth_year' => '1850', 'death_year' => '1910'],
            ['id' => 9, 'name' => 'Novak, Jan', 'surname' => 'Novak', 'forename' => 'Jan', 'birth_year' => '1850', 'death_year' => '1910'],
        ]);

        $this->assertSame('skipped', $report['results'][0]['status']);
        $this->assertSame([9], $report['results'][0]['missing_ids']);
        $this->assertDatabaseHas('global_identities', ['id' => 5]);
    }

    private function insertGlobalIdentity(array $attributes): void
    {
        DB::table('global_identities')->insert(array_merge([
            'created_at' => now(),
            'updated_at' => now(),
            'name' => 'Name',
            'surname' => null,
            'forename' => null,
            'general_name_modifier' => null,
            'alternative_names' => null,
            'related_names' => null,
            'type' => 'person',
            'nationality' => null,
            'gender' => null,
            'birth_year' => null,
            'death_year' => null,
            'related_identity_resources' => null,
            'viaf_id' => null,
            'note' => null,
            'admin_notes' => null,
        ], $attributes));
    }
}
