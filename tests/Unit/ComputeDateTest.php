<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Testing the computeDate() helper function
 */
class ComputeDateTest extends TestCase
{
    private function make($y = null, $m = null, $d = null): object
    {
        return (object) [
            'date_year'  => $y,
            'date_month' => $m,
            'date_day'   => $d,
        ];
    }

    /** @test */
    public function year_only_defaults_to_jan_1()
    {
        $l = $this->make(1950, null, null);
        $this->assertSame('1950-01-01', computeDate($l));
    }

    /** @test */
    public function year_and_month_default_day_to_1()
    {
        $l = $this->make(1950, 5, null);
        $this->assertSame('1950-05-01', computeDate($l));
    }

    /** @test */
    public function full_valid_date_is_used_as_is()
    {
        $l = $this->make(1952, 2, 29);
        $this->assertSame('1952-02-29', computeDate($l));
    }

    /** @test */
    public function invalid_day_is_clamped_to_last_day_of_month()
    {
        $l = $this->make(1951, 2, 31);
        $this->assertSame('1951-02-28', computeDate($l));
    }

    /** @test */
    public function missing_year_returns_null()
    {
        $l = $this->make(null, 5, 10);
        $this->assertNull(computeDate($l));
    }

    /** @test */
    public function string_or_zero_inputs_are_treated_as_missing()
    {
        $l = $this->make('0', '0', '0');
        $this->assertNull(computeDate($l));
    }
}
