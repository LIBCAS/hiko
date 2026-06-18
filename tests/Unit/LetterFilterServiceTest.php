<?php

namespace Tests\Unit;

use App\Services\LetterFilterService;
use PHPUnit\Framework\TestCase;

class LetterFilterServiceTest extends TestCase
{
    public function test_it_parses_comma_whitespace_semicolon_and_newline_separated_ids(): void
    {
        $ids = (new LetterFilterService())->parseIds("4050, 4078;\n4102  4078 invalid -1");

        $this->assertSame([4050, 4078, 4102], $ids);
    }

    public function test_it_returns_no_ids_for_input_without_positive_integers(): void
    {
        $this->assertSame([], (new LetterFilterService())->parseIds('abc, -2, 0'));
    }
}
