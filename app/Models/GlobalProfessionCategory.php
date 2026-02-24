<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalProfessionCategory",
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
class GlobalProfessionCategory extends Model
{
    use HasTranslations;

    protected $table = 'global_profession_categories';
    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function professions()
    {
        return $this->hasMany(GlobalProfession::class, 'profession_category_id');
    }

    public function identities()
    {
        if ($this->isTenancyInitialized()) {
            $pivotTable = "{$this->getTenantPrefix()}__identity_profession_category";
            return $this->belongsToMany(Identity::class, $pivotTable, 'profession_category_id', 'identity_id');
        }

        // Return an empty relationship to prevent accessing non-existent tables
        return $this->belongsToMany(Identity::class, null, null, null)
                    ->whereRaw('1 = 0'); // Ensures no records are returned
    }

    protected function isTenancyInitialized(): bool
    {
        return tenancy()->initialized;
    }

    protected function getTenantPrefix(): ?string
    {
        return tenancy()->tenant ? tenancy()->tenant->table_prefix : null;
    }
}
