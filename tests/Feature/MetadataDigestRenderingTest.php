<?php

namespace Tests\Feature;

use App\Exports\MetadataDigestWorkbook;
use App\Mail\MetadataDigestMail;
use Carbon\CarbonImmutable;
use Maatwebsite\Excel\Excel;
use Maatwebsite\Excel\Facades\Excel as ExcelFacade;
use Tests\TestCase;

class MetadataDigestRenderingTest extends TestCase
{
    public function test_email_and_xlsx_render_without_sending_mail(): void
    {
        $section = [
            'label' => 'Klíčová slova',
            'headings' => ['id', 'cs', 'en', 'category'],
            'groups' => [[
                'scope' => 'local',
                'tenant' => 'brezina',
                'sheet' => 'brezina',
                'records' => [[
                    'id' => 7,
                    'href' => 'https://brezina.example/keywords/7/edit',
                    'export' => [7, 'dopis', 'letter', 'téma'],
                ]],
            ]],
            'total' => 1,
        ];
        $digest = [
            'period_start' => CarbonImmutable::parse('2026-07-01 00:00:00', 'Europe/Prague'),
            'period_end' => CarbonImmutable::parse('2026-08-01 00:00:00', 'Europe/Prague'),
            'sections' => ['keywords' => $section],
            'total' => 1,
        ];

        $html = (new MetadataDigestMail($digest, []))->render();
        $xlsx = ExcelFacade::raw(new MetadataDigestWorkbook($section), Excel::XLSX);

        $this->assertStringContainsString('brezina', $html);
        $this->assertStringContainsString('https://brezina.example/keywords/7/edit', $html);
        $this->assertStringStartsWith('PK', $xlsx);
    }

    public function test_email_renders_in_english_when_english_locale_is_selected(): void
    {
        $originalLocale = app()->getLocale();
        app()->setLocale('en');

        try {
            $section = [
                'label' => __('hiko.keywords'),
                'headings' => ['id', 'cs', 'en', 'category'],
                'groups' => [[
                    'scope' => 'global',
                    'tenant' => null,
                    'sheet' => 'Global',
                    'records' => [[
                        'id' => 7,
                        'href' => 'https://example.test/global/keywords/7/edit',
                        'export' => [7, 'dopis', 'letter', 'topic'],
                    ]],
                ]],
                'total' => 1,
            ];
            $digest = [
                'period_start' => CarbonImmutable::parse('2026-07-01 00:00:00', 'Europe/Prague'),
                'period_end' => CarbonImmutable::parse('2026-08-01 00:00:00', 'Europe/Prague'),
                'sections' => ['keywords' => $section],
                'total' => 1,
            ];

            $html = (new MetadataDigestMail($digest, []))->locale('en')->render();

            $this->assertStringContainsString('New records from', $html);
            $this->assertStringContainsString('Keywords', $html);
            $this->assertStringContainsString('Scope', $html);
            $this->assertStringContainsString('Global', $html);
            $this->assertStringNotContainsString('Nové záznamy', $html);
        } finally {
            app()->setLocale($originalLocale);
        }
    }
}
