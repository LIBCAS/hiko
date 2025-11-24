<?php

namespace App\Models;

use App\Enums\IdentityType;
use App\Models\Religion;
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
        return $this->belongsToMany(
            Profession::class,
            tenancy()->tenant->table_prefix . '__identity_profession'
        )
            ->withPivot('id', 'identity_id', 'profession_id', 'position', 'global_profession_id')
            ->orderBy('position', 'asc');
    }

    public function localProfessions(): BelongsToMany
    {
        return $this->professions();
    }

    public function globalProfessions(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalProfession::class,
            tenancy()->tenant->table_prefix . '__identity_profession',
            'identity_id',
            'global_profession_id'
        )
            ->withPivot('id', 'identity_id', 'profession_id', 'position', 'global_profession_id')
            ->orderBy('position', 'asc');
    }

    public function profession_categories(): BelongsToMany
    {
        if ($this->isTenancyInitialized()) {
            $pivotTable = "{$this->getTenantPrefix()}__identity_profession_category";

            return $this->belongsToMany(
                ProfessionCategory::class,
                $pivotTable,
                'identity_id',
                'profession_category_id'
            )->withPivot('position');
        }

        // Return an empty BelongsToMany relationship to prevent errors
        return $this->belongsToMany(ProfessionCategory::class, null, null, null)
            ->whereRaw('1 = 0'); // Ensures no records are returned
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

    public static function types(): array
    {
        return IdentityType::values();
    }

    public function religions(): BelongsToMany
    {
        $pivotTable = $this->getTenantPrefix() . '__identity_religion';
        return $this->belongsToMany(Religion::class, $pivotTable, 'identity_id', 'religion_id');
    }

    public function syncReligions(?array $ids): void
    {
        $this->religions()->sync($ids ?? []); // null => no rows (no religion)
    }
}
