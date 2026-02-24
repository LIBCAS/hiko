<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLockAuditLog extends Model
{
    protected $fillable = [
        'scope',
        'tenant_id',
        'tenant_prefix',
        'resource_type',
        'resource_id',
        'resource_fingerprint',
        'user_id',
        'user_email',
        'user_name',
        'event',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];
}

