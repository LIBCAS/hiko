<?php

namespace App\Support;

/** Shared SQL for Laravel and the standalone deployment tool. MySQL/MariaDB. */
final class NationalitySchema
{
    public static function quote(string $name): string
    {
        return '`' . str_replace('`', '``', $name) . '`';
    }

    public static function catalogue(): string
    {
        return "CREATE TABLE `nationalities` (id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            name JSON NOT NULL, created_at TIMESTAMP NULL, updated_at TIMESTAMP NULL) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    }

    public static function pivotName(string $source): string
    {
        return $source === 'global_identities' ? 'global_identity_nationality' : substr($source, 0, -10) . 'identity_nationality';
    }

    public static function pivot(string $source): string
    {
        $table = self::quote(self::pivotName($source));
        $key = $source === 'global_identities' ? 'global_identity_id' : 'identity_id';
        $source = self::quote($source);
        return "CREATE TABLE $table ($key BIGINT UNSIGNED NOT NULL, nationality_id BIGINT UNSIGNED NOT NULL,
            position INT UNSIGNED NOT NULL, PRIMARY KEY ($key, nationality_id), INDEX ($key, position),
            FOREIGN KEY ($key) REFERENCES $source(id) ON DELETE CASCADE,
            FOREIGN KEY (nationality_id) REFERENCES nationalities(id) ON DELETE RESTRICT) ENGINE=InnoDB";
    }
}
