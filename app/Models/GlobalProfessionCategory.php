<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfessionCategory extends Model
{
    protected $table = 'global_profession_categories'; // Table name is explicitly set
    protected $guarded = ['id'];

    public function professions()
    {
        // Use tenant-specific profession model if tenancy is initialized, otherwise global
        $relatedModel = tenancy()->initialized ? Profession::class : GlobalProfession::class;

        return $this->hasMany($relatedModel, 'profession_category_id');
    }
}
