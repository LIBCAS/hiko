<?php

namespace App\Models;

use App\Enums\IdentityType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "GlobalIdentity",
    required: ["name", "type"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "name", type: "string"),
        new OA\Property(property: "surname", type: "string", nullable: true),
        new OA\Property(property: "forename", type: "string", nullable: true),
        new OA\Property(property: "general_name_modifier", type: "string", nullable: true),
        new OA\Property(property: "type", type: "string", enum: ["person", "institution"]),
        new OA\Property(property: "nationality", type: "string", nullable: true),
        new OA\Property(property: "gender", type: "string", nullable: true),
        new OA\Property(property: "birth_year", type: "string", nullable: true),
        new OA\Property(property: "death_year", type: "string", nullable: true),
        new OA\Property(property: "viaf_id", type: "string", nullable: true),
        new OA\Property(property: "note", type: "string", nullable: true),
        new OA\Property(property: "related_identity_resources", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "alternative_names", type: "array", items: new OA\Items(type: "string")),
        new OA\Property(property: "related_names", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(
            property: "professions",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "scope", type: "string"),
                    new OA\Property(property: "reference", type: "string"),
                    new OA\Property(
                        property: "name",
                        type: "object",
                        properties: [
                            new OA\Property(property: "cs", type: "string"),
                            new OA\Property(property: "en", type: "string"),
                        ]
                    ),
                    new OA\Property(property: "category_id", type: "integer", nullable: true),
                ]
            )
        ),
        new OA\Property(property: "religions", type: "array", items: new OA\Items(type: "integer")),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true),
    ]
)]
class GlobalIdentity extends Model
{
    use Searchable;

    protected $table = 'global_identities';

    protected $guarded = ['id'];

    protected $casts = [
        'alternative_names' => 'array',
        'related_names' => 'array',
        'related_identity_resources' => 'array',
    ];

    /**
     * Relationship with Global Professions.
     */
    public function professions(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalProfession::class,
            'global_identity_profession',
            'global_identity_id',
            'global_profession_id'
        )->withPivot('position')->orderBy('position');
    }

    /**
     * Relationship with Religions.
     */
    public function religions(): BelongsToMany
    {
        return $this->belongsToMany(
            Religion::class,
            'global_identity_religion',
            'global_identity_id',
            'religion_id'
        );
    }

    public function syncReligions(?array $ids): void
    {
        $this->religions()->sync($ids ?? []);
    }

    /**
     * Relationship with tenant-local identities linked to this global identity.
     */
    public function localIdentities(): HasMany
    {
        if (!function_exists('tenancy') || !tenancy()->initialized) {
            return $this->hasMany(Identity::class, 'global_identity_id')->whereRaw('1 = 0');
        }

        return $this->hasMany(Identity::class, 'global_identity_id');
    }

    /**
     * Relationship with Letters.
     */
    public function letters(): BelongsToMany
    {
        // If tenancy is not initialized, we cannot determine which letters to link
        if (!function_exists('tenancy') || !tenancy()->initialized) {
            return $this->belongsToMany(Letter::class, null, null, null)->whereRaw('1 = 0');
        }

        $pivotTable = tenancy()->tenant->table_prefix . '__identity_letter';

        return $this->belongsToMany(
            Letter::class,
            $pivotTable,
            'global_identity_id',
            'letter_id'
        )->withPivot('role', 'position', 'marked', 'salutation');
    }

    public static function types(): array
    {
        return IdentityType::values();
    }

    public function searchableAs(): string
    {
        return 'global_identity_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alternative_names' => $this->alternative_names,
        ];
    }
}
