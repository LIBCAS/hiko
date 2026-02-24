<?php

namespace App\Console\Commands;

use App\Models\PageLock;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;

class PageLocksStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'page-locks:status
        {--scope= : Filter by scope (tenant|global)}
        {--resource-type= : Filter by resource type}
        {--tenant-id= : Filter by tenant ID}
        {--user= : Filter by user ID, email, or name}
        {--all : Include expired locks}
        {--json : Output JSON}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Show current page locks for debugging';

    /**
     * Execute the console command.
     *
     * @return int
     */
    public function handle()
    {
        if (!Schema::hasTable('page_locks')) {
            $this->error('Table page_locks does not exist yet.');
            return 1;
        }

        $now = now();
        $graceSeconds = (int) config('page_locks.grace_seconds', 5);

        $query = PageLock::query();

        if ($scope = $this->option('scope')) {
            $query->where('scope', $scope);
        }

        if ($resourceType = $this->option('resource-type')) {
            $query->where('resource_type', $resourceType);
        }

        if ($tenantId = $this->option('tenant-id')) {
            $query->where('tenant_id', (int) $tenantId);
        }

        if ($user = $this->option('user')) {
            $query->where(function ($q) use ($user) {
                if (ctype_digit((string) $user)) {
                    $q->where('locked_by_user_id', (int) $user)
                        ->orWhere('locked_by_user_email', 'like', '%' . $user . '%')
                        ->orWhere('locked_by_user_name', 'like', '%' . $user . '%');
                    return;
                }

                $q->where('locked_by_user_email', 'like', '%' . $user . '%')
                    ->orWhere('locked_by_user_name', 'like', '%' . $user . '%');
            });
        }

        $locks = $query
            ->orderBy('scope')
            ->orderBy('tenant_id')
            ->orderBy('resource_type')
            ->orderBy('resource_id')
            ->get();

        $rows = $locks->map(function (PageLock $lock) use ($now, $graceSeconds) {
            $status = 'active';
            if ($lock->expires_at->lte($now)) {
                $status = $lock->expires_at->copy()->addSeconds($graceSeconds)->gt($now)
                    ? 'grace'
                    : 'expired';
            }

            return [
                'id' => $lock->id,
                'status' => $status,
                'scope' => $lock->scope,
                'tenant_id' => $lock->tenant_id,
                'tenant_prefix' => $lock->tenant_prefix,
                'resource_type' => $lock->resource_type,
                'resource_id' => $lock->resource_id,
                'fingerprint' => $lock->resource_fingerprint,
                'user_id' => $lock->locked_by_user_id,
                'user_email' => $lock->locked_by_user_email,
                'user_name' => $lock->locked_by_user_name,
                'locked_by_tenant_id' => $lock->locked_by_tenant_id,
                'locked_at' => optional($lock->locked_at)?->toDateTimeString(),
                'heartbeat_at' => optional($lock->heartbeat_at)?->toDateTimeString(),
                'expires_at' => optional($lock->expires_at)?->toDateTimeString(),
                'expires_in_seconds' => $now->diffInSeconds($lock->expires_at, false),
            ];
        });

        if (!$this->option('all')) {
            $rows = $rows->reject(fn(array $row) => $row['status'] === 'expired')->values();
        }

        $summary = [
            'total' => $rows->count(),
            'active' => $rows->where('status', 'active')->count(),
            'grace' => $rows->where('status', 'grace')->count(),
            'expired' => $rows->where('status', 'expired')->count(),
            'generated_at' => $now->toDateTimeString(),
        ];

        if ($this->option('json')) {
            $this->line(json_encode([
                'summary' => $summary,
                'locks' => $rows->values()->all(),
            ], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            return 0;
        }

        $this->info(sprintf(
            'Locks: total=%d, active=%d, grace=%d, expired=%d',
            $summary['total'],
            $summary['active'],
            $summary['grace'],
            $summary['expired']
        ));

        if ($rows->isEmpty()) {
            $this->line('No locks matched your filters.');
            return 0;
        }

        $this->table([
            'ID',
            'Status',
            'Scope',
            'Tenant',
            'Resource',
            'Res ID',
            'User',
            'User Email',
            'Lock Tenant',
            'Heartbeat',
            'Expires In (s)',
        ], $rows->map(fn(array $row) => [
            $row['id'],
            $row['status'],
            $row['scope'],
            $row['tenant_id'] ?? '-',
            $row['resource_type'],
            $row['resource_id'] ?? '-',
            $row['user_name'] ?? '-',
            $row['user_email'] ?? '-',
            $row['locked_by_tenant_id'] ?? '-',
            $row['heartbeat_at'] ?? '-',
            $row['expires_in_seconds'],
        ])->all());

        return 0;
    }
}
