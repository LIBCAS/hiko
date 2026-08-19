<?php

namespace Tests\Feature;

use App\Exports\IdentitiesExport;
use App\Livewire\IdentitiesTable;
use App\Models\GlobalIdentity;
use App\Models\Identity;
use App\Models\Tenant;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class IdentityRelatedNamesFilterTest extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-identity-filter-');
        $connection = array_merge(config('database.connections.sqlite'), [
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        Config::set('database.connections.sqlite', $connection);
        Config::set('database.connections.tenant', $connection);
        DB::purge('sqlite');
        DB::purge('tenant');

        tenancy()->initialize(new Tenant([
            'id' => 1,
            'table_prefix' => 'test-tenant',
        ]));

        Config::set('database.connections.tenant.prefix', '');
        DB::purge('tenant');

        DB::statement('PRAGMA case_sensitive_like = ON');
        DB::connection('tenant')->statement('PRAGMA case_sensitive_like = ON');

        $this->createSchema();
        $this->seedIdentities();
    }

    protected function tearDown(): void
    {
        if (isset($this->databasePath)) {
            tenancy()->end();
            DB::disconnect('sqlite');
            DB::disconnect('tenant');
            @unlink($this->databasePath);
        }

        parent::tearDown();
    }

    public function test_listing_filter_is_case_insensitive_for_local_and_global_related_names(): void
    {
        $component = new TestableIdentitiesTable();

        foreach (['ack', 'Ack', 'ACK'] as $search) {
            $filters = array_replace($component->filters, ['related_names' => $search]);

            $localQuery = DB::table('test-tenant__identities');
            $component->applyTestFilters($localQuery, $filters, 'local');

            $globalQuery = DB::table('global_identities');
            $component->applyTestFilters($globalQuery, $filters, 'global');

            $this->assertSame([1, 2], $localQuery->orderBy('id')->pluck('id')->all());
            $this->assertSame([101, 102, 103], $globalQuery->orderBy('id')->pluck('id')->all());
        }
    }

    public function test_export_filter_uses_the_same_case_insensitive_matching(): void
    {
        foreach (['ack', 'Ack', 'ACK'] as $search) {
            $export = new TestableIdentitiesExport(['related_names' => $search]);

            $this->assertSame([1, 2], $export->localIds());
            $this->assertSame([101, 102, 103], $export->globalIds());
        }
    }

    protected function createSchema(): void
    {
        Schema::connection('tenant')->create('test-tenant__identities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('related_names')->nullable();
        });

        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('related_names')->nullable();
        });
    }

    protected function seedIdentities(): void
    {
        DB::connection('tenant')->table('test-tenant__identities')->insert([
            [
                'id' => 1,
                'name' => 'Example, Alice',
                'related_names' => '[{"surname":"Ackley","forename":"Alice","general_name_modifier":null}]',
            ],
            [
                'id' => 2,
                'name' => 'Example, Helen',
                'related_names' => '[{"surname":"Hackett","forename":"Helen","general_name_modifier":null}]',
            ],
        ]);

        DB::table('global_identities')->insert([
            [
                'id' => 101,
                'name' => 'Global Example One',
                'related_names' => '[{"surname":"Ackley","forename":"Alex","general_name_modifier":null}]',
            ],
            [
                'id' => 102,
                'name' => 'Global Example Two',
                'related_names' => '[{"surname":"Hackett","forename":"Harriet","general_name_modifier":null}]',
            ],
            [
                'id' => 103,
                'name' => 'Global Example Three',
                'related_names' => '[{"surname":"Noback","forename":"Nora","general_name_modifier":null}]',
            ],
        ]);
    }
}

class TestableIdentitiesTable extends IdentitiesTable
{
    public function applyTestFilters(QueryBuilder $query, array $filters, string $scope): void
    {
        $this->applyFilters($query, $filters, $scope);
    }
}

class TestableIdentitiesExport extends IdentitiesExport
{
    public function localIds(): array
    {
        return $this->applyLocalFilters(Identity::query())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    public function globalIds(): array
    {
        return $this->applyGlobalFilters(GlobalIdentity::query())
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }
}
