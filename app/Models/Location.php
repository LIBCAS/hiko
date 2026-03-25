<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use App\Builders\LocationBuilder;
use App\Enums\LocationType;
use App\Models\Manifestation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Location",
    required: ["name", "type"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "scope", type: "string", example: "local", readOnly: true),
        new OA\Property(property: "reference", type: "string", example: "local-25", readOnly: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "type", type: "string", enum: ["repository", "collection", "archive"]),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class Location extends Model
{
    use HasFactory;
    use Searchable;

    protected $connection = 'tenant';
    protected $table;
    protected $guarded = ['id'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__locations';
        }
    }

    public function searchableAs(): string
    {
        return 'location_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function newEloquentBuilder($query): LocationBuilder
    {
        return new LocationBuilder($query);
    }

    public static function types(): array
    {
        return LocationType::values();
    }

    public function manifestations()
    {
        return $this->hasMany(Manifestation::class, 'repository_id')
            ->orWhere('archive_id', $this->id)
            ->orWhere('collection_id', $this->id);
    }

    public function manifestationsAsRepository()
    {
        return $this->hasMany(Manifestation::class, 'repository_id');
    }

    public function manifestationsAsArchive()
    {
        return $this->hasMany(Manifestation::class, 'archive_id');
    }

    public function manifestationsAsCollection()
    {
        return $this->hasMany(Manifestation::class, 'collection_id');
    }
}
