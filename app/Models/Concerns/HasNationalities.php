<?php

namespace App\Models\Concerns;

use App\Models\Nationality;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;

trait HasNationalities
{
    protected ?array $pendingNationalities = null;

    public function nationalities(): BelongsToMany
    {
        $global = $this->getTable() === 'global_identities';
        $pivot = $global ? 'global_identity_nationality' : substr($this->getTable(), 0, -10) . 'identity_nationality';
        return $this->belongsToMany(
            Nationality::class,
            $pivot,
            $global ? 'global_identity_id' : 'identity_id',
            'nationality_id'
        )
            ->withPivot('position')->orderByPivot('position')->orderBy('nationalities.id');
    }

    public function syncNationalities(array $ids): void
    {
        $values = [];
        foreach (array_values(array_unique(array_map('intval', $ids))) as $position => $id) {
            $values[$id] = ['position' => $position];
        }
        $this->nationalities()->sync($values);
        $this->unsetRelation('nationalities');
    }

    public function setNationalitiesAttribute(array $ids): void
    {
        $this->pendingNationalities = array_values($ids);
    }

    // Keep parent record and ordered assignments in one transaction.
    public function save(array $options = [])
    {
        return $this->getConnection()->transaction(function () use ($options) {
            $saved = parent::save($options);
            if ($saved && $this->pendingNationalities !== null) {
                $this->syncNationalities($this->pendingNationalities);
                $this->pendingNationalities = null;
            }
            return $saved;
        });
    }

    public function nationalityNames(): string
    {
        return $this->nationalities->map(fn($item) => $item->name)->implode(', ');
    }

    public function scopeWithNationalityName($query, string $name)
    {
        \App\Support\NationalityFilter::apply($query, $name);
        return $query;
    }
}
