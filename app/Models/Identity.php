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

        // Use tenant-specific table if tenancy is initialized, otherwise fallback to the global table
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
       // Dynamically set table name only when tenant is initialized
        $relatedModel = tenancy()->initialized ? Profession::class : GlobalProfession::class;

        return $this->belongsToMany(
            $relatedModel,
            tenancy()->initialized ? $this->getTenantPrefix() . '__identity_profession' : 'global_identity_profession',
            'identity_id',
            'profession_id'
        );
    }
}
