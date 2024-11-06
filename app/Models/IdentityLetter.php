<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;
use Illuminate\Support\Facades\Log;

class IdentityLetter extends Model
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
            ? "{$this->getTenantPrefix()}__identity_letter"
            : 'global_identity_letter';
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
     * Define the relationship to the Letter model.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function letter()
    {
        try {
            return $this->belongsTo(Letter::class, 'letter_id')->withPivot('role');
        } catch (\Exception $e) {
            Log::error("Error in IdentityLetter::letter relationship: {$e->getMessage()}");
            throw $e;
        }
    }
}
