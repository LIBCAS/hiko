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
    use HasTranslations, HasFactory, Searchable;

    protected $connection = 'tenant';

    protected $fillable = ['name', 'global_profession_id'];
    
    public array $translatable = ['name'];

    protected $guarded = ['id'];

    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__professions';
        } else {
            // Handle the case where tenancy is not initialized
            $this->table = 'professions'; // Or throw an exception
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

    public function profession_category()
    {
        return $this->belongsTo(ProfessionCategory::class, 'profession_category_id');
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
