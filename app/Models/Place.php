<?php

namespace App\Models;

use App\Models\Letter;
use App\Builders\PlaceBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Place extends Model
{
    use HasFactory;

    protected $guarded = ['id'];

    public function letters()
    {
        return $this->belongsToMany(Letter::class);
    }

    public function newEloquentBuilder($query)
    {
        return new PlaceBuilder($query);
    }
}
