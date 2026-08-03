<?php

namespace Tests\Feature;

use App\Exports\LettersExport;
use App\Http\Controllers\LetterController;
use App\Livewire\FiltersButton;
use App\Livewire\FiltersForm;
use App\Livewire\LettersTable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterFilterService;
use App\Services\SearchIdentity;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Livewire\Livewire;
use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\Excel as ExcelWriter;
use PhpOffice\PhpSpreadsheet\IOFactory;
use Tests\TestCase;

class LetterFilterServiceTest extends TestCase
{
    protected string $databasePath;

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-letter-filter-');
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

        // Models in this application already include the tenant prefix in their
        // table names. Keep the test connection itself unprefixed.
        Config::set('database.connections.tenant.prefix', '');
        DB::purge('tenant');

        $this->createSchema();
        $this->seedFixtures();
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

    public function test_local_mentioned_identity_is_filtered_by_scoped_id(): void
    {
        $this->assertSame([1], $this->ids(['mentioned' => ['local-1']]));
    }

    public function test_multiple_identities_inside_one_role_match_any_selected_identity(): void
    {
        $this->assertSame([1, 2], $this->ids([
            'mentioned' => ['local-1', 'local-3'],
        ]));
    }

    public function test_global_identity_matches_local_identities_linked_to_it(): void
    {
        $this->assertSame([1, 2], $this->ids(['mentioned' => ['global-1']]));
        $this->assertSame([1], $this->ids(['mentioned' => ['local-1']]));
    }

    public function test_legacy_identity_name_filters_search_canonical_and_alternative_names(): void
    {
        $this->assertSame([1, 2], $this->ids(['mentioned' => 'Alice']));
        $this->assertSame([1], $this->ids(['mentioned' => 'Alicia']));
    }

    public function test_identity_selector_search_finds_local_alternative_names(): void
    {
        $results = (new SearchIdentity)(['name' => 'Alicia']);

        $this->assertSame([1], $results->pluck('id')->all());
    }

    public function test_match_all_requires_every_active_filter(): void
    {
        $this->assertSame([1], $this->ids([
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['local-1'],
            'status' => 'publish',
        ]));

        $this->assertSame([], $this->ids([
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['local-3'],
            'status' => 'publish',
        ]));
    }

    public function test_match_any_accepts_any_active_filter_and_returns_letters_once(): void
    {
        $this->assertSame([1, 2, 3], $this->ids([
            'match' => LetterFilterService::MATCH_ANY,
            'mentioned' => ['global-1'],
            'signature' => 'SIG-THREE',
        ]));
    }

    public function test_manifestation_filters_use_current_relational_data(): void
    {
        $this->assertSame([1], $this->ids(['signature' => 'sig-one-b']));
        $this->assertSame([1], $this->ids(['repository' => 'Prague Repository']));
        $this->assertSame([2], $this->ids(['archive' => 'Global Archive']));
    }

    public function test_local_and_global_places_and_keywords_are_searchable(): void
    {
        $this->assertSame([1], $this->ids(['origin' => 'Praha']));
        $this->assertSame([2], $this->ids(['destination' => 'Vienna']));
        $this->assertSame([1], $this->ids(['keyword' => 'philosophy']));
        $this->assertSame([2], $this->ids(['keyword' => 'literature']));
    }

    public function test_scalar_text_boolean_and_date_computed_filters(): void
    {
        $this->assertSame([1], $this->ids(['id' => '1, 99']));
        $this->assertSame([1], $this->ids(['content_stripped' => 'alpha']));
        $this->assertSame([2], $this->ids(['abstract' => 'second abstract']));
        $this->assertSame([1], $this->ids(['languages' => 'latin']));
        $this->assertSame([2], $this->ids(['notes_private' => 'private beta']));
        $this->assertSame([1, 3], $this->ids(['approval' => '1']));
        $this->assertSame([2], $this->ids(['after' => '1950-01-01', 'before' => '1959-12-31']));
        $this->assertSame([1], $this->ids(['editor' => 'Editor One']));
        $this->assertSame([1], $this->ids(['media' => '1']));
        $this->assertSame([2, 3], $this->ids(['media' => '0']));
    }

