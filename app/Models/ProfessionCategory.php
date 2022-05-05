<?php

namespace App\Models;

use App\Models\Identity;
use App\Builders\ProfessionBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProfessionCategory extends Model
{
    use HasFactory;
    use HasTranslations;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function identities()
    {
        return $this->belongsToMany(Identity::class);
    }

    public function newEloquentBuilder($query)
    {
        return new ProfessionBuilder($query);
    }
}
