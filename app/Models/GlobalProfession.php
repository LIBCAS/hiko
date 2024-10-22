<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalProfession extends Model
{
    use HasTranslations;

    public $translatable = ['name'];
    protected $connection = 'mysql';
    protected $table = 'global_professions';
    protected $guarded = ['id'];

    public function profession_category()
    {
        return $this->belongsTo(GlobalProfessionCategory::class, 'profession_category_id');
    }    
}
