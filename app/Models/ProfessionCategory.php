<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "ProfessionCategory",
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
class ProfessionCategory extends Model
{
    use HasTranslations, UsesTenantConnection;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        // Set table based on tenant initialization
        $this->setTable(tenancy()->initialized ? $this->getTenantPrefix() . '__profession_categories' : 'global_profession_categories');
    }

    /**
     * Get professions associated with this profession category.
     */
    public function professions()
    {
        return $this->hasMany(tenancy()->initialized ? Profession::class : GlobalProfession::class, 'profession_category_id');
    }

    /**
     * Get identities associated with this profession category.
     */
    public function identities()
    {
        if ($this->isTenancyInitialized()) {
            $pivotTable = "{$this->getTenantPrefix()}__identity_profession_category";
            return $this->belongsToMany(Identity::class, $pivotTable, 'profession_category_id', 'identity_id');
        }
    
        // Do not define the relationship when not in tenancy
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
