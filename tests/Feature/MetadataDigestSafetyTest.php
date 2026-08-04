<?php

namespace Tests\Feature;

use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MetadataDigestSafetyTest extends TestCase
{
    public function test_digest_commands_do_not_send_or_create_work_when_disabled(): void
    {
        config(['metadata_digest.enabled' => false]);
        Mail::fake();

        $this->artisan('metadata-digests:dispatch-monthly')
            ->expectsOutput('Metadata digest delivery is disabled.')
            ->assertSuccessful();

        $this->artisan('metadata-digests:process')
            ->expectsOutput('Metadata digest delivery is disabled.')
            ->assertSuccessful();

        Mail::assertNothingSent();
    }
}
