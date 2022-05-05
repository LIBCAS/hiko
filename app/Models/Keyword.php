<?php

namespace App\Models;

use App\Models\Letter;
use App\Models\KeywordCategory;
use App\Builders\KeywordBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Keyword extends Model
{
    use HasTranslations;
    use HasFactory;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function keyword_category()
    {
        return $this->belongsTo(KeywordCategory::class);
    }

    public function letters()
    {
        return $this->belongsTo(Letter::class);
    }

    public function newEloquentBuilder($query)
    {
        return new KeywordBuilder($query);
    }
}
