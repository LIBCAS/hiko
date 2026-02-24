<?php

namespace App\Services;

use App\Models\PageLock;
use App\Models\PageLockAuditLog;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;

class PageLockService
{
    public function acquire(array $key, User $user, bool $force = false): array
    {
        if (!$this->canAccessResourceType($key['resource_type'] ?? '')) {
            return ['ok' => false, 'acquired' => false, 'status' => 'forbidden'];
        }

        $context = $this->buildContext($key);
        $now = now();
        $expiresAt = $now->copy()->addSeconds((int) config('page_locks.ttl_seconds', 60));

        return DB::transaction(function () use ($context, $user, $force, $now, $expiresAt) {
            $lock = PageLock::where('resource_fingerprint', $context['resource_fingerprint'])
                ->lockForUpdate()
                ->first();

            if (!$lock) {
                $lock = PageLock::create($this->lockPayload($context, $user, $now, $expiresAt));
                $this->audit('acquired', $context, $user, ['mode' => 'create']);
                return $this->successResult($lock, false);
            }

            if ($this->isExpired($lock, $now)) {
                $previous = $this->holderData($lock);
                $lock->update($this->lockPayload($context, $user, $now, $expiresAt));
                $this->audit('acquired', $context, $user, ['mode' => 'expired', 'previous_holder' => $previous]);
                return $this->successResult($lock, false);
            }

            if ($this->isOwnedByActor($lock, $user)) {
                $lock->update([
                    'heartbeat_at' => $now,
                    'expires_at' => $expiresAt,
                ]);
                $this->audit('heartbeat', $context, $user, ['mode' => 'acquire_refresh']);
                return $this->successResult($lock, false);
            }

            if ($force) {
                $previous = $this->holderData($lock);
                $lock->update($this->lockPayload($context, $user, $now, $expiresAt));
                $this->audit('takeover', $context, $user, ['previous_holder' => $previous]);
                return $this->successResult($lock, true);
            }

            $this->audit('denied', $context, $user, ['locked_by' => $this->holderData($lock)]);
            return [
                'ok' => true,
                'acquired' => false,
                'status' => 'locked',
                'lock' => $this->serializeLock($lock),
            ];
        });
    }

    public function heartbeat(array $key, User $user): array
    {
        if (!$this->canAccessResourceType($key['resource_type'] ?? '')) {
            return ['ok' => false, 'status' => 'forbidden'];
        }

        $context = $this->buildContext($key);
        $now = now();
        $expiresAt = $now->copy()->addSeconds((int) config('page_locks.ttl_seconds', 60));

        return DB::transaction(function () use ($context, $user, $now, $expiresAt) {
            $lock = PageLock::where('resource_fingerprint', $context['resource_fingerprint'])
                ->lockForUpdate()
                ->first();

            if (!$lock) {
                $this->audit('missing', $context, $user, []);
                return ['ok' => false, 'status' => 'missing'];
            }

            if ($this->isExpired($lock, $now)) {
                $this->audit('expired', $context, $user, ['locked_by' => $this->holderData($lock)]);
                return ['ok' => false, 'status' => 'expired', 'lock' => $this->serializeLock($lock)];
            }

            if (!$this->isOwnedByActor($lock, $user)) {
                $this->audit('lost', $context, $user, ['locked_by' => $this->holderData($lock)]);
                return ['ok' => false, 'status' => 'lost', 'lock' => $this->serializeLock($lock)];
            }

            $lock->update([
                'heartbeat_at' => $now,
                'expires_at' => $expiresAt,
            ]);

            $this->audit('heartbeat', $context, $user, []);

            return [
                'ok' => true,
                'status' => 'active',
                'lock' => $this->serializeLock($lock),
            ];
        });
    }

    public function release(array $key, User $user): array
    {
        if (!$this->canAccessResourceType($key['resource_type'] ?? '')) {
            return ['ok' => false, 'status' => 'forbidden'];
        }

        $context = $this->buildContext($key);

        return DB::transaction(function () use ($context, $user) {
            $lock = PageLock::where('resource_fingerprint', $context['resource_fingerprint'])
                ->lockForUpdate()
                ->first();

            if (!$lock) {
                return ['ok' => true, 'released' => false];
            }

            if (!$this->isOwnedByActor($lock, $user)) {
                return ['ok' => true, 'released' => false];
            }

            $this->audit('released', $context, $user, []);
            $lock->delete();

            return ['ok' => true, 'released' => true];
        });
    }

    public function assertOwned(array $key, User $user): array
    {
        if (!$this->canAccessResourceType($key['resource_type'] ?? '')) {
            return ['ok' => false, 'status' => 'forbidden'];
        }

        $context = $this->buildContext($key);
        $lock = PageLock::where('resource_fingerprint', $context['resource_fingerprint'])->first();

        if (!$lock) {
            return ['ok' => false, 'status' => 'missing'];
        }

        if ($this->isExpired($lock, now())) {
            return ['ok' => false, 'status' => 'expired', 'lock' => $this->serializeLock($lock)];
        }

        if (!$this->isOwnedByActor($lock, $user)) {
            return ['ok' => false, 'status' => 'lost', 'lock' => $this->serializeLock($lock)];
        }

        return ['ok' => true, 'status' => 'active'];
    }

