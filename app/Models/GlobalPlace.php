<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalPlace",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "scope", type: "string", example: "global", readOnly: true),
        new OA\Property(property: "reference", type: "string", example: "global-123", readOnly: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string"), nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class GlobalPlace extends Model
{
    protected $table = 'global_places';
    protected $guarded = ['id'];

    protected $casts = [
        'alternative_names' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

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
            "{$tenantPrefix}__letter_place", // Pivot table
            'global_place_id',
            'letter_id'
        )->withPivot('role', 'position', 'marked');
    }
}
