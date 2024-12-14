<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use App\Builders\KeywordBuilder;

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

        $this->initializeTable();
    }

    /**
     * Initialize the table name dynamically based on tenancy.
     */
    protected function initializeTable(): void
    {
        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = "{$tenantPrefix}__keywords";
        } else {
            $this->table = 'keywords';
        }
    }

    /**
     * Define the index name for Laravel Scout.
     */
    public function searchableAs(): string
    {
        return 'keyword_index';
    }

    /**
     * Define the searchable array for Laravel Scout.
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    /**
     * Relationship with KeywordCategory.
     */
    public function keyword_category(): BelongsTo
    {
        return $this->belongsTo(KeywordCategory::class, 'keyword_category_id', 'id');
    }

    /**
     * Relationship with letters (many-to-many).
     */
    public function letters(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__keyword_letter'
            : 'keyword_letter';

        return $this->belongsToMany(Letter::class, $pivotTable, 'keyword_id', 'letter_id');
    }

    /**
     * Use a custom Eloquent builder.
     */
    public function newEloquentBuilder($query): KeywordBuilder
    {
        return new KeywordBuilder($query);
    }

    /**
     * Encode JSON values with options.
     */
    protected function asJson($value): string
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
    }
}
