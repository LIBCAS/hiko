<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use App\Traits\UsesTenantConnection;

class Profession extends Model
{
    use UsesTenantConnection, HasTranslations;

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->setTable(tenancy()->initialized ? $this->getTenantPrefix() . '__professions' : 'global_professions');
    }

    /**
     * Get the profession category associated with this profession.
     */
    public function profession_category()
    {
        return $this->belongsTo(tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class, 'profession_category_id');
    }

    /**
     * Get identities associated with this profession.
     */
    public function identities()
    {
        $relatedModel = tenancy()->initialized ? 'App\\Models\\Identity' : 'App\\Models\\GlobalIdentity';
        $pivotTable = tenancy()->initialized
            ? $this->getTenantPrefix() . '__identity_profession'
            : 'global_identity_profession';

        return $this->belongsToMany(
            $relatedModel,
            $pivotTable,
            'profession_id',
            'identity_id'
        );
    }
}
