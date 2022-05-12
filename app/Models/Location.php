<?php

namespace App\Models;

use Laravel\Scout\Searchable;
use App\Builders\LocationBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;
    use Searchable;

    protected $guarded = ['id'];

    public function searchableAs()
    {
        return 'location_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
        ];
    }

    public function newEloquentBuilder($query)
    {
        return new LocationBuilder($query);
    }
}
