<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;

class ProfessionCategory extends Model
{
    use HasFactory, HasTranslations;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    // Define the connection and table name
    protected $connection;
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Determine the connection and table based on tenancy
        if (tenancy()->initialized) {
            // Tenant-specific connection and table
            $this->connection = 'tenant';

            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__profession_categories';
        } else {
            // Global connection and table
            $this->connection = 'mysql'; // or your central connection name
            $this->table = 'global_profession_categories';
        }
    }

    /**
     * Define the professions relationship.
     * This will work for both local and global categories.
     */
    public function professions()
    {
        if ($this->getConnectionName() === 'tenant') {
            return $this->hasMany(Profession::class, 'profession_category_id');
        } else {
            return $this->hasMany(GlobalProfession::class, 'profession_category_id');
        }
    }

    /**
     * Scope a query to only include categories matching a search term.
     */
    public function scopeSearchByName(Builder $query, $term)
    {
        $locale = app()->getLocale();
        $term = strtolower($term);

        return $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"$locale\"'))) LIKE ?", ["%{$term}%"]);
    }
}
