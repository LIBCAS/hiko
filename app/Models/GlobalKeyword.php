<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

class GlobalKeyword extends Model
{
    use HasTranslations;

    protected $table = 'global_keywords';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    /**
     * Relationship with the GlobalKeywordCategory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function keyword_category()
    {
        return $this->belongsTo(GlobalKeywordCategory::class, 'keyword_category_id');
    }

    /**
     * Relationship with the Letter model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function letters(): BelongsToMany
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';

        return $this->belongsToMany(
            Letter::class,
            "{$tenantPrefix}__keyword_letter", // Pivot table
            'global_keyword_id',
            'letter_id'
        );
    }
}
