<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalKeyword",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "cs", type: "string"),
            new OA\Property(property: "en", type: "string")
        ]),
        new OA\Property(property: "keyword_category_id", type: "integer", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class GlobalKeyword extends Model
{
    use HasTranslations;

    protected $table = 'global_keywords';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    /**
     * Relationship with the GlobalKeywordCategory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function keyword_category()
    {
        return $this->belongsTo(GlobalKeywordCategory::class, 'keyword_category_id');
    }

    /**
     * Relationship with the Letter model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function letters(): BelongsToMany
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';

        return $this->belongsToMany(
            Letter::class,
            "{$tenantPrefix}__keyword_letter", // Pivot table
            'global_keyword_id',
            'letter_id'
        );
    }
}
