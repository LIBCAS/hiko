<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use App\Builders\KeywordBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class KeywordCategory extends Model
{
    use HasTranslations;
    use HasFactory;
    use Searchable;

    public array $translatable = ['name'];

    protected $guarded = ['id'];

    public function searchableAs(): string
    {
        return 'keyword_category_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class);
    }

    public function newEloquentBuilder($query): KeywordBuilder
    {
        return new KeywordBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