    protected function canAccessResourceType(string $resourceType): bool
    {
        $ability = config("page_locks.abilities.{$resourceType}");

        if (!$ability) {
            return false;
        }

        $user = auth()->user();
        return $user && $user->can($ability);
    }

    protected function buildContext(array $key): array
    {
        $scope = ($key['scope'] ?? 'tenant') === 'global' ? 'global' : 'tenant';
        $resourceType = (string) ($key['resource_type'] ?? '');
        $resourceId = isset($key['resource_id']) && $key['resource_id'] !== '' ? (string) $key['resource_id'] : null;

        $tenantId = null;
        $tenantPrefix = null;
        if ($scope === 'tenant' && function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            $tenantId = (int) tenancy()->tenant->id;
            $tenantPrefix = (string) tenancy()->tenant->table_prefix;
        }

        $resourceFingerprint = implode('|', [
            $scope,
            $tenantId ?? 'global',
            $resourceType,
            $resourceId ?? 'page',
        ]);

        return [
            'scope' => $scope,
            'tenant_id' => $tenantId,
            'tenant_prefix' => $tenantPrefix,
            'resource_type' => $resourceType,
            'resource_id' => $resourceId,
            'resource_fingerprint' => $resourceFingerprint,
        ];
    }

    protected function isExpired(PageLock $lock, Carbon $now): bool
    {
        $grace = (int) config('page_locks.grace_seconds', 5);
        return $lock->expires_at->copy()->addSeconds($grace)->lt($now);
    }

    protected function lockPayload(array $context, User $user, Carbon $now, Carbon $expiresAt): array
    {
        return [
            'scope' => $context['scope'],
            'tenant_id' => $context['tenant_id'],
            'tenant_prefix' => $context['tenant_prefix'],
            'resource_type' => $context['resource_type'],
            'resource_id' => $context['resource_id'],
            'resource_fingerprint' => $context['resource_fingerprint'],
            'locked_by_user_id' => $user->id,
            'locked_by_user_email' => $user->email,
            'locked_by_user_name' => $user->name,
            'locked_by_tenant_id' => $this->currentActorTenantId(),
            'locked_at' => $now,
            'heartbeat_at' => $now,
            'expires_at' => $expiresAt,
        ];
    }

    protected function successResult(PageLock $lock, bool $takenOver): array
    {
        return [
            'ok' => true,
            'acquired' => true,
            'status' => 'active',
            'taken_over' => $takenOver,
            'lock' => $this->serializeLock($lock),
        ];
    }

    protected function serializeLock(PageLock $lock): array
    {
        return [
            'locked_by_user_id' => (int) $lock->locked_by_user_id,
            'locked_by_user_name' => $lock->locked_by_user_name,
            'locked_by_user_email' => $lock->locked_by_user_email,
            'locked_by_tenant_id' => $lock->locked_by_tenant_id ? (int) $lock->locked_by_tenant_id : null,
            'expires_at' => $lock->expires_at?->toIso8601String(),
        ];
    }

    protected function holderData(PageLock $lock): array
    {
        return [
            'id' => (int) $lock->locked_by_user_id,
            'name' => $lock->locked_by_user_name,
            'email' => $lock->locked_by_user_email,
            'tenant_id' => $lock->locked_by_tenant_id ? (int) $lock->locked_by_tenant_id : null,
        ];
    }

    protected function isOwnedByActor(PageLock $lock, User $user): bool
    {
        $actorTenantId = $this->currentActorTenantId();
        $lockTenantId = $lock->locked_by_tenant_id ? (int) $lock->locked_by_tenant_id : null;

        if ($lockTenantId !== null || $actorTenantId !== null) {
            if ($lockTenantId !== $actorTenantId) {
                return false;
            }
        }

        $lockEmail = mb_strtolower(trim((string) $lock->locked_by_user_email));
        $userEmail = mb_strtolower(trim((string) $user->email));

        if ($lockEmail !== '' && $userEmail !== '') {
            return $lockEmail === $userEmail;
        }

        return (int) $lock->locked_by_user_id === (int) $user->id;
    }

    protected function currentActorTenantId(): ?int
    {
        if (function_exists('tenancy') && tenancy()->initialized && tenancy()->tenant) {
            return (int) tenancy()->tenant->id;
        }

        return null;
    }

    protected function audit(string $event, array $context, User $user, array $meta): void
    {
        PageLockAuditLog::create([
            'scope' => $context['scope'],
            'tenant_id' => $context['tenant_id'],
            'tenant_prefix' => $context['tenant_prefix'],
            'resource_type' => $context['resource_type'],
            'resource_id' => $context['resource_id'],
            'resource_fingerprint' => $context['resource_fingerprint'],
            'user_id' => $user->id,
            'user_email' => $user->email,
            'user_name' => $user->name,
            'event' => $event,
            'meta' => $meta,
        ]);
    }
}
