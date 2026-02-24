<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class OcrSnapshot extends Model
{
    protected $fillable = [
        'tenant_id',
        'tenant_prefix',
        'letter_id',
        'user_id',
        'user_email',
        'provider',
        'model',
        'status',
        'source_files',
        'recognized_text',
        'metadata',
        'mapped_fields',
        'request_prompt',
        'raw_response',
        'error_message',
        'applied_at',
        'applied_by_user_id',
        'apply_mode',
        'applied_field_keys',
    ];

    protected $casts = [
        'source_files' => 'array',
        'metadata' => 'array',
        'mapped_fields' => 'array',
        'applied_field_keys' => 'array',
        'applied_at' => 'datetime',
    ];
}
