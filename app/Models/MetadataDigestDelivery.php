<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MetadataDigestDelivery extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_SENT = 'sent';
    public const STATUS_FAILED = 'failed';

    protected $connection = 'mysql';

    protected $fillable = [
        'metadata_digest_run_id',
        'recipient_email',
        'normalized_email',
        'recipient_name',
        'locale',
        'tenant_ids',
        'status',
        'attempts',
        'available_at',
        'started_at',
        'sent_at',
        'totals',
        'error_message',
    ];

    protected $casts = [
        'tenant_ids' => 'array',
        'totals' => 'array',
        'available_at' => 'datetime',
        'started_at' => 'datetime',
        'sent_at' => 'datetime',
    ];

    public function run(): BelongsTo
    {
        return $this->belongsTo(MetadataDigestRun::class, 'metadata_digest_run_id');
    }
}
