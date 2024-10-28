<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

class ProfessionCategory extends Model
{
    use HasTranslations, UsesTenantConnection;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(tenancy()->initialized ? $this->getTenantPrefix() . '__profession_categories' : 'global_profession_categories');
    }
    
    public function professions()
    {
        return $this->hasMany(
            tenancy()->initialized ? Profession::class : GlobalProfession::class,
            'profession_category_id'
        );
    }

    public function identities()
    {
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession_category'
            : 'global_identity_profession_category';
    
        return $this->belongsToMany(Identity::class, $pivotTable, 'profession_category_id', 'identity_id');
    }
    
}
