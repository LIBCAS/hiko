<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class MetadataDigestRun extends Model
{
    public const TRIGGER_SCHEDULED = 'scheduled';
    public const TRIGGER_MANUAL = 'manual';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_PARTIAL = 'partial';
    public const STATUS_FAILED = 'failed';

    protected $connection = 'mysql';

    protected $fillable = [
        'trigger',
        'deduplication_key',
        'period_start',
        'period_end',
        'status',
        'requested_from_tenant_id',
        'requested_by_user_id',
        'requested_by_name',
        'requested_by_email',
        'error_message',
    ];

    protected $casts = [
        'period_start' => 'datetime',
        'period_end' => 'datetime',
    ];

    public function deliveries(): HasMany
    {
        return $this->hasMany(MetadataDigestDelivery::class);
    }
}
