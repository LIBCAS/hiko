<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Sanctum\PersonalAccessToken as SanctumPersonalAccessToken;

class PersonalAccessToken extends SanctumPersonalAccessToken
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $guarded = [];

    protected $casts = [
        'abilities' => 'array',
    ];

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->setTable(tenancy()->tenant->table_prefix . '__personal_access_tokens');
        } else {
            $this->setTable('personal_access_tokens'); // fallback, just in case
        }
    }
}
