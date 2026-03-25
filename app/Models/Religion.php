<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Religion",
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "slug", type: "string"),
        new OA\Property(property: "name", type: "string", description: "Localized religion name"),
        new OA\Property(property: "path_text", type: "string", description: "Localized full path"),
        new OA\Property(property: "is_active", type: "boolean"),
        new OA\Property(property: "sort_order", type: "integer"),
    ]
)]
class Religion extends Model
{
    protected $table = 'religions';
    protected $fillable = ['slug','name','is_active','sort_order','path_text','lower_path_text'];
    protected $casts = ['is_active' => 'bool'];

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }
    public function scopeOrderForTree(Builder $q): Builder { return $q->orderBy('sort_order')->orderBy('name'); }

    public function pathText(): string
    {
        return $this->path_text ?? $this->name;
    }
}
