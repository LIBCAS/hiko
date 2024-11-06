<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalProfessionCategory extends Model
{
    use HasTranslations;

    protected $table = 'global_profession_categories';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function professions()
    {
        return $this->hasMany(GlobalProfession::class, 'profession_category_id');
    }

    public function identities()
    {
        return $this->belongsToMany(Identity::class, 'global_identity_profession_category', 'profession_category_id', 'identity_id');
    }
}
