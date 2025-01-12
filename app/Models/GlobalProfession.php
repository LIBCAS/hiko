<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

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
    public function identities()
    {
        return $this->belongsToMany(Identity::class, 'global_identity_profession', 'profession_id', 'identity_id');
    }  
    
}