    public function test_normalize_removes_empty_url_values_and_defaults_to_match_all(): void
    {
        $normalized = app(LetterFilterService::class)->normalize([
            'author' => ['', 'local-2', 'local-2'],
            'recipient' => [],
            'status' => ' ',
            'unknown' => 'ignored',
        ]);

        $this->assertSame([
            'match' => LetterFilterService::MATCH_ALL,
            'author' => ['local-2'],
        ], $normalized);
    }

    public function test_filter_url_is_authoritative_over_a_previous_session(): void
    {
        session()->put('lettersTableFilters', ['status' => 'draft']);

        Livewire::withQueryParams([
            'filters' => [
                'match' => LetterFilterService::MATCH_ANY,
                'mentioned' => ['local-1'],
                'status' => 'publish',
            ],
        ])->test(FiltersForm::class)
            ->assertSet('filters.match', LetterFilterService::MATCH_ANY)
            ->assertSet('filters.mentioned', ['local-1'])
            ->assertSet('filters.status', 'publish');
    }

    public function test_empty_filter_url_ignores_filters_from_a_previous_session(): void
    {
        session()->put('lettersTableFilters', [
            'recipient' => ['local-4'],
            'origin' => 'Prague',
        ]);

        Livewire::test(FiltersForm::class)
            ->assertSet('filters', ['match' => LetterFilterService::MATCH_ALL]);

        Livewire::test(FiltersButton::class)
            ->assertSet('activeFilters', []);

        Livewire::test(LettersTable::class)
            ->assertSet('filters', ['match' => LetterFilterService::MATCH_ALL]);

        Auth::login(User::findOrFail(1));
        $view = app(LetterController::class)->index(Request::create('/letters', 'GET'));

        $this->assertSame(
            ['match' => LetterFilterService::MATCH_ALL],
            $view->getData()['letterFilters']
        );
    }

    public function test_identity_selection_and_badge_removal_update_shared_filter_state(): void
    {
        $component = Livewire::test(FiltersForm::class)
            ->call('updateIdentityFilter', 'mentioned', ['local-1', 'local-3'])
            ->assertSet('filters.mentioned', ['local-1', 'local-3'])
            ->assertDispatched('filtersChanged');

        $component
            ->call('removeFilter', ['filterKey' => 'mentioned'])
            ->assertSet('filters', ['match' => LetterFilterService::MATCH_ALL])
            ->assertDispatched('filtersChanged');
    }

    public function test_letter_export_uses_the_same_nested_filters_as_the_table(): void
    {
        $filters = [
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['global-1'],
            'status' => 'publish',
        ];
        $expectedIds = $this->ids($filters);

        Excel::fake();

        $request = Request::create('/letters/export', 'GET', [
            'filters' => $filters,
        ]);
        app(LetterController::class)->export($request);

        Excel::assertDownloaded('letters.xlsx', function (LettersExport $export) use ($expectedIds) {
            $exportedIds = collect(iterator_to_array($export->sheets()[0]->generator()))
                ->pluck(0)
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            return $exportedIds === $expectedIds;
        });
    }

    public function test_loading_export_link_encodes_nested_filters_only_once(): void
    {
        $filters = [
            'match' => LetterFilterService::MATCH_ALL,
            'recipient' => ['global-1', 'local-4'],
            'origin' => 'Prague',
        ];
        $href = route('letters.export', ['filters' => $filters]);
        $html = Blade::render(
            '<x-loading-link :href="$href" id="export-url">Export</x-loading-link>',
            compact('href')
        );

        preg_match('/href="([^"]+)"/', $html, $matches);
        $browserHref = html_entity_decode($matches[1] ?? '');
        parse_str((string) parse_url($browserHref, PHP_URL_QUERY), $query);

        $this->assertStringNotContainsString('&amp;amp;', $html);
        $this->assertSame($filters, $query['filters'] ?? null);
    }

