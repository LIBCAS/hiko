<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PasswordReset extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->setTable(tenancy()->tenant->table_prefix . '__password_resets');
        } else {
            $this->setTable('password_resets'); // fallback, just in case
        }
    }
}
