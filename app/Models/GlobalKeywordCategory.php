<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalKeywordCategory extends Model
{
    use HasTranslations;

    protected $table = 'global_keyword_categories';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function keywords()
    {
        return $this->hasMany(GlobalKeyword::class, 'keyword_category_id');
    }
}