    public function test_filtered_xlsx_has_normalized_sheets_and_preserves_repeated_values(): void
    {
        $filters = [
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['global-1'],
        ];
        $expectedIds = $this->ids($filters);
        $contents = Excel::raw(new LettersExport($filters), ExcelWriter::XLSX);
        $file = tmpfile();

        $this->assertIsResource($file);
        fwrite($file, $contents);
        $path = stream_get_meta_data($file)['uri'];
        $workbook = IOFactory::load($path);

        $this->assertSame([
            'Letters',
            'Identities',
            'Places',
            'Keywords',
            'Manifestations',
            'Related resources',
        ], $workbook->getSheetNames());

        $letters = $workbook->getSheetByName('Letters');
        $actualIds = collect($letters->rangeToArray('A2:A' . $letters->getHighestDataRow()))
            ->flatten()
            ->filter(fn ($id) => $id !== null && $id !== '')
            ->map(fn ($id) => (int) $id)
            ->values()
            ->all();

        $this->assertSame($expectedIds, $actualIds);

        $manifestations = collect($workbook->getSheetByName('Manifestations')->rangeToArray(
            'A2:W' . $workbook->getSheetByName('Manifestations')->getHighestDataRow()
        ));
        $this->assertSame(['SIG-ONE-A', 'SIG-ONE-B', 'SIG-TWO'], $manifestations->pluck(2)->all());
        $this->assertSame([1, 1, 2], $manifestations->pluck(0)->map(fn ($id) => (int) $id)->all());
        $this->assertSame('local', $manifestations[0][9]);
        $this->assertSame('Prague Repository', $manifestations[0][12]);
        $this->assertSame('global', $manifestations[2][13]);
        $this->assertSame('Global Archive', $manifestations[2][16]);

        $identities = collect($workbook->getSheetByName('Identities')->rangeToArray(
            'A2:O' . $workbook->getSheetByName('Identities')->getHighestDataRow()
        ));
        $this->assertSame([1, 1, 1, 2], $identities->pluck(0)->map(fn ($id) => (int) $id)->all());
        $this->assertSame(['mentioned', 'author', 'recipient', 'mentioned'], $identities->pluck(1)->all());
        $this->assertSame(['local', 'local', 'global', 'local'], $identities->pluck(3)->all());
        $this->assertSame('Global Alice', $identities[0][14]);
        $this->assertSame('Direct Global Recipient', $identities[2][6]);

        $places = collect($workbook->getSheetByName('Places')->rangeToArray(
            'A2:H' . $workbook->getSheetByName('Places')->getHighestDataRow()
        ));
        $this->assertSame(['local', 'global'], $places->pluck(3)->all());
        $this->assertSame(['Prague', 'Vienna'], $places->pluck(6)->all());

        $keywords = collect($workbook->getSheetByName('Keywords')->rangeToArray(
            'A2:J' . $workbook->getSheetByName('Keywords')->getHighestDataRow()
        ));
        $this->assertSame(['local', 'global'], $keywords->pluck(1)->all());
        $this->assertSame(['Philosophy', 'Literature'], $keywords->pluck(5)->all());

        $resources = collect($workbook->getSheetByName('Related resources')->rangeToArray(
            'A2:E' . $workbook->getSheetByName('Related resources')->getHighestDataRow()
        ));
        $this->assertCount(2, $resources);
        $this->assertSame(['Source One', 'Source Two'], $resources->pluck(2)->all());
        $this->assertSame([1, 2], $resources->pluck(1)->map(fn ($position) => (int) $position)->all());

        fclose($file);
    }

    protected function ids(array $filters): array
    {
        return app(LetterFilterService::class)
            ->filteredQuery($filters)
            ->orderBy('test-tenant__letters.id')
            ->pluck('test-tenant__letters.id')
            ->map(fn ($id) => (int) $id)
            ->all();
    }

