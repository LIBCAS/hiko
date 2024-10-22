<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use App\Traits\UsesTenantConnection;

class IdentityProfessionCategory extends Model
{
    use UsesTenantConnection;

    protected $guarded = ['id'];
    protected $connection = 'tenant';
    public $incrementing = false;
    public $timestamps = false;
    protected $primaryKey = null;

    protected $fillable = ['identity_id', 'profession_category_id', 'position'];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        $this->initializeUsesTenantTable(); // Handles tenant-specific table name dynamically
    }
}
