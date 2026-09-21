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

    public static function orderedForLocale(): \Illuminate\Database\Eloquent\Collection
    {
        $locale = app()->getLocale();
        $collator = new \Collator($locale);

        return static::all()->sort(function (self $left, self $right) use ($collator, $locale) {
            $order = $collator->compare($left->getTranslation('name', $locale), $right->getTranslation('name', $locale));

            return $order ?: ($left->getKey() <=> $right->getKey());
        })->values();
    }
}
