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
        \Tests\Support\NationalityFixtures::create(['global_identities', 'test-tenant__identities']);
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

    public function test_expanded_nationality_listing_and_export_match(): void
    {
        (require database_path('migrations/2026_10_08_000000_create_nationality_search_expansions.php'))->up();
        DB::table('nationality_search_expansions')->insert(['source_nationality_id' => 1, 'target_nationality_id' => 2]);
        foreach (['test-tenant__identity_nationality' => 'identity_id', 'global_identity_nationality' => 'global_identity_id'] as $pivot => $key) {
            $ids = $key === 'identity_id' ? [1, 2] : [101, 102];
            DB::table($pivot)->insert([
                [$key => $ids[0], 'nationality_id' => 1, 'position' => 0],
                [$key => $ids[1], 'nationality_id' => 2, 'position' => 0],
            ]);
        }
        $component = new TestableIdentitiesTable();
        foreach (['direct' => [[1], [101]], 'expanded' => [[1, 2], [101, 102]]] as $mode => [$localIds, $globalIds]) {
            $filters = array_replace($component->filters, ['nationality' => 'Czech', 'nationality_match' => $mode]);
            foreach (['local' => ['test-tenant__identities', $localIds], 'global' => ['global_identities', $globalIds]] as $scope => [$table, $expected]) {
                $query = DB::table($table);
                $component->applyTestFilters($query, $filters, $scope);
                $this->assertSame($expected, $query->orderBy('id')->pluck('id')->all());
            }
            $export = new TestableIdentitiesExport($filters);
            $this->assertSame($localIds, $export->localIds());
            $this->assertSame($globalIds, $export->globalIds());
        }
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

    public function test_global_identity_filter_lists_local_identities_by_assignment(): void
    {
        $component = new TestableIdentitiesTable();

        foreach (['yes' => [1], 'no' => [2], 'all' => [1, 2]] as $filter => $expectedIds) {
            $filters = array_replace($component->filters, ['global_identity' => $filter]);
            $query = DB::table('test-tenant__identities');

            $component->applyTestFilters($query, $filters, 'local');

            $this->assertSame($expectedIds, $query->orderBy('id')->pluck('id')->all());
        }
    }

    public function test_export_global_identity_filter_matches_the_listing(): void
    {
        foreach (['yes' => [1], 'no' => [2], 'all' => [1, 2]] as $filter => $expectedIds) {
            $export = new TestableIdentitiesExport(['global_identity' => $filter]);

            $this->assertSame($expectedIds, $export->localIds());
        }
    }

    public function test_nationality_filters_listing_and_export_for_both_scopes(): void
    {
        Identity::findOrFail(1)->syncNationalities([1, 4]);
        Identity::findOrFail(2)->syncNationalities([5]);
        GlobalIdentity::findOrFail(101)->syncNationalities([5]);
        GlobalIdentity::findOrFail(102)->syncNationalities([4, 1]);

        $component = new TestableIdentitiesTable();

        foreach (
            [
                'zech' => [[1], [102]],
                'CZECH' => [[1], [102]],
                '  cZeCh  ' => [[1], [102]],
                'missing' => [[], []],
                '' => [[1, 2], [101, 102, 103]],
                '   ' => [[1, 2], [101, 102, 103]],
            ] as $search => [$localIds, $globalIds]
        ) {
            $filters = array_replace($component->filters, ['nationality' => $search]);
            $localQuery = DB::table('test-tenant__identities')
                ->leftJoin('global_identities', 'test-tenant__identities.global_identity_id', '=', 'global_identities.id');
            $component->applyTestFilters($localQuery, $filters, 'local');
            $this->assertSame($localIds, $localQuery->orderBy('test-tenant__identities.id')->pluck('test-tenant__identities.id')->all());

            $globalQuery = DB::table('global_identities');
            $component->applyTestFilters($globalQuery, $filters, 'global');
            $this->assertSame($globalIds, $globalQuery->orderBy('id')->pluck('id')->all());

            $export = new TestableIdentitiesExport(['nationality' => $search]);
            $this->assertSame($localIds, $export->localIds());
            $this->assertSame($globalIds, $export->globalIds());
        }
    }

    public function test_nationality_presence_matches_listing_and_export_independently_for_each_scope(): void
    {
        // Local 1 links to global 101 but has no assignments of its own.
        Identity::findOrFail(2)->syncNationalities([1, 4]);
        GlobalIdentity::findOrFail(101)->syncNationalities([1, 4]);
        $component = new TestableIdentitiesTable();

        foreach ([
            ['all', '', [1, 2], [101, 102, 103]],
            ['yes', '', [2], [101]],
            ['no', '', [1], [102, 103]],
            ['yes', 'Czech', [2], [101]],
            ['no', 'Czech', [], []],
        ] as [$presence, $name, $localIds, $globalIds]) {
            $filters = array_replace($component->filters, ['has_nationality' => $presence, 'nationality' => $name]);
            $local = DB::table('test-tenant__identities')
                ->leftJoin('global_identities', 'test-tenant__identities.global_identity_id', '=', 'global_identities.id');
            $component->applyTestFilters($local, $filters, 'local');
            $this->assertSame($localIds, $local->orderBy('test-tenant__identities.id')->pluck('test-tenant__identities.id')->all());
            $global = DB::table('global_identities');
            $component->applyTestFilters($global, $filters, 'global');
            $this->assertSame($globalIds, $global->orderBy('id')->pluck('id')->all());
            $export = new TestableIdentitiesExport($filters);
            $this->assertSame($localIds, $export->localIds());
            $this->assertSame($globalIds, $export->globalIds());
        }
    }

    protected function createSchema(): void
    {
        Schema::connection('tenant')->create('test-tenant__identities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('related_names')->nullable();
            $table->text('nationality')->nullable();
            $table->unsignedBigInteger('global_identity_id')->nullable();
        });

        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->text('related_names')->nullable();
            $table->text('nationality')->nullable();
        });
    }

    protected function seedIdentities(): void
    {
        DB::connection('tenant')->table('test-tenant__identities')->insert([
            [
                'id' => 1,
                'name' => 'Example, Alice',
                'related_names' => '[{"surname":"Ackley","forename":"Alice","general_name_modifier":null}]',
                'global_identity_id' => 101,
            ],
            [
                'id' => 2,
                'name' => 'Example, Helen',
                'related_names' => '[{"surname":"Hackett","forename":"Helen","general_name_modifier":null}]',
                'global_identity_id' => null,
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
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }

    public function globalIds(): array
    {
        return $this->applyGlobalFilters(GlobalIdentity::query())
            ->pluck('id')
            ->map(fn($id) => (int) $id)
            ->sort()
            ->values()
            ->all();
    }
}
