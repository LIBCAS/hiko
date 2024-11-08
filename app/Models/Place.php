<?php

namespace App\Models;

use App\Builders\PlaceBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Stancl\Tenancy\Facades\Tenancy;

class Place extends Model
{
    use HasFactory;
    use Searchable;

    protected $connection = 'tenant';
    protected $guarded = ['id'];
    protected $casts = [
        'alternative_names' => 'json',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__places';
        }
    }

    public function searchableAs()
    {
        return 'place_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'alternative_names' => is_array($this->alternative_names) ? implode(', ', $this->alternative_names) : $this->alternative_names,
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
}
