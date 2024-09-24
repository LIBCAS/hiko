<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfessionCategory extends Model
{
    protected $table = 'global_profession_categories';
    protected $guarded = ['id'];

    public function professions()
    {
        return $this->hasMany(GlobalProfession::class);
    }
}
