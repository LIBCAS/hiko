<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Stancl\Tenancy\Facades\Tenancy;

class Identity extends Model
{
    protected $guarded = ['id'];
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);
        $this->table = $this->isTenancyInitialized()
            ? "{$this->getTenantPrefix()}__identities"
            : 'global_identities';
    }

    protected function isTenancyInitialized(): bool
    {
        return tenancy()->initialized;
    }

    protected function getTenantPrefix(): ?string
    {
        return tenancy()->tenant ? tenancy()->tenant->table_prefix : '';
    }

    public function professions(): BelongsToMany
    {
        $pivotTable = $this->isTenancyInitialized()
            ? "{$this->getTenantPrefix()}__identity_profession"
            : 'global_identity_profession';

        return $this->belongsToMany(Profession::class, $pivotTable, 'identity_id', 'profession_id')
                    ->withPivot('position', 'global_profession_id');
    }

    public function globalProfessions(): BelongsToMany
    {
        return $this->belongsToMany(GlobalProfession::class, 'global_identity_profession', 'identity_id', 'profession_id');
    }

    public function profession_categories(): BelongsToMany
    {
        $pivotTable = $this->isTenancyInitialized()
            ? "{$this->getTenantPrefix()}__identity_profession_category"
            : 'global_identity_profession_category';

        return $this->belongsToMany(ProfessionCategory::class, $pivotTable, 'identity_id', 'profession_category_id')
                    ->withPivot('position');
    }

    public function letters(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__keyword_letter'
            : 'keyword_letter';
    
        return $this->belongsToMany(Letter::class, $pivotTable, 'keyword_id', 'letter_id');
    }    

    public function scopeSearch($query, $filters)
    {
        if (!empty($filters['search_term'])) {
            $query->where('name', 'like', '%' . $filters['search_term'] . '%');
        }

        if (!empty($filters['type'])) {
            $query->where('type', $filters['type']);
        }

        return $query;
    }

    public function scopeWithLocalAndGlobalProfessions($query)
    {
        $query->with(['professions' => function ($localQuery) {
            $localQuery->select('id', 'name')
                       ->addSelect(DB::raw("'Local' as scope"));
        }]);

        // Load global professions based on tenant's identity_profession table
        if ($this->isTenancyInitialized()) {
            $tenantTablePrefix = $this->getTenantPrefix() . '__identity_profession';
            $query->with(['globalProfessions' => function ($globalQuery) use ($tenantTablePrefix) {
                $globalQuery->selectRaw("global_professions.id as global_profession_id, 
                                         JSON_UNQUOTE(JSON_EXTRACT(global_professions.name, '$.en')) as name, 
                                         'Global' as scope")
                             ->join($tenantTablePrefix, "{$tenantTablePrefix}.global_profession_id", '=', 'global_professions.id')
                             ->whereNotNull("{$tenantTablePrefix}.global_profession_id");
            }]);
        }

        return $query;
    }    
}
