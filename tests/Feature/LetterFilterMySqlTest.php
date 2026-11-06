<?php

namespace Tests\Feature;

use App\Models\Letter;
use App\Services\LetterFilterService;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * Tests the expanded letter filters' MySQL JSON_TABLE queries, which SQLite cannot verify. Guards against the language selector returning zero matches despite matching records. Uses read-only synthetic derived tables; skips unless MySQL is configured.
 */
class LetterFilterMySqlTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL to exercise correlated JSON_TABLE filtering.');
        }
    }

    // Selected languages must match complete names, with Any/All applied within the selector
    public function test_language_selection_matches_whole_tokens_and_supports_any_and_all(): void
    {
        $this->assertSame([1, 3], $this->ids(['languages' => ['German']]));
        $this->assertSame([1, 2, 3], $this->ids(['languages' => ['German', 'Czech']]));
        $this->assertSame([3], $this->ids(['languages' => ['German', 'Latin'], 'languages_match' => 'all']));
        $this->assertSame([], $this->ids(['languages' => ['Ger']]));
    }

    // Null, blank values and empty semicolon-separated tokens must not count as languages
    public function test_language_counts_ignore_empty_tokens_and_accept_zero(): void
    {
        $this->assertSame([4, 5], $this->ids(['languages_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([3], $this->ids(['languages_count' => ['operator' => 'eq', 'value' => 2]]));
    }

    // Title and URL must match the same resource; empty means no resource has a filled URL. Also verifies resource counts, including letters with no resources.
    public function test_related_resource_filters_match_one_resource_and_check_emptiness_across_all_resources(): void
    {
        $this->assertSame([1, 3], $this->ids(['resource_title' => ['operator' => 'contains', 'value' => 'Source']]));
        $this->assertSame([1], $this->ids(['resource_link' => ['operator' => 'contains', 'value' => '/alpha']]));
        $this->assertSame([], $this->ids([
            'resource_title' => ['operator' => 'contains', 'value' => 'Beta'],
            'resource_link' => ['operator' => 'contains', 'value' => '/alpha'],
        ]));
        $this->assertSame([2, 4, 5], $this->ids(['resource_link' => ['operator' => 'empty']]));
        $this->assertSame([1, 3], $this->ids(['resource_link' => ['operator' => 'not_empty']]));
        $this->assertSame([2, 4], $this->ids(['related_resources_count' => ['operator' => 'eq', 'value' => 0]]));
        $this->assertSame([1], $this->ids(['related_resources_count' => ['operator' => 'eq', 'value' => 2]]));
    }

    // Applies the app's letter filters to synthetic fixtures and returns matching IDs
    protected function ids(array $filters): array
    {
        $query = $this->fixtureQuery();
        app(LetterFilterService::class)->apply($query, $filters, 'filter_test__');

        return $query->orderBy('filter_test__letters.id')
            ->pluck('filter_test__letters.id')->map(fn ($id) => (int) $id)->all();
    }

    // Builds SELECT-only fixtures without creating tables or modifying tenant records
    protected function fixtureQuery(): Builder
    {
        $fixtures = [
            [1, 'German', [
                ['title' => 'Source Alpha', 'link' => 'https://example.test/alpha'],
                ['title' => 'Source Beta', 'link' => null],
            ]],
            [2, 'Czech', []],
            [3, 'German; Latin;;', [['title' => 'Source Gamma', 'link' => 'https://example.test/gamma']]],
            [4, null, null],
            [5, ' ; ', [['title' => 'Blank link', 'link' => " \n\t"]]],
        ];
        $selects = [];
        $bindings = [];

        foreach ($fixtures as [$id, $languages, $resources]) {
            $selects[] = 'SELECT ? AS id,
                CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci AS languages,
                CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin AS related_resources';
            array_push($bindings, $id, $languages, $resources === null ? null : json_encode($resources));
        }

        $model = (new Letter())->setConnection(config('database.default'))->setTable('filter_test__letters');

        $query = $model->newQuery();
        $query->getQuery()
            ->fromRaw('(' . implode(' UNION ALL ', $selects) . ') AS filter_test__letters', $bindings)
            ->select('filter_test__letters.id');

        return $query;
    }
}
