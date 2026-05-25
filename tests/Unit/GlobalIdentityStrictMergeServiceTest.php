<?php

namespace Tests\Unit;

use App\Models\GlobalIdentity;
use App\Services\GlobalIdentityStrictMergeService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use PHPUnit\Framework\Attributes\Test;
use Tests\TestCase;

class GlobalIdentityStrictMergeServiceTest extends TestCase
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
    public function it_merges_global_identity_records_and_repoints_references(): void
    {
        DB::table('tenants')->insert(['table_prefix' => 'hiko-test']);

        $survivor = GlobalIdentity::query()->create([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'birth_year' => '1889',
            'nationality' => 'hungarian, Czech',
            'admin_notes' => 'hiko-test#1, hiko-test2#3',
        ]);
        DB::table('global_identities')->where('id', $survivor->id)->update(['alternative_names' => 'Alt one']);

        $loser = GlobalIdentity::query()->create([
            'name' => 'Jan Vrba',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'death_year' => '1961',
            'nationality' => 'Czech, hungarian',
            'note' => 'Second note',
            'admin_notes' => 'hiko-test2#3, hiko-test#2',
        ]);
        DB::table('global_identities')->where('id', $loser->id)->update(['alternative_names' => 'Alt one']);

        DB::table('global_professions')->insert([
            'id' => 10,
            'name' => json_encode(['cs' => 'Spisovatel', 'en' => 'Writer']),
        ]);
        DB::table('religions')->insert(['id' => 30, 'name' => 'Religion']);
        DB::table('global_identity_profession')->insert([
            'global_identity_id' => $loser->id,
            'global_profession_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('global_identity_keyword')->insert([
            'identity_id' => $loser->id,
            'keyword_id' => 20,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('global_identity_religion')->insert([
            'global_identity_id' => $loser->id,
            'religion_id' => 30,
        ]);
        DB::table('hiko-test__identities')->insert(['global_identity_id' => $loser->id]);
        DB::table('hiko-test__identity_letter')->insert(['global_identity_id' => $loser->id]);

        $service = app(GlobalIdentityStrictMergeService::class);
        $records = $service->getPreviewRecords([$survivor->id, $loser->id]);
        $deathYearKey = collect($service->scalarOptions($records, 'death_year'))
            ->firstWhere('value', '1961')['key'];
        $professionKeys = collect($service->multiOptions($records, 'professions'))->pluck('key')->all();
        $religionKeys = collect($service->multiOptions($records, 'religions'))->pluck('key')->all();
        $nationalityKeys = collect($service->multiOptions($records, 'nationality'))->pluck('key')->all();
        $noteKeys = collect($service->multiOptions($records, 'note'))->pluck('key')->all();

        app(GlobalIdentityStrictMergeService::class)->execute(
            [$survivor->id, $loser->id],
            $survivor->id,
            ['death_year' => $deathYearKey],
            [
                'professions' => $professionKeys,
                'religions' => $religionKeys,
                'nationality' => $nationalityKeys,
                'note' => $noteKeys,
            ]
        );

        $this->assertDatabaseMissing('global_identities', ['id' => $loser->id]);
        $this->assertDatabaseHas('global_identities', [
            'id' => $survivor->id,
            'death_year' => '1961',
            'nationality' => 'Hungarian, Czech',
            'note' => 'Second note',
            'admin_notes' => 'hiko-test#1, hiko-test2#3, hiko-test#2',
            'alternative_names' => 'Alt one' . "\n\n===\n\n" . 'Alt one',
        ]);
        $this->assertDatabaseHas('global_identity_profession', ['global_identity_id' => $survivor->id, 'global_profession_id' => 10]);
        $this->assertDatabaseHas('global_identity_keyword', ['identity_id' => $survivor->id, 'keyword_id' => 20]);
        $this->assertDatabaseHas('global_identity_religion', ['global_identity_id' => $survivor->id, 'religion_id' => 30]);
        $this->assertDatabaseHas('hiko-test__identities', ['global_identity_id' => $survivor->id]);
        $this->assertDatabaseHas('hiko-test__identity_letter', ['global_identity_id' => $survivor->id]);
        $this->assertDatabaseHas('merge_audit_logs', ['entity' => 'global_identity', 'operation' => 'strict_global_merge', 'status' => 'success']);
    }

    #[Test]
    public function empty_multi_selection_uses_database_defaults(): void
    {
        $survivor = GlobalIdentity::query()->create([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'nationality' => 'czech',
        ]);

        $loser = GlobalIdentity::query()->create([
            'name' => 'Vrba, Jan',
            'surname' => 'Vrba',
            'forename' => 'Jan',
            'type' => 'person',
            'nationality' => 'hungarian',
            'note' => 'Note',
        ]);

        DB::table('global_professions')->insert([
            'id' => 10,
            'name' => json_encode(['cs' => 'Spisovatel', 'en' => 'Writer']),
        ]);
        DB::table('religions')->insert(['id' => 30, 'name' => 'Religion']);
        DB::table('global_identity_profession')->insert([
            'global_identity_id' => $loser->id,
            'global_profession_id' => 10,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('global_identity_religion')->insert([
            'global_identity_id' => $loser->id,
            'religion_id' => 30,
        ]);

        app(GlobalIdentityStrictMergeService::class)->execute(
            [$survivor->id, $loser->id],
            $survivor->id,
            [],
            [
                'nationality' => [],
                'note' => [],
                'professions' => [],
                'religions' => [],
            ]
        );

        $this->assertDatabaseHas('global_identities', [
            'id' => $survivor->id,
            'nationality' => null,
            'note' => null,
        ]);
        $this->assertDatabaseMissing('global_identity_profession', ['global_identity_id' => $survivor->id]);
        $this->assertDatabaseMissing('global_identity_religion', ['global_identity_id' => $survivor->id]);
    }

    #[Test]
    public function it_blocks_mixed_identity_types(): void
    {
        $person = GlobalIdentity::query()->create([
            'name' => 'Person',
            'surname' => 'Person',
            'type' => 'person',
        ]);

        $institution = GlobalIdentity::query()->create([
            'name' => 'Institution',
            'type' => 'institution',
        ]);

        $this->expectException(\InvalidArgumentException::class);

        app(GlobalIdentityStrictMergeService::class)->execute(
            [$person->id, $institution->id],
            $person->id,
            ['type' => collect(app(GlobalIdentityStrictMergeService::class)->scalarOptions(
                app(GlobalIdentityStrictMergeService::class)->getPreviewRecords([$person->id, $institution->id]),
                'type'
            ))->first()['key']],
            []
        );
    }
}
