<?php

namespace App\Models;

use App\Builders\LocationBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Location extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function newEloquentBuilder($query)
    {
        return new LocationBuilder($query);
    }
}
