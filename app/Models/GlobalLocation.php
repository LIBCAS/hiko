<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use App\Enums\LocationType;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalLocation",
    required: ["name", "type"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "type", type: "string", enum: ["repository", "collection", "archive"]),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class GlobalLocation extends Model
{
    use Searchable;

    protected $guarded = ['id'];

    public function searchableAs(): string
    {
        return 'global_location_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'type' => $this->type,
        ];
    }

    public static function types(): array
    {
        return LocationType::values();
    }
}
