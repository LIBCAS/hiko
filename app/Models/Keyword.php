<?php

namespace App\Models;

use App\Models\Letter;
use App\Models\KeywordCategory;
use App\Builders\KeywordBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Keyword extends Model
{
    use HasTranslations;
    use HasFactory;
    use Searchable;

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function searchableAs()
    {
        return 'keyword_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

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

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
