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

        // Ensure the tenant-specific table name is set with the correct separator
        $this->setTable($this->getTenantPrefix() . '__profession_categories');
    }

    public function professions()
    {
        return $this->hasMany(
            tenancy()->initialized ? Profession::class : GlobalProfession::class,
            'profession_category_id'
        );
    }

    public function scopeSearchByName(Builder $query, $term)
    {
        $locale = app()->getLocale();
        $term = strtolower($term);

        return $query->whereRaw(
            "LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.\"$locale\"'))) LIKE ?",
            ["%{$term}%"]
        );
    }

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
