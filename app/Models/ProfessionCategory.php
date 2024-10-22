<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use App\Traits\UsesTenantConnection;
use App\Traits\TenantAware;

class ProfessionCategory extends Model
{
    use HasFactory, HasTranslations, UsesTenantConnection, TenantAware {
        UsesTenantConnection::getTenantPrefix insteadof TenantAware;
    }

    protected $guarded = ['id'];
    public $translatable = ['name'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Set table name for tenant-specific categories
        $this->setTable($this->getTenantPrefix() . '__profession_categories');
    }

    /**
     * Get the professions associated with this category.
     */
    public function professions()
    {
        return $this->hasMany(
            tenancy()->initialized ? Profession::class : GlobalProfession::class,
            'profession_category_id'
        );
    }
}
