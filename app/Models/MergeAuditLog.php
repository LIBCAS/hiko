<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MergeAuditLog extends Model
{
    protected $table = 'merge_audit_logs';

    protected $fillable = [
        'tenant_id',
        'tenant_prefix',
        'user_id',
        'user_email',
        'entity',
        'operation',
        'status',
        'payload',
        'result',
        'error_message',
    ];

    protected $casts = [
        'payload' => 'array',
        'result' => 'array',
    ];
}
