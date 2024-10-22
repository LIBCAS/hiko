<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;

class IdentityLetter extends Model
{
    use UsesTenantConnection;

    protected $guarded = ['id'];
    protected $connection = 'tenant'; // Ensures the tenant connection
    public $incrementing = false; // No auto-incrementing primary key
    public $timestamps = false; // Disables timestamps
    protected $primaryKey = null; // No single primary key

    protected $fillable = ['identity_id', 'letter_id', 'position', 'role', 'marked', 'salutation'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initializeUsesTenantTable(); // Handles tenant-specific table name dynamically
    }
}
