<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

class Profession extends Model
{
    use UsesTenantConnection, HasTranslations;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set table name for tenant-specific professions
        $this->setTable($this->getTenantPrefix() . '__professions');
    }

    /**
     * Get the profession category associated with this profession.
     */
    public function profession_category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class, 'profession_category_id');
    }

    /**
     * Get identities associated with this profession.
     */
    public function identities()
    {
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession'
            : 'global_identity_profession';

        return $this->belongsToMany(
            Identity::class,
            $pivotTable,
            'profession_id',
            'identity_id'
        );
    }

    /**
     * Apply filters to the query based on the provided filters array.
     *
     * @param Builder $query
     * @param array $filters
     * @return Builder
     */
    public function scopeApplyFilters(Builder $query, array $filters): Builder
    {
        if (!empty($filters['cs'])) {
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%".strtolower($filters['cs'])."%"]);
        }

        if (!empty($filters['en'])) {
            $query->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.en'))) LIKE ?", ["%".strtolower($filters['en'])."%"]);
        }

        if (!empty($filters['category'])) {
            $query->whereHas('profession_category', function ($categoryQuery) use ($filters) {
                $categoryQuery->whereRaw("LOWER(JSON_UNQUOTE(JSON_EXTRACT(name, '$.cs'))) LIKE ?", ["%".strtolower($filters['category'])."%"]);
            });
        }

        return $query;
    }
}
