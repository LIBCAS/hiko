<?php

namespace Tests\Unit;

use App\Exports\MetadataDigestWorkbook;
use PHPUnit\Framework\TestCase;

class MetadataDigestWorkbookTest extends TestCase
{
    public function test_it_creates_one_sheet_per_non_empty_scope_group(): void
    {
        $workbook = new MetadataDigestWorkbook([
            'headings' => ['id', 'cs', 'en'],
            'groups' => [
                [
                    'sheet' => 'Global',
                    'records' => [['export' => [3, 'globální', 'global']]],
                ],
                [
                    'sheet' => 'brezina',
                    'records' => [['export' => [7, 'lokální', 'local']]],
                ],
            ],
        ]);

        $sheets = $workbook->sheets();

        $this->assertCount(2, $sheets);
        $this->assertSame('Global', $sheets[0]->title());
        $this->assertSame([[3, 'globální', 'global']], $sheets[0]->array());
        $this->assertSame(['id', 'cs', 'en'], $sheets[0]->headings());
        $this->assertSame('brezina', $sheets[1]->title());
    }
}
