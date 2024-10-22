<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfessionCategory extends Model
{
    protected $table = 'global_profession_categories'; // Global table name
    protected $guarded = ['id'];

    /**
     * Get the professions associated with this global category.
     */
    public function professions()
    {
        // Use global professions for global categories
        return $this->hasMany(GlobalProfession::class, 'profession_category_id');
    }
}
