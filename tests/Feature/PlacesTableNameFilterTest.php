<?php

namespace Tests\Feature;

use App\Livewire\PlacesTable;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class PlacesTableNameFilterTest extends TestCase
{
    public function test_name_filter_matches_aliases_without_case_or_accent_sensitivity(): void
    {
        if (DB::connection()->getDriverName() !== 'mysql') {
            $this->markTestSkipped('Requires MySQL to exercise JSON_SEARCH and its actual collation behavior.');
        }

        // Derived tables keep the test read-only, including when run against a local dev database.
        $table = new class extends PlacesTable {
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
                    ['Primaryville', '', 'Exampleland', '', json_encode(['Other alias', 'Áliasville']), $source]
                );
                $this->applyFilters($query, $this->filters);

                return $query;
            }
        };

        foreach (['local', 'global', 'all'] as $source) {
            $table->filters['source'] = $source;
            $expected = $source === 'all' ? ['global', 'local'] : [$source];

            foreach (['Áliasville', 'aliasville', 'ALIASVILLE', 'áLIAS', 'liasv', 'PRIMARYVILLE'] as $term) {
                $table->filters['name'] = $term;
                $results = $table->results();
                sort($results);
                $this->assertSame($expected, $results, "Source {$source}, term {$term}");
            }

            $table->filters['name'] = 'Missingville';
            $this->assertSame([], $table->results());

            // An alias match must still respect the other filters.
            $table->filters['name'] = 'aliasville';
            $table->filters['country'] = 'Differentland';
            $this->assertSame([], $table->results());
            $table->filters['country'] = '';
        }
    }
}
