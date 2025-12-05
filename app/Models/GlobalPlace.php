<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

class GlobalPlace extends Model
{
    protected $table = 'global_places';
    protected $guarded = ['id'];

    protected $casts = [
        'alternative_names' => 'array',
        'latitude' => 'float',
        'longitude' => 'float',
    ];

    /**
     * Relationship with the Letter model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function letters(): BelongsToMany
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : '';

        return $this->belongsToMany(
            Letter::class,
            "{$tenantPrefix}__letter_place", // Pivot table
            'global_place_id',
            'letter_id'
        )->withPivot('role', 'position', 'marked');
    }
}
