<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class Identity extends Model
{
    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Constructor to dynamically set the table name based on tenancy.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Dynamically set the tenant-specific table name
        $this->table = $this->isTenancyInitialized()
            ? "{$this->getTenantPrefix()}__identities" // Tenant-specific table
            : 'global_identities'; // Global table
    }

    /**
     * Check if tenancy is initialized.
     *
     * @return bool
     */
    protected function isTenancyInitialized(): bool
    {
        return tenancy()->initialized;
    }

    /**
     * Get the tenant's table prefix.
     *
     * @return string
     */
    protected function getTenantPrefix(): string
    {
        return tenancy()->tenant->table_prefix;
    }

    /**
     * Define the professions relationship.
     *
     * @return BelongsToMany
     */
    public function professions(): BelongsToMany
    {
        try {
            // Determine pivot table name based on tenancy
            $pivotTable = $this->isTenancyInitialized()
                ? "{$this->getTenantPrefix()}__identity_profession"
                : 'global_identity_profession';

            // Determine related model based on tenancy
            $relatedModel = $this->isTenancyInitialized()
                ? Profession::class
                : GlobalProfession::class;

            return $this->belongsToMany(
                $relatedModel,
                $pivotTable,
                'identity_id',
                'profession_id'
            )->withPivot('position');
        } catch (\Exception $e) {
            Log::error("Error in Identity::professions relationship: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Define the profession categories relationship.
     *
     * @return BelongsToMany
     */
    public function profession_categories(): BelongsToMany
    {
        try {
            // Determine pivot table name based on tenancy
            $pivotTable = $this->isTenancyInitialized()
                ? "{$this->getTenantPrefix()}__identity_profession_category"
                : 'global_identity_profession_category';

            // Determine related model based on tenancy
            $relatedModel = $this->isTenancyInitialized()
                ? ProfessionCategory::class
                : GlobalProfessionCategory::class;

            return $this->belongsToMany(
                $relatedModel,
                $pivotTable,
                'identity_id',
                'profession_category_id'
            )->withPivot('position');
        } catch (\Exception $e) {
            Log::error("Error in Identity::profession_categories relationship: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Define the letters relationship.
     *
     * @return BelongsToMany
     */
    public function letters(): BelongsToMany
    {
        try {
            // Determine pivot table name based on tenancy
            $pivotTable = $this->isTenancyInitialized()
                ? "{$this->getTenantPrefix()}__identity_letter"
                : 'global_identity_letter';

            return $this->belongsToMany(
                Letter::class,
                $pivotTable,
                'identity_id',
                'letter_id'
            )->withPivot('role');
        } catch (\Exception $e) {
            Log::error("Error in Identity::letters relationship: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Scope a query to search identities based on filters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param array $filters
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeSearch($query, $filters)
    {
        if (!empty($filters['search_term'])) {
            $query->where('name', 'like', '%' . $filters['search_term'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query;
    }
}
