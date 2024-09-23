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
}
