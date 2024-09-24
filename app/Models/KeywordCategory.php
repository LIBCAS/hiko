<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use Laravel\Scout\Searchable;
use App\Builders\KeywordBuilder;

class KeywordCategory extends Model
{
    use HasFactory, HasTranslations, Searchable;

    protected $connection = 'tenant';
    protected $guarded = ['id'];
    public array $translatable = ['name'];
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set the table name with tenant prefix
        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__keyword_categories';
        }
    }

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
        return $this->hasMany(Keyword::class, 'keyword_category_id', 'id');
    }

    public function newEloquentBuilder($query): KeywordBuilder
    {
        return new KeywordBuilder($query);
    }

    protected function asJson($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
