<?php

namespace Tests\Unit;

use App\Livewire\ProfessionsTable;
use Tests\TestCase;

class ProfessionsTableFilterTest extends TestCase
{
    public function test_whitespace_only_values_are_ignored_in_either_language_filter(): void
    {
        $table = new TestableProfessionsTable();

        $table->filters['cs'] = ' KNIH ';
        $table->filters['en'] = '   ';
        $this->assertSame([
            ['%knih%'],
            ['%knih%'],
        ], $table->filterBindings());

        $table->filters['cs'] = "\t";
        $table->filters['en'] = ' BOOK ';
        $this->assertSame([
            ['%book%'],
            ['%book%'],
        ], $table->filterBindings());
    }
}

class TestableProfessionsTable extends ProfessionsTable
{
    public function filterBindings(): array
    {
        return [
            $this->getTenantProfessionsQuery()->getQuery()->getBindings(),
            $this->getGlobalProfessionsQuery()->getQuery()->getBindings(),
        ];
    }
}
