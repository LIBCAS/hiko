<?php

namespace App\Models;

use App\Models\Keyword;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KeywordCategory extends Model
{
    use HasTranslations;
    use HasFactory;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function keywords()
    {
        return $this->hasMany(Keyword::class);
    }
}
