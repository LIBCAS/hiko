<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;
use Spatie\Translatable\HasTranslations;

class GlobalProfession extends Model
{
    use HasTranslations;

    public $translatable = ['name'];
    protected $connection = 'mysql'; // Use global MySQL connection
    protected $table = 'global_professions';
    protected $guarded = ['id'];

    /**
     * Get the profession category associated with this global profession.
     */
    public function profession_category()
    {
        return $this->belongsTo(GlobalProfessionCategory::class, 'profession_category_id');
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
