<?php

namespace App\Models;

use Stancl\Tenancy\Facades\Tenancy;
use App\Builders\ProfessionBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class Profession extends Model
{
    use HasTranslations;
    use HasFactory;
    use Searchable;

    protected $connection = 'tenant';

    protected $fillable = ['name', 'global_profession_id'];
    
    public array $translatable = ['name'];

    protected $guarded = ['id'];

    protected $table;
    
    //protected $casts = [
    //    'name' => 'array', // Cast the 'name' field to an array
    //];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Correctly check if tenancy is initialized in v3.x
        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix; // Get the current tenant's table prefix
            $this->table = $tenantPrefix . '__professions'; // Set the tenant-specific table
        } else {
            // Fallback to the global professions table (if no tenant is initialized)
            $this->table = 'global_professions'; // You can adjust this to suit your needs
        }
    }

    public function searchableAs(): string
    {
        return 'profession_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    public function profession_category(): BelongsTo
    {
        return $this->belongsTo(ProfessionCategory::class);
    }

    public function identities(): BelongsToMany
    {
        return $this->belongsToMany(Identity::class);
    }

    public function newEloquentBuilder($query): ProfessionBuilder
    {
        return new ProfessionBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
