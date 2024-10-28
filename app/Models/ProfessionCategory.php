<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\UsesTenantConnection;
use App\Traits\TenantAware;

class ProfessionCategory extends Model
{
    use HasFactory, HasTranslations, UsesTenantConnection, TenantAware {
        UsesTenantConnection::getTenantPrefix insteadof TenantAware; // Resolve conflict
        TenantAware::getTenantPrefix as getTenantPrefixFromAware; // Alias TenantAware method if needed later
    }

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    
        // Set the table name based on whether tenancy is initialized
        $this->setTable(
            tenancy()->initialized ? $this->getTenantPrefix() . '__profession_categories' : 'global_profession_categories'
        );
    }
    
    // Many professions belong to this category (Tenant or Global)
    public function professions(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__profession_category_profession'
            : 'global_profession_category_profession';

        return $this->belongsToMany(
            tenancy()->initialized ? Profession::class : GlobalProfession::class,
            $pivotTable,
            'profession_category_id',
            'profession_id'
        );
    }

    // Search by name scope
    public function scopeSearchByName(Builder $query, $term)
    {
        $locale = app()->getLocale();
        $term = strtolower($term);

        return $query->whereRaw(
            "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"$locale\"'))) LIKE ?",
            ["%{$term}%"]
        );
    }

    // Many identities belong to many categories
    public function identities(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession_category'
            : 'global_identity_profession_category';

        return $this->belongsToMany(
            Identity::class,
            $pivotTable,
            'profession_category_id',
            'identity_id'
        );
    }
}
