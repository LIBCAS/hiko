<?php

namespace App\Models;

use App\Builders\IdentityBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Stancl\Tenancy\Facades\Tenancy;

class Identity extends Model
{
    protected $connection = 'tenant';
    protected $guarded = ['id'];
    protected $table;
    protected $casts = [
        'alternative_names' => 'array',
        'related_identity_resources' => 'array',
        'related_names' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Dynamically set the tenant-specific table name
        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__identities';  // Set tenant-specific table name
        } else {
            throw new \Exception('Tenancy not initialized.');
        }
    }

    public function newEloquentBuilder($query): IdentityBuilder
    {
        return new IdentityBuilder($query);  // Use custom IdentityBuilder, if needed
    }

    public function professions(): BelongsToMany
    {
        // Get the tenant-specific table prefix
        $tenantPrefix = tenancy()->tenant->table_prefix;

        // Use the tenant-specific pivot table (e.g., blekastad__identity_profession)
        return $this->belongsToMany(Profession::class, $tenantPrefix . '__identity_profession')
                    ->withPivot('position')
                    ->orderBy('pivot_position', 'asc');
    }

    public function profession_categories(): BelongsToMany
    {
        $tenantPrefix = tenancy()->tenant->table_prefix;

        // Use the tenant-specific pivot table for profession categories
        return $this->belongsToMany(ProfessionCategory::class, $tenantPrefix . '__identity_profession_category')
                    ->withPivot('position')
                    ->orderBy('pivot_position', 'asc');
    }

    // Define the relationship with the tenant-specific pivot table
    public function letters(): BelongsToMany
    {
        // Use the tenant-specific pivot table (e.g., blekastad__identity_letter)
        return $this->belongsToMany(Letter::class, tenancy()->tenant->table_prefix . '__identity_letter')
            ->withPivot('position', 'role', 'marked', 'salutation')  // Include necessary pivot fields
            ->orderBy('pivot_position', 'asc');  // Add ordering if necessary
    }
}
