<?php

namespace App\Models;

use App\Models\Letter;
use App\Models\Profession;
use App\Builders\IdentityBuilder;
use App\Models\ProfessionCategory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Identity extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    protected $casts = [
        'alternative_names' => 'array',
    ];

    public function professions()
    {
        return $this->belongsToMany(Profession::class)
            ->withPivot('position');
    }

    public function profession_categories()
    {
        return $this->belongsToMany(ProfessionCategory::class)
            ->withPivot('position');
    }

    public function letters()
    {
        return $this->belongsToMany(Letter::class)
            ->withPivot('marked');
    }

    public function getDatesAttribute()
    {
        if (empty($this->birth_year) && empty($this->death_year)) {
            return '';
        }

        if ($this->birth_year && $this->death_year) {
            return "({$this->birth_year}–{$this->death_year})";
        }

        if ($this->birth_year) {
            return "({$this->birth_year}–)";
        }

        if ($this->death_year) {
            return "(–{$this->death_year})";
        }
    }

    public function newEloquentBuilder($query)
    {
        return new IdentityBuilder($query);
    }
}
