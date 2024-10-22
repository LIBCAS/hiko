<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfessionCategory extends Model
{
    protected $table = 'global_profession_categories'; // Table name explicitly set
    protected $guarded = ['id'];

    public function professions()
    {
        // If tenancy is initialized, switch to tenant-specific professions, otherwise use global professions
        $relatedModel = tenancy()->initialized ? Profession::class : GlobalProfession::class;

        return $this->hasMany($relatedModel, 'profession_category_id');
    }
}
