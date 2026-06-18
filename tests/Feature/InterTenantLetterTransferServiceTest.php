<?php

namespace Tests\Feature;

use App\Models\InterTenantTransferRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InterTenantLetterTransferData;
use App\Services\InterTenantLetterTransferService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class InterTenantLetterTransferServiceTest extends TestCase
{
    private string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-transfer-');
        $connection = [
            'driver' => 'sqlite',
            'database' => $this->databasePath,
            'prefix' => '',
            'foreign_key_constraints' => true,
        ];
        Config::set('database.connections.mysql', $connection);
        Config::set('database.connections.tenant', $connection);
        Config::set('database.default', 'mysql');
        Config::set('tenancy.database.central_connection', 'mysql');
        DB::purge('mysql');
        DB::purge('tenant');

        $this->createSchema();
    }

    protected function tearDown(): void
    {
        DB::disconnect('mysql');
        DB::disconnect('tenant');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function test_it_copies_letters_as_drafts_and_attaches_the_approver(): void
    {
        $sourceId = DB::connection('mysql')->table('tenants')->insertGetId([
            'name' => 'Source',
            'table_prefix' => 'source',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $targetId = DB::connection('mysql')->table('tenants')->insertGetId([
            'name' => 'Target',
            'table_prefix' => 'target',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $source = Tenant::query()->findOrFail($sourceId);
        $target = Tenant::query()->findOrFail($targetId);

        DB::connection('mysql')->table('source__letters')->insert([
            'id' => 15,
            'uuid' => 'source-uuid',
            'date_year' => 1926,
            'history' => 'old history',
            'status' => 'publish',
            'approval' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::connection('mysql')->table('source__places')->insert([
            'id' => 7,
            'name' => 'Prague',
        ]);
        DB::connection('mysql')->table('global_places')->insert([
            'id' => 22,
            'name' => 'Praha',
        ]);
        DB::connection('mysql')->table('source__letter_place')->insert([
            'letter_id' => 15,
            'place_id' => 7,
            'global_place_id' => null,
            'role' => 'origin',
        ]);

        $request = new InterTenantTransferRequest([
            'source_tenant_id' => $source->id,
            'target_tenant_id' => $target->id,
            'entity_type' => 'letters',
            'status' => InterTenantTransferRequest::STATUS_PENDING,
            'requested_by_user_id' => 3,
            'requested_by_name' => 'Source Admin',
            'source_record_ids' => [15],
        ]);
        $request->setConnection('mysql');
        $request->save();

        $approver = new User(['name' => 'Target Admin', 'email' => 'target@example.test']);
        $approver->id = 9;

        $result = (new InterTenantLetterTransferService(new InterTenantLetterTransferData()))
            ->approve($request, $target, $approver, [
                'places' => [7 => 'global-22'],
            ]);

        $targetLetter = DB::connection('mysql')->table('target__letters')->first();
        $this->assertSame(15, array_key_first($result['letter_id_map']));
        $this->assertSame('draft', $targetLetter->status);
        $this->assertSame(0, $targetLetter->approval);
        $this->assertNull($targetLetter->history);
        $this->assertNotSame('source-uuid', $targetLetter->uuid);
        $this->assertDatabaseHas('target__letter_user', [
            'letter_id' => $targetLetter->id,
            'user_id' => 9,
        ], 'mysql');
        $this->assertDatabaseHas('target__letter_place', [
            'letter_id' => $targetLetter->id,
            'place_id' => null,
            'global_place_id' => 22,
            'role' => 'origin',
        ], 'mysql');

        $request->refresh();
        $this->assertSame(InterTenantTransferRequest::STATUS_COMPLETED, $request->status);
        $this->assertSame(1, $request->result['letter_count']);
        $this->assertSame(1926, $request->final_snapshot['letters'][0]['date_year']);
    }

    private function createSchema(): void
    {
        $schema = Schema::connection('mysql');

        $schema->create('tenants', function (Blueprint $table) {
            $table->increments('id');
            $table->string('name');
            $table->string('table_prefix')->unique();
            $table->json('data')->nullable();
            $table->timestamps();
        });

        $schema->create('inter_tenant_transfer_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('source_tenant_id');
            $table->unsignedInteger('target_tenant_id');
            $table->string('entity_type');
            $table->string('status');
            $table->unsignedBigInteger('requested_by_user_id')->nullable();
            $table->string('requested_by_name');
            $table->string('requested_by_email')->nullable();
            $table->unsignedBigInteger('decided_by_user_id')->nullable();
            $table->string('decided_by_name')->nullable();
            $table->string('decided_by_email')->nullable();
            $table->json('source_record_ids');
            $table->json('filters')->nullable();
            $table->json('mappings')->nullable();
            $table->json('result')->nullable();
            $table->text('final_snapshot')->nullable();
            $table->text('decision_reason')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->timestamps();
        });
        $schema->create('global_places', function (Blueprint $table) {
            $table->id();
            $table->string('name');
        });

        foreach (['source', 'target'] as $prefix) {
            $schema->create("{$prefix}__letters", function (Blueprint $table) {
                $table->id();
                $table->string('uuid');
                $table->integer('date_year')->nullable();
                $table->text('history')->nullable();
                $table->string('status');
                $table->boolean('approval');
                $table->timestamps();
            });
            $schema->create("{$prefix}__identity_letter", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('identity_id')->nullable();
                $table->unsignedBigInteger('global_identity_id')->nullable();
                $table->unsignedBigInteger('letter_id');
                $table->string('role')->nullable();
                $table->integer('position')->nullable();
                $table->text('marked')->nullable();
                $table->text('salutation')->nullable();
            });
            $schema->create("{$prefix}__letter_place", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('place_id')->nullable();
                $table->unsignedBigInteger('global_place_id')->nullable();
                $table->string('role');
                $table->integer('position')->nullable();
                $table->text('marked')->nullable();
            });
            $schema->create("{$prefix}__places", function (Blueprint $table) {
                $table->id();
                $table->string('name');
            });
            $schema->create("{$prefix}__keyword_letter", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('keyword_id')->nullable();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('global_keyword_id')->nullable();
            });
            $schema->create("{$prefix}__manifestations", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('repository_id')->nullable();
                $table->unsignedBigInteger('archive_id')->nullable();
                $table->unsignedBigInteger('collection_id')->nullable();
            });
            $schema->create("{$prefix}__media", function (Blueprint $table) {
                $table->id();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->integer('order_column')->nullable();
            });
            $schema->create("{$prefix}__letter_user", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('user_id');
            });
        }
    }
}
