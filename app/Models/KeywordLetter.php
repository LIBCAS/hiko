<?php

namespace App\Models;

use App\Traits\UsesTenantConnection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KeywordLetter extends Model
{
    use UsesTenantConnection;
    use HasFactory;

    protected $guarded = ['id'];

    protected $connection = 'tenant';

    public $timestamps = false;

    protected $table;

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
            ? "{$this->getTenantPrefix()}__keyword_letter"
            : 'global_keyword_letter';
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
     * Define the relationship to the Letter model, handling both tenant and global contexts.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function keyword()
    {
        try {
            $relatedModel = $this->isTenancyInitialized() ? Keyword::class : GlobalKeyword::class;

            return $this->belongsTo($relatedModel, 'keyword_id');
        } catch (\Exception $e) {
            Log::error("Error in IdentityProfession::profession relationship: {$e->getMessage()}");
            throw $e;
        }
    }

    /**
     * Define the relationship to GlobalKeyword if `global_keyword_id` is set.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function globalKeyword()
    {
        return $this->belongsTo(GlobalKeyword::class, 'global_keyword_id');
    }
}
