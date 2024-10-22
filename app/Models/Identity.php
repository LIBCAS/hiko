<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;

class Identity extends Model
{
    use UsesTenantConnection;

    protected $guarded = ['id'];

    /**
     * Constructor to set the correct tenant table
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    
        // Set tenant-specific or global table
        if (tenancy()->initialized) {
            $this->setTable($this->getTenantPrefix() . '__identities');
        } else {
            $this->setTable('global_identities');
        }
    }    

    /**
     * Get professions associated with this identity.
     */
    public function professions()
    {
        // Use tenant-specific profession model if tenancy is initialized, otherwise global
        $relatedModel = tenancy()->initialized ? Profession::class : GlobalProfession::class;

        return $this->belongsToMany(
            $relatedModel,
            tenancy()->initialized ? $this->getTenantPrefix() . '__identity_profession' : 'global_identity_profession',
            'identity_id',
            'profession_id'
        );
    }

    public function scopeFilter($query, $filters)
    {
        if (!empty($filters['cs'])) {
            $query->where('name', 'LIKE', "%{$filters['cs']}%");
        }
    
        if (!empty($filters['en'])) {
            $query->where('name', 'LIKE', "%{$filters['en']}%");
        }
    
        if (!empty($filters['category'])) {
            $query->whereHas('professions.profession_category', function ($q) use ($filters) {
                $q->where('name', 'LIKE', "%{$filters['category']}%");
            });
        }
    
        return $query;
    }    
}
