<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;
use Illuminate\Support\Facades\Log;

class IdentityProfessionCategory extends Model
{
    use UsesTenantConnection;

    protected $guarded = ['id'];
    protected $connection = 'tenant'; // Ensures tenant connection is used
    public $timestamps = false;       // Disables automatic timestamps
    protected $table;                 // Dynamically set table name

    /**
     * Constructor to dynamically set the table name based on tenancy.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Dynamically set the tenant-specific table name
        $this->table = $this->isTenancyInitialized()
            ? "{$this->getTenantPrefix()}__identity_profession_category"
            : 'global_identity_profession_category';
    }

    /**
     * Check if tenancy is initialized.
     *
     * @return bool
     */
    protected function isTenancyInitialized(): bool
    {
        return tenancy()->initialized;
    }

    /**
     * Get the tenant's table prefix.
     *
     * @return string|null
     */
    protected function getTenantPrefix(): ?string
    {
        return tenancy()->tenant ? tenancy()->tenant->table_prefix : null;
    }

    /**
     * Define the relationship to the Identity model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function identity()
    {
        return $this->belongsTo(Identity::class, 'identity_id');
    }

    /**
     * Define the relationship to the ProfessionCategory model, handling both tenant and global contexts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function professionCategory()
    {
        try {
            $relatedModel = $this->isTenancyInitialized() ? ProfessionCategory::class : GlobalProfessionCategory::class;

            return $this->belongsTo($relatedModel, 'profession_category_id');
        } catch (\Exception $e) {
            Log::error("Error in IdentityProfessionCategory::professionCategory relationship: {$e->getMessage()}");
            throw $e;
        }
    }
}
