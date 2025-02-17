<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Spatie\Translatable\HasTranslations;
use Laravel\Scout\Searchable;
use Illuminate\Support\Facades\Log;

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
        $this->initializeTable();
    }

    protected function initializeTable(): void
    {
        $this->table = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__keyword_categories'
            : 'keyword_categories';
    }

    public function searchableAs(): string
    {
        return 'keyword_category_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs', false) ?? '',
            'en' => $this->getTranslation('name', 'en', false) ?? '',
        ];
    }

    public function keywords(): HasMany
    {
        return $this->hasMany(Keyword::class, 'keyword_category_id', 'id')
            ->select(['id', 'keyword_category_id', 'name']);
    }
}
