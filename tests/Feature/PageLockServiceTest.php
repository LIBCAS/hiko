<?php

namespace Tests\Feature;

use App\Models\PageLock;
use App\Models\PageLockAuditLog;
use App\Models\User;
use App\Services\PageLockService;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Schema;
use Tests\TestCase;

class PageLockServiceTest extends TestCase
{
    protected string $databasePath;

    protected PageLockService $locks;

    protected User $owner;

    protected array $key = [
        'scope' => 'global',
        'resource_type' => 'global_identity_edit',
        'resource_id' => '123',
    ];

    protected function setUp(): void
    {
        parent::setUp();

        if (!in_array('sqlite', \PDO::getAvailableDrivers(), true)) {
            $this->markTestSkipped('The pdo_sqlite extension is required for this integration test.');
        }

        $this->databasePath = tempnam(sys_get_temp_dir(), 'hiko-page-lock-');
        $connection = array_merge(config('database.connections.sqlite'), [
            'database' => $this->databasePath,
            'prefix' => '',
        ]);

        Config::set('database.default', 'sqlite');
        Config::set('database.connections.sqlite', $connection);
        Config::set('database.connections.tenant', $connection);
        Config::set('page_locks.ttl_seconds', 60);
        Config::set('page_locks.grace_seconds', 5);
        DB::purge('sqlite');
        DB::purge('tenant');

        $this->createSchema();

        Gate::before(fn () => true);
        $this->owner = $this->user(1001, 'Test Editor One', 'editor-one@example.test');
        Auth::setUser($this->owner);
        $this->locks = new class extends PageLockService {
            protected function currentActorTenantId(): ?int
            {
                return 42;
            }
        };
        Carbon::setTestNow('2026-08-05 10:00:00');
    }

    protected function tearDown(): void
    {
        Carbon::setTestNow();
        Auth::forgetUser();

        if (tenancy()->initialized) {
            tenancy()->end();
        }

        DB::disconnect('sqlite');
        DB::disconnect('tenant');
        @unlink($this->databasePath);

        parent::tearDown();
    }

    public function test_late_heartbeat_renews_an_expired_lock_still_owned_by_the_requester(): void
    {
        $this->assertTrue($this->locks->acquire($this->key, $this->owner)['acquired']);

        Carbon::setTestNow(now()->addSeconds(70));
        $result = $this->locks->heartbeat($this->key, $this->owner);

        $this->assertTrue($result['ok'], json_encode($result));
        $this->assertSame('active', $result['status']);
        $this->assertSame(now()->addSeconds(60)->toIso8601String(), $result['lock']['expires_at']);
        $this->assertDatabaseHas('page_lock_audit_logs', [
            'resource_fingerprint' => 'global|global|global_identity_edit|123',
            'event' => 'heartbeat',
            'meta' => json_encode(['mode' => 'expired_refresh']),
        ]);
    }

    public function test_another_user_can_acquire_an_expired_lock_and_the_original_owner_then_loses_it(): void
    {
        $this->locks->acquire($this->key, $this->owner);
        Carbon::setTestNow(now()->addSeconds(70));

        $other = $this->user(1002, 'Test Editor Two', 'editor-two@example.test');
        Auth::setUser($other);
        $takeover = $this->locks->acquire($this->key, $other);

        $this->assertTrue($takeover['acquired']);
        $this->assertFalse($takeover['taken_over']);

        Auth::setUser($this->owner);
        $result = $this->locks->heartbeat($this->key, $this->owner);

        $this->assertFalse($result['ok']);
        $this->assertSame('lost', $result['status']);
        $this->assertSame('Test Editor Two', $result['lock']['locked_by_user_name']);
    }

    public function test_save_ownership_check_renews_an_expired_self_owned_lock(): void
    {
        $this->locks->acquire($this->key, $this->owner);
        Carbon::setTestNow(now()->addSeconds(70));

        $result = $this->locks->assertOwned($this->key, $this->owner);

        $this->assertTrue($result['ok'], json_encode($result));
        $this->assertSame('active', $result['status']);
        $this->assertTrue(PageLock::firstOrFail()->expires_at->greaterThan(now()));
        $this->assertSame(
            'assert_expired_refresh',
            PageLockAuditLog::query()->latest('id')->firstOrFail()->meta['mode']
        );
    }

    protected function user(int $id, string $name, string $email): User
    {
        $user = new User([
            'name' => $name,
            'email' => $email,
            'role' => 'editor',
        ]);
        $user->id = $id;

        return $user;
    }

    protected function createSchema(): void
    {
        Schema::create('page_locks', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->string('resource_type', 100);
            $table->string('resource_id', 100)->nullable();
            $table->string('resource_fingerprint', 255)->unique();
            $table->unsignedBigInteger('locked_by_user_id');
            $table->string('locked_by_user_email')->nullable();
            $table->string('locked_by_user_name')->nullable();
            $table->unsignedBigInteger('locked_by_tenant_id')->nullable();
            $table->timestamp('locked_at');
            $table->timestamp('heartbeat_at');
            $table->timestamp('expires_at');
            $table->timestamps();
        });

        Schema::create('page_lock_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->string('scope', 20);
            $table->unsignedBigInteger('tenant_id')->nullable();
            $table->string('tenant_prefix')->nullable();
            $table->string('resource_type', 100);
            $table->string('resource_id', 100)->nullable();
            $table->string('resource_fingerprint', 255)->index();
            $table->unsignedBigInteger('user_id')->nullable();
            $table->string('user_email')->nullable();
            $table->string('user_name')->nullable();
            $table->string('event', 50);
            $table->json('meta')->nullable();
            $table->timestamps();
        });
    }
}
