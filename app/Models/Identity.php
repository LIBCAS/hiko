<?php

namespace App\Models;

use App\Models\Letter;
use App\Models\Profession;
use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Identity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function professions()
    {
        return $this->belongsToMany(Profession::class)->withPivot('position');
    }

    public function profession_categories()
    {
        return $this->belongsToMany(ProfessionCategory::class)->withPivot('position');
    }

    public function letters()
    {
        return $this->belongsToMany(Letter::class);
    }
}
