<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AppSyncHistory extends Model
{
    protected $table = 'app_sync_history';

    protected $fillable = [
        'user_email',
        'status',
        'message',
        'duration_seconds',
    ];

    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];
}
