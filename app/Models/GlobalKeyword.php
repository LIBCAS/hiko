<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
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
     * Relationship with the Identity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function identities()
    {
        return $this->belongsToMany(Identity::class, 'global_identity_keyword', 'keyword_id', 'identity_id');
    }  
    
}
