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

        if (tenancy()->initialized) {
            $this->setTable($this->getTenantPrefix() . '__professions');
        } else {
            $this->setTable('global_professions');
        }
    }

    public function profession_category()
    {
        return $this->belongsTo(tenancy()->initialized ? ProfessionCategory::class : GlobalProfessionCategory::class, 'profession_category_id');
    }

    public function identities()
    {
        $relatedModel = tenancy()->initialized ? Identity::class : GlobalIdentity::class;
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
