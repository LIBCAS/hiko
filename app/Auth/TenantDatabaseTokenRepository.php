<?php

namespace App\Auth;

use Illuminate\Auth\Passwords\DatabaseTokenRepository;
use Illuminate\Contracts\Hashing\Hasher as HasherContract;
use Illuminate\Database\ConnectionInterface;

/**
 * Class TenantDatabaseTokenRepository
 *
 * This class extends the DatabaseTokenRepository to support multi-tenancy.
 * It overrides the table method to use a tenant-specific DB table for password resets.
 *
 * @package App\Auth
 */
class TenantDatabaseTokenRepository extends DatabaseTokenRepository
{
    public function __construct(
        ConnectionInterface $connection,
        HasherContract $hasher,
        string $table,
        string $key,
        int $expires = 60,
        int $throttle = 0
    ) {
        if (function_exists('tenancy') && tenancy()->initialized) {
            $dynamicTable = tenancy()->tenant->table_prefix . '__password_resets';
        } else {
            $dynamicTable = 'password_resets';
        }

        parent::__construct($connection, $hasher, $dynamicTable, $key, $expires, $throttle);
    }

    protected function table()
    {
        $table = $this->table;

        if (function_exists('tenancy') && tenancy()->initialized) {
            $table = tenancy()->tenant->table_prefix . '__password_resets';
        }

        return $this->connection->table($table);
    }
}
