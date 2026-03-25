<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Profession",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "scope", type: "string", example: "local", readOnly: true),
        new OA\Property(property: "reference", type: "string", example: "local-152", readOnly: true),
        new OA\Property(property: "name", type: "object", properties: [
            new OA\Property(property: "cs", type: "string"),
            new OA\Property(property: "en", type: "string")
        ]),
        new OA\Property(property: "category_id", type: "integer", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class Profession extends Model
{
    use UsesTenantConnection, HasTranslations;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->initialized) {
            $this->setTable($this->getTenantPrefix() . '__professions');
        } else {
            $this->setTable('global_professions');
        }
    }

    public function profession_category()
    {
        return $this->belongsTo(tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class, 'profession_category_id');
    }

    public function identities()
    {
        $relatedModel = tenancy()->initialized ? Identity::class : GlobalIdentity::class;
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession'
            : 'global_identity_profession';

        return $this->belongsToMany(
            $relatedModel,
            $pivotTable,
            'profession_id',
            'identity_id'
        );
    }
}
