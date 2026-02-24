<?php

namespace Tests\Unit;

use App\Helpers\DateHelper;
use PHPUnit\Framework\TestCase;

/**
 * Testing date bounds derivation using DateHelper class
 */
class DateBoundsTest extends TestCase
{
    /** @test */
    public function start_bound_for_y_only_is_y_01_01()
    {
        $dt = DateHelper::deriveStartBoundDate(1950, null, null);
        $this->assertSame('1950-01-01', $dt->toDateString());
    }

    /** @test */
    public function start_bound_for_y_and_m_is_first_of_month()
    {
        $dt = DateHelper::deriveStartBoundDate(1950, 5, null);
        $this->assertSame('1950-05-01', $dt->toDateString());
    }

    /** @test */
    public function end_bound_for_y_only_is_y_12_31()
    {
        $dt = DateHelper::deriveEndBoundDate(1950, null, null);
        $this->assertSame('1950-12-31', $dt->toDateString());
    }

    /** @test */
    public function end_bound_for_y_and_m_is_last_of_month()
    {
        $dt = DateHelper::deriveEndBoundDate(1950, 2, null);
        $this->assertSame('1950-02-28', $dt->toDateString());
    }

    /** @test */
    public function null_year_returns_null_bounds()
    {
        $this->assertNull(DateHelper::deriveStartBoundDate(null, 2, 1));
        $this->assertNull(DateHelper::deriveEndBoundDate(null, 2, 1));
    }
}
