<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InterTenantTransferRequest extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_REJECTED = 'rejected';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_FAILED = 'failed';

    protected $connection = 'mysql';

    protected $fillable = [
        'source_tenant_id',
        'target_tenant_id',
        'entity_type',
        'status',
        'requested_by_user_id',
        'requested_by_name',
        'requested_by_email',
        'decided_by_user_id',
        'decided_by_name',
        'decided_by_email',
        'source_record_ids',
        'filters',
        'mappings',
        'result',
        'final_snapshot',
        'decision_reason',
        'error_message',
        'decided_at',
    ];

    protected $casts = [
        'source_record_ids' => 'array',
        'filters' => 'array',
        'mappings' => 'array',
        'result' => 'array',
        'final_snapshot' => 'array',
        'decided_at' => 'datetime',
    ];

    public function sourceTenant()
    {
        return $this->belongsTo(Tenant::class, 'source_tenant_id');
    }

    public function targetTenant()
    {
        return $this->belongsTo(Tenant::class, 'target_tenant_id');
    }

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }
}
