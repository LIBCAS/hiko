<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use OpenApi\Attributes as OA;

#[OA\Schema(schema: 'Nationality', properties: [
    new OA\Property(property: 'id', type: 'integer', readOnly: true),
    new OA\Property(property: 'name', type: 'object', required: ['cs', 'en'], properties: [
        new OA\Property(property: 'cs', type: 'string'),
        new OA\Property(property: 'en', type: 'string'),
    ]),
])]
class Nationality extends Model
{
    use HasTranslations;
    protected $fillable = ['name'];
    public $translatable = ['name'];
}
