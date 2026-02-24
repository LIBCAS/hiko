<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalProfession",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "cs", type: "string"),
            new OA\Property(property: "en", type: "string")
        ]),
        new OA\Property(property: "profession_category_id", type: "integer", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class GlobalProfession extends Model
{
    use HasTranslations;

    protected $table = 'global_professions';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    /**
     * Relationship with the GlobalProfessionCategory.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function profession_category()
    {
        return $this->belongsTo(GlobalProfessionCategory::class, 'profession_category_id');
    }

    /**
     * Relationship with the Identity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function identities(): BelongsToMany
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix . '__' : '';

        return $this->belongsToMany(
            Identity::class,
            "{$tenantPrefix}identity_profession", // Pivot table
            'global_profession_id',
            'identity_id'
        );
    }
}
