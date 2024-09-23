<?php

namespace App\Models;

use App\Builders\KeywordBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Keyword extends Model
{
    use HasTranslations, HasFactory, Searchable;

    protected $connection = 'tenant';
    protected $guarded = ['id'];
    public array $translatable = ['name'];
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__keywords';
        }
    }

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

    public function letters(): BelongsToMany
    {
        return $this->belongsToMany(Letter::class);
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