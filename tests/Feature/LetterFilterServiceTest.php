<?php

namespace Tests\Feature;

use App\Exports\AdvancedLettersExport;
use App\Exports\LettersExport;
use App\Http\Controllers\LetterController;
use App\Livewire\FiltersButton;
use App\Livewire\FiltersForm;
use App\Livewire\LettersTable;
use App\Models\Tenant;
use App\Models\User;
use App\Services\LetterFilterService;
use App\Services\SearchIdentity;
use Illuminate\Support\Carbon;
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
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
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

        Config::set('database.connections.tenant.prefix', '');
        DB::purge('tenant');

        $this->createSchema();
        $this->seedFixtures();
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();

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
            ->call('updateSelection', 'mentioned', ['local-1', 'local-3'])
            ->assertSet('filters', ['match' => LetterFilterService::MATCH_ALL])
            ->assertNotDispatched('filtersChanged')
            ->call('search')
            ->assertSet('filters.mentioned', ['local-1', 'local-3'])
            ->assertDispatched('filtersChanged');

        $component
            ->call('removeFilter', ['filterKey' => 'mentioned'])
            ->assertSet('filters', ['match' => LetterFilterService::MATCH_ALL])
            ->assertDispatched('filtersChanged');
    }

    public function test_letter_export_uses_the_same_nested_filters_as_the_table(): void
    {
        Carbon::setTestNow('2026-08-04 14:25:30');
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

        Excel::assertDownloaded('letters_20260804142530.xlsx', function (LettersExport $export) use ($expectedIds) {
            $lettersSheet = collect($export->sheets())->first(
                fn($sheet) => $sheet->title() === 'Letters'
            );
            $exportedIds = collect(iterator_to_array($lettersSheet->generator()))
                ->pluck(0)
                ->map(fn($id) => (int) $id)
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
            'author_count' => ['operator' => 'eq', 'value' => '0'],
            'date_note' => ['operator' => 'empty'],
            'notes_public' => ['operator' => 'contains', 'value' => 'A & B / 100%_?'],
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

    public function test_advanced_letter_export_uses_the_same_nested_filters_as_the_table(): void
    {
        Carbon::setTestNow('2026-08-04 14:25:30');
        $filters = [
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['global-1'],
        ];
        $expectedIds = $this->ids($filters);

        Excel::fake();
        app(LetterController::class)->exportAdvanced(Request::create('/letters/export/advanced', 'GET', [
            'filters' => $filters,
        ]));

        Excel::assertDownloaded('letters-advanced_20260804142530.xlsx', function (AdvancedLettersExport $export) use ($expectedIds) {
            $exportedIds = collect(iterator_to_array($export->sheets()[0]->generator()))
                ->pluck(0)
                ->filter(fn($id) => $id !== null && $id !== '')
                ->map(fn($id) => (int) $id)
                ->sort()
                ->values()
                ->all();

            return $exportedIds === $expectedIds;
        });
    }

    public function test_filtered_xlsx_has_normalized_sheets_and_preserves_repeated_values(): void
    {
        $filters = [
            'match' => LetterFilterService::MATCH_ALL,
            'mentioned' => ['global-1'],
        ];
        $expectedIds = $this->ids($filters);
        $advancedContents = Excel::raw(new AdvancedLettersExport($filters), ExcelWriter::XLSX);
        $advancedFile = tmpfile();

        $this->assertIsResource($advancedFile);
        fwrite($advancedFile, $advancedContents);
        $advancedPath = stream_get_meta_data($advancedFile)['uri'];
        $advancedWorkbook = IOFactory::load($advancedPath);
        $this->assertSame(['Summary'], $advancedWorkbook->getSheetNames());

        $summary = $advancedWorkbook->getSheetByName('Summary');
        $this->assertSame(1, (int) $summary->getCell('A3')->getValue());
        $this->assertNull($summary->getCell('A4')->getValue());
        $this->assertSame(2, (int) $summary->getCell('A5')->getValue());
        $this->assertArrayHasKey('A3:A4', $summary->getMergeCells());
        $this->assertArrayHasKey('L3:L4', $summary->getMergeCells());
        $this->assertSame('Author Local', $summary->getCell('M3')->getValue());
        $this->assertSame('Direct Global Recipient', $summary->getCell('P3')->getValue());
        $this->assertSame('Alice Local One', $summary->getCell('S3')->getValue());
        $this->assertSame('SIG-ONE-A', $summary->getCell('AB3')->getValue());
        $this->assertSame('SIG-ONE-B', $summary->getCell('AB4')->getValue());
        $this->assertSame('Source One', $summary->getCell('AL3')->getValue());
        $this->assertSame('Source Two', $summary->getCell('AL4')->getValue());
        $this->assertSame(
            Alignment::VERTICAL_CENTER,
            $summary->getStyle('A3')->getAlignment()->getVertical()
        );
        $this->assertSame('FFFFFFFF', $summary->getStyle('A3')->getFill()->getStartColor()->getARGB());
        $this->assertSame('FFF3F4F6', $summary->getStyle('A5')->getFill()->getStartColor()->getARGB());
        $this->assertSame(
            Border::BORDER_MEDIUM,
            $summary->getStyle('D1')->getBorders()->getRight()->getBorderStyle()
        );
        $this->assertSame(
            Border::BORDER_MEDIUM,
            $summary->getStyle('A4')->getBorders()->getBottom()->getBorderStyle()
        );

        fclose($advancedFile);

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
            ->filter(fn($id) => $id !== null && $id !== '')
            ->map(fn($id) => (int) $id)
            ->values()
            ->all();

        $this->assertSame($expectedIds, $actualIds);

        $manifestations = collect($workbook->getSheetByName('Manifestations')->rangeToArray(
            'A2:W' . $workbook->getSheetByName('Manifestations')->getHighestDataRow()
        ));
        $this->assertSame(['SIG-ONE-A', 'SIG-ONE-B', 'SIG-TWO'], $manifestations->pluck(2)->all());
        $this->assertSame([1, 1, 2], $manifestations->pluck(0)->map(fn($id) => (int) $id)->all());
        $this->assertSame('local', $manifestations[0][9]);
        $this->assertSame('Prague Repository', $manifestations[0][12]);
        $this->assertSame('global', $manifestations[2][13]);
        $this->assertSame('Global Archive', $manifestations[2][16]);

        $identities = collect($workbook->getSheetByName('Identities')->rangeToArray(
            'A2:O' . $workbook->getSheetByName('Identities')->getHighestDataRow()
        ));
        $this->assertSame([1, 1, 1, 2], $identities->pluck(0)->map(fn($id) => (int) $id)->all());
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
        $this->assertSame([1, 2], $resources->pluck(1)->map(fn($position) => (int) $position)->all());

        fclose($file);
    }

    public function test_partial_dates_ranges_and_period_modes_use_possible_intervals(): void
    {
        $letters = DB::connection('tenant')->table('test-tenant__letters');
        (clone $letters)->where('id', 1)->update(['date_year' => 1808, 'date_is_range' => 1, 'range_year' => 1815]);
        (clone $letters)->where('id', 2)->update(['date_year' => 1805]);
        (clone $letters)->where('id', 3)->update(['date_year' => null]);
        $period = ['after' => '1800-01-01', 'before' => '1810-12-31'];
        $this->assertSame([1, 2], $this->ids($period));
        $this->assertSame([2], $this->ids($period + ['date_match' => 'within']));
        $this->assertSame([2], $this->ids(['after' => '1805-06-01', 'before' => '1805-06-30']));
        $this->assertSame([], $this->ids(['after' => '1805-06-01', 'before' => '1805-06-30', 'date_match' => 'within']));
        $this->assertSame([2, 3], $this->ids(['match' => 'any', 'after' => '1805-06-01', 'before' => '1805-06-30', 'id' => '3']));
    }

    public function test_date_components_include_legacy_zeros_and_only_ranges_for_second_date(): void
    {
        $letters = DB::connection('tenant')->table('test-tenant__letters');
        (clone $letters)->where('id', 1)->update(['date_year' => 0, 'date_month' => 0, 'date_day' => 0]);
        (clone $letters)->where('id', 2)->update(['date_month' => 5, 'date_day' => 12, 'date_is_range' => 1, 'range_year' => 1956]);
        $this->assertSame([1], $this->ids(['date_year' => ['operator' => 'empty']]));
        $this->assertSame([1, 3], $this->ids(['date_month' => ['operator' => 'empty']]));
        $this->assertSame([2], $this->ids(['date_month' => ['operator' => 'eq', 'value' => 5]]));
        $this->assertSame([2], $this->ids(['range_month' => ['operator' => 'empty']]));
        $this->assertSame([2], $this->ids(['range_year' => ['operator' => 'eq', 'value' => 1956]]));
    }

    public function test_month_upper_bounds_respect_leap_years_and_ranges_without_known_end(): void
    {
        $letters = DB::connection('tenant')->table('test-tenant__letters');
        (clone $letters)->where('id', 1)->update(['date_year' => 1900, 'date_month' => 2]);
        (clone $letters)->where('id', 2)->update(['date_year' => 2000, 'date_month' => 2]);
        (clone $letters)->where('id', 3)->update(['date_year' => 1800, 'date_is_range' => 1]);
        $this->assertSame([2, 3], $this->ids(['after' => '2000-02-29', 'before' => '2000-02-29']));
        $this->assertSame([1, 3], $this->ids(['after' => '1900-02-28', 'before' => '1900-02-28']));
        $this->assertSame([], $this->ids(['before' => '1900-01-01', 'date_match' => 'within']));
    }

    public function test_assignment_counts_include_zero_and_do_not_collapse_linked_local_identities(): void
    {
        DB::connection('tenant')->table('test-tenant__identity_letter')->insert([
            'letter_id' => 1,
            'identity_id' => 3,
            'role' => 'mentioned',
        ]);
        $this->assertSame([2, 3], $this->ids(['author_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([1], $this->ids(['mentioned_count' => ['operator' => 'gte', 'value' => 2]]));
        $this->assertSame([3], $this->ids(['mentioned_count' => ['operator' => 'lte', 'value' => 0]]));
        $this->assertSame([2, 3], $this->ids(['origin_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([1, 3], $this->ids(['destination_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([3], $this->ids(['keyword_count' => ['operator' => 'eq', 'value' => 0]]));
    }

    public function test_assignment_selection_and_marked_name_must_match_the_same_assignment(): void
    {
        $pivot = DB::connection('tenant')->table('test-tenant__identity_letter');
        (clone $pivot)->where('letter_id', 1)->where('role', 'author')->update(['marked' => 'Scribe Alpha']);
        $pivot->insert(['letter_id' => 1, 'identity_id' => 4, 'role' => 'author', 'marked' => 'Scribe Beta']);
        $this->assertSame([], $this->ids(['author' => ['local-2'], 'author_marked' => 'Beta']));
        $this->assertSame([1], $this->ids(['author' => ['local-4'], 'author_marked' => 'Beta']));
        $this->assertSame([1], $this->ids(['author' => ['local-2', 'local-4'], 'author_match' => 'all']));
        $this->assertSame([], $this->ids(['author' => ['local-2', 'local-1'], 'author_match' => 'all']));
        $this->assertSame([2, 3], $this->ids(['author_marked' => ['operator' => 'empty']]));
        $this->assertSame([1], $this->ids(['match' => 'any', 'author' => ['local-2'], 'author_marked' => 'unmatched']));
    }

    public function test_local_and_global_place_and_keyword_multiselects_support_any_and_all(): void
    {
        DB::connection('tenant')->table('test-tenant__letter_place')->insert([
            'letter_id' => 1,
            'global_place_id' => 1,
            'role' => 'origin',
            'marked' => 'Old Town',
        ]);
        DB::connection('tenant')->table('test-tenant__keyword_letter')->insert(['letter_id' => 1, 'global_keyword_id' => 1]);
        $this->assertSame([1], $this->ids(['origin' => ['local-1', 'global-1'], 'origin_match' => 'all']));
        $this->assertSame([1, 2], $this->ids(['keyword' => ['local-1', 'global-1']]));
        $this->assertSame([1], $this->ids(['keyword' => ['local-1', 'global-1'], 'keyword_match' => 'all']));
        $this->assertSame([], $this->ids(['origin' => ['local-1'], 'origin_marked' => 'Old Town']));
        $this->assertSame([1], $this->ids(['origin' => ['global-1'], 'origin_marked' => 'Old Town']));
    }

    public function test_languages_are_exact_tokens_and_have_counts(): void
    {
        $this->assertSame([1], $this->ids(['languages' => ['Czech', 'Latin'], 'languages_match' => 'all']));
        $this->assertSame([1, 2], $this->ids(['languages' => ['Latin', 'German']]));
        $this->assertSame([], $this->ids(['languages' => ['Lat']]));
        $this->assertSame([1], $this->ids(['languages_count' => ['operator' => 'eq', 'value' => 2]]));
        DB::connection('tenant')->table('test-tenant__letters')->where('id', 1)->update(['languages' => 'Czech; Latin;;']);
        $this->assertSame([1], $this->ids(['languages' => ['Latin']]));
        $this->assertSame([1], $this->ids(['languages_count' => ['operator' => 'eq', 'value' => 2]]));
        DB::connection('tenant')->table('test-tenant__letters')->where('id', 3)->update(['languages' => '']);
        $this->assertSame([3], $this->ids(['languages_count' => ['operator' => 'eq', 'value' => 0]]));
    }

    public function test_manifestation_conditions_match_one_record_and_empty_checks_cover_all_records(): void
    {
        $manifestations = DB::connection('tenant')->table('test-tenant__manifestations');
        (clone $manifestations)->where('signature', 'SIG-ONE-A')->update(['type' => 'letter', 'preservation' => 'original', 'copy' => 'handwritten', 'manifestation_notes' => 'Edited note']);
        (clone $manifestations)->where('signature', 'SIG-ONE-B')->update(['repository_id' => null, 'type' => 'postcard', 'preservation' => 'copy']);
        $this->assertSame([1], $this->ids(['signature' => 'ONE-B']));
        $this->assertSame([], $this->ids(['signature' => 'ONE-B', 'repository' => ['local-1']]));
        $this->assertSame([1], $this->ids(['signature' => 'ONE-A', 'repository' => ['local-1'], 'type' => ['letter', 'postcard'], 'copy' => ['handwritten']]));
        $this->assertSame([2, 3], $this->ids(['manifestation_notes' => ['operator' => 'empty']]));
        $this->assertSame([1], $this->ids(['manifestations_count' => ['operator' => 'eq', 'value' => 2]]));
        (clone $manifestations)->where('letter_id', 3)->delete();
        $this->assertSame([3], $this->ids(['manifestations_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([2, 3], $this->ids(['manifestation_notes' => ['operator' => 'empty']]));
    }

    public function test_resource_counts_and_text_conditions_use_the_same_resource(): void
    {
        $this->assertSame([1], $this->ids(['related_resources_count' => ['operator' => 'eq', 'value' => 2]]));
        $this->assertSame([2, 3], $this->ids(['related_resources_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([1], $this->ids(['resource_title' => 'One', 'resource_link' => '/one']));
        $this->assertSame([], $this->ids(['resource_title' => 'One', 'resource_link' => '/two']));
        $this->assertSame([2, 3], $this->ids(['resource_link' => ['operator' => 'empty']]));
        $this->assertSame([1], $this->ids(['resource_link' => ['operator' => 'not_empty']]));
    }

    public function test_translated_abstracts_and_text_empty_checks_search_values_and_literal_wildcards(): void
    {
        $letters = DB::connection('tenant')->table('test-tenant__letters');
        (clone $letters)->where('id', 1)->update(['abstract' => '{"cs":"český abstrakt", "en":null}', 'notes_public' => 'A 100% _literal_ note']);
        (clone $letters)->where('id', 2)->update(['abstract' => '{"en":"English abstract"}', 'notes_public' => '100X']);
        $this->assertSame([1], $this->ids(['abstract_cs' => 'Český']));
        $this->assertSame([2, 3], $this->ids(['abstract_cs' => ['operator' => 'empty']]));
        $this->assertSame([1], $this->ids(['abstract_en' => ['operator' => 'empty']]));
        (clone $letters)->where('id', 1)->update(['abstract' => '{"en":"null"}']);
        $this->assertSame([1], $this->ids(['abstract_en' => 'null']));
        $this->assertSame([], $this->ids(['abstract_en' => ['operator' => 'empty']]));
        $this->assertSame([1], $this->ids(['notes_public' => '100%']));
        $this->assertSame([1], $this->ids(['notes_public' => '_literal_']));
        $this->assertSame([3], $this->ids(['notes_public' => ['operator' => 'empty']]));
        $letters->where('id', 3)->update(['notes_public' => " \n\t\r "]);
        $this->assertSame([3], $this->ids(['notes_public' => ['operator' => 'empty']]));
    }

    public function test_workflow_checkboxes_and_inference_flags_combine_with_top_level_modes(): void
    {
        DB::connection('tenant')->table('test-tenant__letters')->where('id', 1)->update(['author_inferred' => 1, 'date_uncertain' => 1]);
        $this->assertSame([1, 2, 3], $this->ids(['approval' => ['0', '1'], 'status' => ['draft', 'publish']]));
        $this->assertSame([2], $this->ids(['approval' => ['0'], 'author_inferred' => '0']));
        $this->assertSame([1], $this->ids(['author_inferred' => '1', 'date_uncertain' => '1']));
        $this->assertSame([2, 3], $this->ids(['date_uncertain' => '0']));
    }

    public function test_cancel_reset_and_validation_do_not_apply_unsubmitted_edits(): void
    {
        $component = Livewire::withQueryParams(['filters' => ['status' => 'publish']])->test(FiltersForm::class)
            ->call('openFilters')
            ->assertDispatched('letter-filters-ready')
            ->set('draft.author_count.value', 0)
            ->assertNotDispatched('filtersChanged')
            ->call('cancel')
            ->assertSet('draft.author_count.value', '')
            ->assertSet('filters.status', 'publish')
            ->call('resetFilters')
            ->assertSet('draft.status', [])
            ->assertSet('filters.status', 'publish')
            ->assertNotDispatched('filtersChanged')
            ->set('draft.author_count.value', -1)
            ->call('search')
            ->assertHasErrors('draft.author_count.value')
            ->assertSet('filters.status', 'publish');
        $component->set('draft.author_count.value', 0)->call('search')
            ->assertHasNoErrors()
            ->assertSet('filters', ['match' => 'all', 'author_count' => ['operator' => 'eq', 'value' => 0]])
            ->assertDispatched('letter-filters-applied');
    }

    public function test_all_letter_text_fields_and_flags_are_filterable(): void
    {
        $textFields = [
            'date_marked',
            'date_note',
            'author_note',
            'recipient_note',
            'origin_note',
            'destination_note',
            'incipit',
            'explicit',
            'people_mentioned_note',
            'notes_private',
            'notes_public',
            'copyright',
            'content_stripped'
        ];
        $flags = [
            'date_uncertain',
            'date_approximate',
            'date_inferred',
            'date_is_range',
            'author_inferred',
            'author_uncertain',
            'recipient_inferred',
            'recipient_uncertain',
            'origin_inferred',
            'origin_uncertain',
            'destination_inferred',
            'destination_uncertain'
        ];
        DB::connection('tenant')->table('test-tenant__letters')->where('id', 1)->update(
            array_fill_keys($textFields, 'Synthetic search value') + array_fill_keys($flags, 1)
        );
        foreach ($textFields as $field) {
            $this->assertSame([1], $this->ids([$field => ['operator' => 'contains', 'value' => 'search value']]), $field);
        }
        foreach ($flags as $field) {
            $this->assertSame([1], $this->ids([$field => '1']), $field);
            $this->assertSame([2, 3], $this->ids([$field => '0']), $field);
        }
        foreach (['manifestation_notes', 'l_number', 'location_note'] as $field) {
            DB::connection('tenant')->table('test-tenant__manifestations')->where('signature', 'SIG-ONE-A')->update([$field => 'Synthetic search value']);
            $this->assertSame([1], $this->ids([$field => 'search value']), $field);
        }
        // The first fixture has a legacy direct-global recipient assignment; it still counts as one record.
        $this->assertSame([1, 3], $this->ids(['recipient_count' => ['operator' => 'eq', 'value' => 1]]));
    }

    public function test_typed_url_normalization_rejects_malformed_values_and_preserves_zero(): void
    {
        $normalized = app(LetterFilterService::class)->normalize([
            'author_count' => ['operator' => 'eq', 'value' => '0'],
            'notes_public' => ['operator' => 'empty', 'value' => 'discarded'],
            'approval' => ['0', '1', 'invalid'],
            'author' => [['nested' => 'bad'], 'local-2', 'local-2'],
            'date_year' => ['operator' => ['unexpected'], 'value' => 1800],
            'date_month' => ['operator' => 'eq', 'value' => 13],
            'after' => '1800-02-30',
            'before' => ['malformed'],
            'origin_match' => 'all',
            'unknown' => 'ignored',
        ]);
        $this->assertSame([
            'match' => 'all',
            'author_count' => ['operator' => 'eq', 'value' => 0],
            'author' => ['local-2'],
            'notes_public' => ['operator' => 'empty'],
            'approval' => ['0', '1'],
        ], $normalized);
    }

    public function test_applying_an_unrelated_draft_change_preserves_legacy_language_substring_urls(): void
    {
        $component = Livewire::withQueryParams(['filters' => ['languages' => 'lat']])->test(FiltersForm::class)
            ->set('draft.date_note.operator', 'empty')
            ->call('search')
            ->assertSet('filters.languages', 'lat');
        $this->assertSame([1], $this->ids(['languages' => $component->get('filters.languages')]));
        $component->call('updateSelection', 'languages', ['Latin'])->call('search')
            ->assertSet('filters.languages', ['Latin']);
    }

    public function test_filter_lookup_uses_scoped_values_and_is_available_to_metadata_viewers(): void
    {
        $options = app(\App\Services\LetterFilterOptions::class);
        $this->assertSame(['local-1', 'local-3'], array_column($options->options('author', search: 'Alice Local'), 'value'));
        $this->assertSame(['global-1', 'local-1'], array_column($options->options('origin', ['global-1', 'local-1']), 'value'));
        $this->assertSame(['local-1'], array_column($options->options('repository', search: 'Prague'), 'value'));
        $this->assertSame([], $options->options('collection', search: 'Prague'));
        $middleware = app('router')->getRoutes()->getByName('ajax.letter-filter-options')->gatherMiddleware();
        $this->assertContains('can:view-metadata', $middleware);
        $this->assertNotContains('can:manage-metadata', $middleware);
    }

    public function test_empty_salutation_is_checked_across_all_recipients_even_with_a_marked_name_condition(): void
    {
        DB::connection('tenant')->table('test-tenant__identity_letter')
            ->where('letter_id', 1)->where('role', 'recipient')->update(['marked' => 'Selected name', 'salutation' => null]);
        DB::connection('tenant')->table('test-tenant__identity_letter')->insert([
            'letter_id' => 1,
            'identity_id' => 2,
            'role' => 'recipient',
            'marked' => 'Other name',
            'salutation' => 'Dear recipient',
        ]);
        $this->assertSame([], $this->ids([
            'recipient_marked' => ['operator' => 'contains', 'value' => 'Selected name'],
            'recipient_salutation' => ['operator' => 'empty'],
        ]));
    }

    protected function ids(array $filters): array
    {
        return app(LetterFilterService::class)
            ->filteredQuery($filters)
            ->orderBy('test-tenant__letters.id')
            ->pluck('test-tenant__letters.id')
            ->map(fn($id) => (int) $id)
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
            $table->integer('range_year')->nullable();
            $table->integer('range_month')->nullable();
            $table->integer('range_day')->nullable();
            foreach (['author', 'recipient', 'origin', 'destination'] as $role) {
                $table->boolean($role . '_inferred')->default(false);
                $table->boolean($role . '_uncertain')->default(false);
                $table->text($role . '_note')->nullable();
            }
            $table->text('people_mentioned_note')->nullable();
            $table->text('copyright')->nullable();
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
            $table->json('alternative_names')->nullable();
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
            ['id' => 1, 'date_year' => 1940, 'date_computed' => '1940-01-01', 'content_stripped' => 'Alpha content', 'abstract' => '{"en":"First abstract"}', 'languages' => 'Czech;Latin', 'notes_private' => 'Private alpha', 'related_resources' => '[{"title":"Source One","link":"https://example.test/one"},{"title":"Source Two","link":"https://example.test/two"}]', 'status' => 'publish', 'approval' => 1],
            ['id' => 2, 'date_year' => 1955, 'date_computed' => '1955-06-15', 'content_stripped' => 'Beta content', 'abstract' => '{"en":"Second abstract"}', 'languages' => 'German', 'notes_private' => 'Private beta', 'related_resources' => null, 'status' => 'draft', 'approval' => 0],
            ['id' => 3, 'date_year' => 1965, 'date_computed' => '1965-12-31', 'content_stripped' => 'Gamma content', 'abstract' => '{"en":"Third abstract"}', 'languages' => 'French', 'notes_private' => 'Private gamma', 'related_resources' => null, 'status' => 'draft', 'approval' => 1],
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
