<?php

namespace Tests\Unit;

use App\Http\Requests\StoreMetadataDigestRequest;
use PHPUnit\Framework\TestCase;

class MetadataDigestAuthorizationTest extends TestCase
{
    public function test_only_active_admins_can_request_a_manual_digest(): void
    {
        $activeAdmin = new class {
            public string $role = 'admin';
            public function isDeactivated(): bool { return false; }
        };
        $developer = new class {
            public string $role = 'developer';
            public function isDeactivated(): bool { return false; }
        };
        $deactivatedAdmin = new class {
            public string $role = 'admin';
            public function isDeactivated(): bool { return true; }
        };

        $request = StoreMetadataDigestRequest::create('/data/metadata-digest', 'POST');

        $request->setUserResolver(fn() => $activeAdmin);
        $this->assertTrue($request->authorize());

        $request->setUserResolver(fn() => $developer);
        $this->assertFalse($request->authorize());

        $request->setUserResolver(fn() => $deactivatedAdmin);
        $this->assertFalse($request->authorize());
    }
}
