<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GlobalProfession extends Model
{
    use HasTranslations;

    protected $table = 'global_professions';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    /**
     * Relationship with the GlobalProfessionCategory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profession_category()
    {
        return $this->belongsTo(GlobalProfessionCategory::class, 'profession_category_id');
    }

    /**
     * Relationship with the Identity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function identities(): BelongsToMany
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix . '__' : '';
    
        return $this->belongsToMany(
            Identity::class,
            "{$tenantPrefix}identity_profession", // Pivot table
            'global_profession_id',
            'identity_id'
        );
    }
}
