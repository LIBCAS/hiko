<?php

namespace Tests\Feature;

use App\Livewire\PlacesTable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlacesTableNameFilterTest extends TestCase
{
    public function test_name_filter_normalizes_whitespace_and_matches_alternative_names_without_case_or_accent_sensitivity(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL to exercise JSON_SEARCH and its actual collation behavior.');
        }

        // Derived tables keep the test read-only, including when run against a local dev database.
        $table = new class extends PlacesTable {
            public bool $emptyNames = false;

            public function results(): array
            {
                return $this->findPlaces()->getCollection()->pluck('source')->all();
            }

            protected function getLocalPlacesQuery()
            {
                return $this->fixtureQuery('local');
            }

            protected function getGlobalPlacesQuery()
            {
                return $this->fixtureQuery('global');
            }

            private function fixtureQuery(string $source)
            {
                $query = DB::query()->fromRaw(
                    '(SELECT 1 AS id, CONVERT(? USING utf8mb4) COLLATE utf8mb4_unicode_ci AS name,
                        ? AS division, ? AS country, ? AS additional_name,
                        CONVERT(? USING utf8mb4) COLLATE utf8mb4_bin AS alternative_names,
                        ? AS source) AS fixture',
                    $this->emptyNames
                        ? [null, null, null, null, null, $source]
                        : ['Primaryville District', 'Example Province', 'Exampleland', 'Additional Borough',
                            json_encode(['Other alternative name', 'Áliasville', 'An Éxampletown']), $source]
                );
                $this->applyFilters($query, $this->filters);

                return $query;
            }
        };

        foreach (['local', 'global', 'all'] as $source) {
            $table->filters['source'] = $source;
            $expected = $source === 'all' ? ['global', 'local'] : [$source];

            foreach (['Áliasville', 'aliasville', 'ALIASVILLE', 'áLIAS', 'liasv', 'PRIMARYVILLE',
                'an   e', ' an e ', "an\t\ne", "\u{00A0}an\u{00A0}\u{2003}e\u{2003}",
                ' primaryville   d ', "Example\tProvince", 'Additional   Borough'] as $term) {
                $table->filters['name'] = $term;
                $results = $table->results();
                sort($results);
                $this->assertSame($expected, $results, "Source {$source}, term {$term}");
            }

            $table->filters['name'] = 'Missingville';
            $this->assertSame([], $table->results());

            // An alternative-name match must still respect the other filters.
            $table->filters['name'] = 'aliasville';
            $table->filters['country'] = 'Differentland';
            $this->assertSame([], $table->results());
            $table->filters['country'] = '';

            // NULL text fields distinguish no filter from an accidental LIKE '%%'.
            $table->emptyNames = true;
            foreach (['', '   ', "\t\r\n", "\u{00A0}\u{2003}"] as $term) {
                $table->filters['name'] = $term;
                $results = $table->results();
                sort($results);
                $this->assertSame($expected, $results, 'Blank input must not add a name condition');
            }
            $table->filters['country'] = 'Differentland';
            $this->assertSame([], $table->results(), 'Other filters still apply with blank name input');
            $table->filters['country'] = '';
            $table->emptyNames = false;
        }
    }
}
