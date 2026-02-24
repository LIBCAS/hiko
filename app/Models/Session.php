<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Session extends Model
{
    use HasFactory;

    protected $connection = 'tenant';

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->setTable(tenancy()->tenant->table_prefix . '__sessions');
        } else {
            $this->setTable('sessions'); // fallback, just in case
        }
    }
}
