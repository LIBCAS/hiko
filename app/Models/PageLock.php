<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PageLock extends Model
{
    protected $fillable = [
        'scope',
        'tenant_id',
        'tenant_prefix',
        'resource_type',
        'resource_id',
        'resource_fingerprint',
        'locked_by_user_id',
        'locked_by_user_email',
        'locked_by_user_name',
        'locked_by_session_id',
        'locked_by_tenant_id',
        'locked_at',
        'heartbeat_at',
        'expires_at',
    ];

    protected $casts = [
        'locked_at' => 'datetime',
        'heartbeat_at' => 'datetime',
        'expires_at' => 'datetime',
    ];
}
