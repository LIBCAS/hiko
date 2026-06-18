<?php

namespace Tests\Feature;

use App\Models\InterTenantTransferRequest;
use App\Models\Tenant;
use App\Models\User;
use App\Services\InterTenantDependencyCopyService;
use App\Services\InterTenantLetterTransferData;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use RuntimeException;
use Tests\TestCase;

class InterTenantDependencyCopyServiceTest extends TestCase
{
    private string $databasePath;
    private Tenant $source;
    private Tenant $target;
    private InterTenantTransferRequest $transfer;
    private InterTenantDependencyCopyService $service;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-dependency-copy-');
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
        $this->source = $this->createTenant('Source', 'source');
        $this->target = $this->createTenant('Target', 'target');
        DB::connection('mysql')->table('source__letters')->insert(['id' => 10]);

        $this->transfer = new InterTenantTransferRequest([
            'source_tenant_id' => $this->source->id,
            'target_tenant_id' => $this->target->id,
            'entity_type' => 'letters',
            'status' => InterTenantTransferRequest::STATUS_PENDING,
            'requested_by_name' => 'Source Admin',
            'source_record_ids' => [10],
        ]);
        $this->transfer->save();
        $this->service = new InterTenantDependencyCopyService(new InterTenantLetterTransferData());
    }

    protected function tearDown(): void
    {
        DB::disconnect('mysql');
        DB::disconnect('tenant');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function test_it_normalizes_copies_and_then_reuses_a_place_with_audit_history(): void
    {
        DB::connection('mysql')->table('source__places')->insert([
            'id' => 7,
            'name' => ' Prague ',
            'additional_name' => ' Old Town ',
            'country' => 'Czech Republic',
            'division' => ' Prague ',
            'note' => 'Source note',
            'latitude' => 50.087,
            'longitude' => 14.421,
            'alternative_names' => json_encode(['Praha'], JSON_UNESCAPED_UNICODE),
            'geoname_id' => 3067696,
        ]);
        DB::connection('mysql')->table('source__letter_place')->insert([
            'letter_id' => 10,
            'place_id' => 7,
            'role' => 'origin',
        ]);

        $created = $this->service->copy(
            $this->transfer,
            $this->target,
            $this->user(),
            'places',
            7
        );
        $reused = $this->service->copy(
            $this->transfer->fresh(),
            $this->target,
            $this->user(),
            'places',
            7
        );
        $reusePreview = $this->service->preview(
            $this->transfer->fresh(),
            $this->target,
            'places',
            7
        );

        $this->assertSame('created', $created['action']);
        $this->assertSame($created['id'], $reused['id']);
        $this->assertSame('reused', $reused['action']);
        $this->assertSame('reuse', $reusePreview['action']);
        $this->assertSame('#' . $created['id'], $reusePreview['message_parts'][1]['text']);
        $this->assertStringContainsString(
            "/places/{$created['id']}/edit",
            $reusePreview['message_parts'][1]['url']
        );
        $this->assertDatabaseCount('target__places', 1, 'mysql');
        $this->assertDatabaseHas('target__places', [
            'id' => $created['id'],
            'name' => 'Prague',
            'additional_name' => 'Old Town',
            'country' => 'Czech Republic',
            'division' => 'Prague',
        ], 'mysql');

        $audit = $this->transfer->fresh()->result['dependency_copies'];
        $this->assertSame(['created', 'reused'], array_column($audit, 'action'));
        $this->assertSame([7, 7], array_column($audit, 'source_id'));
    }

    public function test_keyword_copy_requires_a_choice_when_multiple_categories_match(): void
    {
        DB::connection('mysql')->table('source__keyword_categories')->insert([
            'id' => 4,
            'name' => json_encode(['cs' => ' Politika ', 'en' => ' Politics '], JSON_UNESCAPED_UNICODE),
        ]);
        DB::connection('mysql')->table('source__keywords')->insert([
            'id' => 8,
            'name' => json_encode(['cs' => ' Demokracie ', 'en' => ' Democracy '], JSON_UNESCAPED_UNICODE),
            'keyword_category_id' => 4,
        ]);
        DB::connection('mysql')->table('source__keyword_letter')->insert([
            'letter_id' => 10,
            'keyword_id' => 8,
        ]);
        DB::connection('mysql')->table('target__keyword_categories')->insert([
            [
                'id' => 21,
                'name' => json_encode(['cs' => 'politika', 'en' => 'politics']),
            ],
            [
                'id' => 22,
                'name' => json_encode(['cs' => 'POLITIKA', 'en' => 'POLITICS']),
            ],
        ]);

        $preview = $this->service->preview($this->transfer, $this->target, 'keywords', 8);
        try {
            $this->service->copy(
                $this->transfer,
                $this->target,
                $this->user(),
                'keywords',
                8
            );
            $this->fail('Copying without selecting one of the duplicate categories should fail.');
        } catch (RuntimeException $e) {
            $this->assertSame(
                __('hiko.transfer_copy_keyword_category_required'),
                $e->getMessage()
            );
        }
        $result = $this->service->copy(
            $this->transfer,
            $this->target,
            $this->user(),
            'keywords',
            8,
            22
        );

        $this->assertSame('choose_category', $preview['action']);
        $this->assertSame([21, 22], array_column($preview['category_options'], 'id'));
        $this->assertSame('created', $result['action']);
        $this->assertSame('reused', $result['category_action']);
        $this->assertSame(22, $result['category_id']);
        $keyword = DB::connection('mysql')->table('target__keywords')->find($result['id']);
        $this->assertSame(
            ['cs' => 'Demokracie', 'en' => 'Democracy'],
            json_decode($keyword->name, true)
        );
    }

    public function test_keyword_preview_links_the_exact_keyword_created_by_an_earlier_copy(): void
    {
        DB::connection('mysql')->table('source__keyword_categories')->insert([
            'id' => 4,
            'name' => json_encode(['cs' => 'Politika', 'en' => 'Politics'], JSON_UNESCAPED_UNICODE),
        ]);
        DB::connection('mysql')->table('source__keywords')->insert([
            'id' => 8,
            'name' => json_encode(['cs' => 'Demokracie', 'en' => 'Democracy'], JSON_UNESCAPED_UNICODE),
            'keyword_category_id' => 4,
        ]);
        DB::connection('mysql')->table('source__keyword_letter')->insert([
            'letter_id' => 10,
            'keyword_id' => 8,
        ]);

        $created = $this->service->copy(
            $this->transfer,
            $this->target,
            $this->user(),
            'keywords',
            8
        );
        $preview = $this->service->preview(
            $this->transfer->fresh(),
            $this->target,
            'keywords',
            8
        );

        $this->assertSame('reuse', $preview['action']);
        $this->assertSame('reuse', $preview['category_action']);
        $this->assertSame('#' . $created['id'], $preview['message_parts'][1]['text']);
        $this->assertStringContainsString(
            "/keywords/{$created['id']}/edit",
            $preview['message_parts'][1]['url']
        );
        $this->assertSame('#' . $created['category_id'], $preview['message_parts'][3]['text']);
        $this->assertStringContainsString(
            "/keywords/category/{$created['category_id']}/edit",
            $preview['message_parts'][3]['url']
        );
    }

    public function test_location_copy_reuses_a_case_insensitive_name_and_matching_type(): void
    {
        DB::connection('mysql')->table('source__locations')->insert([
            'id' => 5,
            'name' => ' Archive Prague ',
            'type' => 'archive',
        ]);
        DB::connection('mysql')->table('source__manifestations')->insert([
            'letter_id' => 10,
            'archive_id' => 5,
        ]);
        DB::connection('mysql')->table('target__locations')->insert([
            'id' => 31,
            'name' => 'archive prague',
            'type' => 'archive',
        ]);

        $result = $this->service->copy(
            $this->transfer,
            $this->target,
            $this->user(),
            'locations',
            5
        );

        $this->assertSame(['id' => 31, 'action' => 'reused'], $result);
        $this->assertDatabaseCount('target__locations', 1, 'mysql');
    }

    public function test_identity_auto_mapping_only_selects_one_target_with_the_same_global_identity_and_type(): void
    {
        DB::connection('mysql')->table('target__identities')->insert([
            ['id' => 41, 'name' => 'Unique person', 'type' => 'person', 'global_identity_id' => 101],
            ['id' => 42, 'name' => 'Wrong type', 'type' => 'institution', 'global_identity_id' => 101],
            ['id' => 43, 'name' => 'Duplicate A', 'type' => 'person', 'global_identity_id' => 102],
            ['id' => 44, 'name' => 'Duplicate B', 'type' => 'person', 'global_identity_id' => 102],
        ]);
        $payload = [
            'dependencies' => [
                'identities' => collect([
                    (object) ['id' => 1, 'type' => 'person', 'global_identity_id' => 101],
                    (object) ['id' => 2, 'type' => 'person', 'global_identity_id' => 102],
                    (object) ['id' => 3, 'type' => 'person', 'global_identity_id' => null],
                ]),
            ],
        ];

        $this->assertSame(
            [1 => 'local-41'],
            $this->service->identityAutoMappings($payload, $this->target)
        );
    }

    private function createTenant(string $name, string $prefix): Tenant
    {
        $id = DB::connection('mysql')->table('tenants')->insertGetId([
            'name' => $name,
            'table_prefix' => $prefix,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return Tenant::query()->findOrFail($id);
    }

    private function user(): User
    {
        $user = new User(['name' => 'Target Admin', 'email' => 'target@example.test']);
        $user->id = 9;

        return $user;
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

        foreach (['global_identities', 'global_places', 'global_keywords', 'global_locations'] as $tableName) {
            $schema->create($tableName, function (Blueprint $table) {
                $table->id();
                $table->string('name')->nullable();
                $table->string('type')->nullable();
            });
        }

        foreach (['source', 'target'] as $prefix) {
            $schema->create("{$prefix}__letters", fn (Blueprint $table) => $table->id());
            $schema->create("{$prefix}__identities", function (Blueprint $table) {
                $table->id();
                $table->string('name');
                $table->string('type');
                $table->unsignedBigInteger('global_identity_id')->nullable();
            });
            $schema->create("{$prefix}__places", function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('name');
                $table->string('additional_name')->nullable();
                $table->string('country')->nullable();
                $table->string('division')->nullable();
                $table->text('note')->nullable();
                $table->double('latitude')->nullable();
                $table->double('longitude')->nullable();
                $table->text('alternative_names')->nullable();
                $table->integer('geoname_id')->nullable();
            });
            $schema->create("{$prefix}__keyword_categories", function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->text('name');
            });
            $schema->create("{$prefix}__keywords", function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->text('name');
                $table->unsignedBigInteger('keyword_category_id')->nullable();
            });
            $schema->create("{$prefix}__locations", function (Blueprint $table) {
                $table->id();
                $table->timestamps();
                $table->string('name');
                $table->string('type');
            });
            $schema->create("{$prefix}__identity_letter", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('identity_id')->nullable();
                $table->unsignedBigInteger('global_identity_id')->nullable();
            });
            $schema->create("{$prefix}__letter_place", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('place_id')->nullable();
                $table->unsignedBigInteger('global_place_id')->nullable();
                $table->string('role')->nullable();
            });
            $schema->create("{$prefix}__keyword_letter", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('keyword_id')->nullable();
                $table->unsignedBigInteger('global_keyword_id')->nullable();
            });
            $schema->create("{$prefix}__manifestations", function (Blueprint $table) {
                $table->id();
                $table->unsignedBigInteger('letter_id');
                $table->unsignedBigInteger('repository_id')->nullable();
                $table->unsignedBigInteger('archive_id')->nullable();
                $table->unsignedBigInteger('collection_id')->nullable();
                $table->unsignedBigInteger('global_repository_id')->nullable();
                $table->unsignedBigInteger('global_archive_id')->nullable();
                $table->unsignedBigInteger('global_collection_id')->nullable();
            });
            $schema->create("{$prefix}__media", function (Blueprint $table) {
                $table->id();
                $table->string('model_type');
                $table->unsignedBigInteger('model_id');
                $table->integer('order_column')->nullable();
            });
        }
    }
}
