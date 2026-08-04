<?php

namespace Tests\Unit;

use App\Services\MetadataDigestRecipientResolver;
use Tests\TestCase;

class MetadataDigestRecipientResolverTest extends TestCase
{
    public function test_it_deduplicates_case_insensitive_addresses_and_keeps_all_memberships(): void
    {
        $recipients = app(MetadataDigestRecipientResolver::class)->fromMemberships([
            ['email' => ' Scholar@Example.org ', 'name' => 'Leader Alpha', 'tenant_id' => 2],
            ['email' => 'scholar@example.org', 'name' => 'Leader Beta', 'tenant_id' => 1],
            ['email' => 'invalid', 'name' => 'Invalid', 'tenant_id' => 3],
        ]);

        $this->assertCount(1, $recipients);
        $recipient = $recipients->get('scholar@example.org');
        $this->assertSame('Scholar@Example.org', $recipient['email']);
        $this->assertSame('Leader Alpha', $recipient['name']);
        $this->assertSame([1, 2], $recipient['tenant_ids']);
    }
}
