<?php

namespace App\Models;

use App\Builders\PlaceBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Stancl\Tenancy\Facades\Tenancy;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Place",
    required: ["name"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "scope", type: "string", example: "local", readOnly: true),
        new OA\Property(property: "reference", type: "string", example: "local-123", readOnly: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string"), nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class Place extends Model
{
    use HasFactory;
    use Searchable;

    protected $connection = 'tenant';
    protected $guarded = ['id'];
    protected $casts = [
        'alternative_names' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__places';
        }
    }

    public function searchableAs()
    {
        return 'place_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'additional_name' => $this->additional_name,
            'alternative_names' => is_array($this->alternative_names) ? implode(', ', $this->alternative_names) : $this->alternative_names,
        ];
    }    

    public function letters()
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__letter_place'
            : 'letter_place';

        return $this->belongsToMany(Letter::class, $pivotTable, 'place_id', 'letter_id')
            ->withPivot('role', 'position', 'marked');
    }

    public function newEloquentBuilder($query)
    {
        return new PlaceBuilder($query);
    }
}
