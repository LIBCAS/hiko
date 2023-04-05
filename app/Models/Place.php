<?php

namespace App\Models;

use App\Builders\PlaceBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Place extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = ['id'];

    public function searchableAs()
    {
        return 'place_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function letters()
    {
        return $this->belongsToMany(Letter::class);
    }

    public function newEloquentBuilder($query)
    {
        return new PlaceBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
