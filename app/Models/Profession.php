<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

class Profession extends Model
{
    use UsesTenantConnection, HasTranslations;

    protected $guarded = ['id'];

    // Define which attributes are translatable
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
    
       // Dynamically set table name only when tenant is initialized
        $this->setTable(
            tenancy()->initialized ? $this->getTenantPrefix() . '__professions' : 'global_professions'
        );
    }     

    /**
     * Get the profession category associated with this profession.
     */
    public function profession_category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class, 'profession_category_id');
    }

    /**
     * Get identities associated with this profession.
     */
    public function identities()
    {
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession'
            : 'global_identity_profession';

        return $this->belongsToMany(
            Identity::class,
            $pivotTable,
            'profession_id',
            'identity_id'
        );
    }
}
