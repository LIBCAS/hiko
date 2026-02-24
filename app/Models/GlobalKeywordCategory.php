<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalKeywordCategory",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "cs", type: "string"),
            new OA\Property(property: "en", type: "string")
        ]),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class GlobalKeywordCategory extends Model
{
    use HasTranslations;

    protected $table = 'global_keyword_categories';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function keywords()
    {
        return $this->hasMany(GlobalKeyword::class, 'keyword_category_id');
    }
}
