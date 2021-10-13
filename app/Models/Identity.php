<?php

namespace App\Models;

use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Identity extends Model
{
    use HasFactory;

    public function professions()
    {
        return $this->belongsToMany(Profession::class);
    }

    public function profession_categories()
    {
        return $this->belongsToMany(ProfessionCategory::class);
    }
}
