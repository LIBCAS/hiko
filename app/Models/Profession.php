<?php

namespace App\Models;

use App\Models\Identity;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Profession extends Model
{
    use HasTranslations;
    use HasFactory;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function identities()
    {
        return $this->belongsToMany(Identity::class);
    }
}
