<?php

namespace App\Models;

use App\Builders\KeywordBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Keyword extends Model
{
    use HasTranslations;
    use HasFactory;
    use Searchable;

    public array $translatable = ['name'];

    protected $guarded = ['id'];

    public function searchableAs(): string
    {
        return 'keyword_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    public function keyword_category(): BelongsTo
    {
        return $this->belongsTo(KeywordCategory::class);
    }

    public function letters(): BelongsTo
    {
        return $this->belongsTo(Letter::class);
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