    protected function createSchema(): void
    {
        Schema::connection('tenant')->create('test-tenant__letters', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->nullable();
            $table->timestamps();
            $table->longText('history')->nullable();
            $table->json('copies')->nullable();
            $table->integer('date_year')->nullable();
            $table->integer('date_month')->nullable();
            $table->integer('date_day')->nullable();
            $table->date('date_computed')->nullable();
            $table->string('date_marked')->nullable();
            $table->boolean('date_uncertain')->default(false);
            $table->boolean('date_approximate')->default(false);
            $table->boolean('date_inferred')->default(false);
            $table->boolean('date_is_range')->default(false);
            $table->text('date_note')->nullable();
            $table->longText('content_stripped')->nullable();
            $table->longText('content')->nullable();
            $table->longText('abstract')->nullable();
            $table->string('explicit')->nullable();
            $table->string('incipit')->nullable();
            $table->text('languages')->nullable();
            $table->longText('notes_private')->nullable();
            $table->longText('notes_public')->nullable();
            $table->json('related_resources')->nullable();
            $table->string('status')->nullable();
            $table->boolean('approval')->default(false);
        });

        Schema::connection('tenant')->create('test-tenant__identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->json('alternative_names')->nullable();
            $table->unsignedBigInteger('global_identity_id')->nullable();
            $table->string('birth_year')->nullable();
            $table->string('death_year')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__identity_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('identity_id')->nullable();
            $table->unsignedBigInteger('global_identity_id')->nullable();
            $table->unsignedBigInteger('letter_id');
            $table->string('role');
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
            $table->text('salutation')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__places', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->json('alternative_names')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__letter_place', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('place_id')->nullable();
            $table->unsignedBigInteger('global_place_id')->nullable();
            $table->string('role');
            $table->integer('position')->nullable();
            $table->text('marked')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__keywords', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('name')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__keyword_letter', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('keyword_id')->nullable();
            $table->unsignedBigInteger('global_keyword_id')->nullable();
            $table->unsignedBigInteger('letter_id');
        });

        Schema::connection('tenant')->create('test-tenant__locations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('type');
        });

        Schema::connection('tenant')->create('test-tenant__manifestations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('repository_id')->nullable();
            $table->unsignedBigInteger('archive_id')->nullable();
            $table->unsignedBigInteger('collection_id')->nullable();
            $table->unsignedBigInteger('global_repository_id')->nullable();
            $table->unsignedBigInteger('global_archive_id')->nullable();
            $table->unsignedBigInteger('global_collection_id')->nullable();
            $table->string('signature')->nullable();
            $table->string('type')->nullable();
            $table->string('preservation')->nullable();
            $table->string('copy')->nullable();
            $table->string('l_number')->nullable();
            $table->text('manifestation_notes')->nullable();
            $table->text('location_note')->nullable();
            $table->timestamps();
        });

        Schema::connection('tenant')->create('test-tenant__users', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('role')->nullable();
            $table->string('email')->nullable();
            $table->string('password')->nullable();
        });

        Schema::connection('tenant')->create('test-tenant__letter_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('letter_id');
            $table->unsignedBigInteger('user_id');
        });

        Schema::connection('tenant')->create('test-tenant__media', function (Blueprint $table) {
            $table->id();
            $table->string('model_type');
            $table->unsignedBigInteger('model_id');
        });

        Schema::create('global_identities', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
        });

        Schema::create('global_places', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->json('alternative_names')->nullable();
        });

        Schema::create('global_keywords', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->json('name')->nullable();
        });

        Schema::create('global_locations', function (Blueprint $table) {
            $table->id();
            $table->timestamps();
            $table->string('name');
            $table->string('type');
        });
    }

    protected function seedFixtures(): void
    {
        DB::connection('tenant')->table('test-tenant__letters')->insert([
            ['id' => 1, 'date_computed' => '1940-01-01', 'content_stripped' => 'Alpha content', 'abstract' => '{"en":"First abstract"}', 'languages' => 'Czech;Latin', 'notes_private' => 'Private alpha', 'related_resources' => '[{"title":"Source One","link":"https://example.test/one"},{"title":"Source Two","link":"https://example.test/two"}]', 'status' => 'publish', 'approval' => 1],
            ['id' => 2, 'date_computed' => '1955-06-15', 'content_stripped' => 'Beta content', 'abstract' => '{"en":"Second abstract"}', 'languages' => 'German', 'notes_private' => 'Private beta', 'related_resources' => null, 'status' => 'draft', 'approval' => 0],
            ['id' => 3, 'date_computed' => '1965-12-31', 'content_stripped' => 'Gamma content', 'abstract' => '{"en":"Third abstract"}', 'languages' => 'French', 'notes_private' => 'Private gamma', 'related_resources' => null, 'status' => 'draft', 'approval' => 1],
        ]);

        DB::table('global_identities')->insert([
            ['id' => 1, 'name' => 'Global Alice'],
            ['id' => 2, 'name' => 'Direct Global Recipient'],
        ]);
        DB::connection('tenant')->table('test-tenant__identities')->insert([
            ['id' => 1, 'name' => 'Alice Local One', 'alternative_names' => '["Alicia"]', 'global_identity_id' => 1],
            ['id' => 2, 'name' => 'Author Local', 'alternative_names' => null, 'global_identity_id' => null],
            ['id' => 3, 'name' => 'Alice Local Two', 'alternative_names' => null, 'global_identity_id' => 1],
            ['id' => 4, 'name' => 'Unlinked Alice', 'alternative_names' => null, 'global_identity_id' => null],
        ]);
        DB::connection('tenant')->table('test-tenant__identity_letter')->insert([
            ['identity_id' => 1, 'global_identity_id' => null, 'letter_id' => 1, 'role' => 'mentioned'],
            ['identity_id' => 2, 'global_identity_id' => null, 'letter_id' => 1, 'role' => 'author'],
            ['identity_id' => null, 'global_identity_id' => 2, 'letter_id' => 1, 'role' => 'recipient'],
            ['identity_id' => 3, 'global_identity_id' => null, 'letter_id' => 2, 'role' => 'mentioned'],
            ['identity_id' => 4, 'global_identity_id' => null, 'letter_id' => 3, 'role' => 'recipient'],
        ]);

        DB::connection('tenant')->table('test-tenant__places')->insert(['id' => 1, 'name' => 'Prague', 'alternative_names' => '["Praha"]']);
        DB::table('global_places')->insert(['id' => 1, 'name' => 'Vienna', 'alternative_names' => '["Wien"]']);
        DB::connection('tenant')->table('test-tenant__letter_place')->insert([
            ['letter_id' => 1, 'place_id' => 1, 'global_place_id' => null, 'role' => 'origin'],
            ['letter_id' => 2, 'place_id' => null, 'global_place_id' => 1, 'role' => 'destination'],
        ]);

        DB::connection('tenant')->table('test-tenant__keywords')->insert(['id' => 1, 'name' => '{"en":"Philosophy"}']);
        DB::table('global_keywords')->insert(['id' => 1, 'name' => '{"en":"Literature"}']);
        DB::connection('tenant')->table('test-tenant__keyword_letter')->insert([
            ['letter_id' => 1, 'keyword_id' => 1, 'global_keyword_id' => null],
            ['letter_id' => 2, 'keyword_id' => null, 'global_keyword_id' => 1],
        ]);

        DB::connection('tenant')->table('test-tenant__locations')->insert(['id' => 1, 'name' => 'Prague Repository', 'type' => 'repository']);
        DB::table('global_locations')->insert(['id' => 1, 'name' => 'Global Archive', 'type' => 'archive']);
        DB::connection('tenant')->table('test-tenant__manifestations')->insert([
            ['letter_id' => 1, 'repository_id' => 1, 'global_archive_id' => null, 'signature' => 'SIG-ONE-A'],
            ['letter_id' => 1, 'repository_id' => 1, 'global_archive_id' => null, 'signature' => 'SIG-ONE-B'],
            ['letter_id' => 2, 'repository_id' => null, 'global_archive_id' => 1, 'signature' => 'SIG-TWO'],
            ['letter_id' => 3, 'repository_id' => null, 'global_archive_id' => null, 'signature' => 'SIG-THREE'],
        ]);

        DB::connection('tenant')->table('test-tenant__users')->insert(['id' => 1, 'name' => 'Editor One', 'role' => 'guest']);
        DB::connection('tenant')->table('test-tenant__letter_user')->insert(['letter_id' => 1, 'user_id' => 1]);
        DB::connection('tenant')->table('test-tenant__media')->insert([
            'model_type' => 'App\\Models\\Letter',
            'model_id' => 1,
        ]);
    }
}
