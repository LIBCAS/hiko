<?php

namespace App\Models;

use App\Builders\IdentityBuilder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;

class Identity extends Model
{
    use HasFactory;
    use Searchable;

    protected $connection = 'tenant';

    protected $guarded = ['id'];

    protected $casts = [
        'alternative_names' => 'array',
    ];

    public function searchableAs(): string
    {
        return 'identity_index';
    }

    public function toSearchableArray(): array
    {
        $names = $this->alternative_names;
        $names[] = $this->name;

        $names = collect($names)
            ->reject(function ($name) {
                return empty($name);
            })
            ->unique()
            ->implode(', ');

        return [
            'id' => $this->id,
            'names' => $names,
        ];
    }

    public function professions(): BelongsToMany
    {
        return $this->belongsToMany(Profession::class)
            ->withPivot('position');
    }

    public function profession_categories(): BelongsToMany
    {
        return $this->belongsToMany(ProfessionCategory::class)
            ->withPivot('position');
    }

    public function letters(): BelongsToMany
    {
        return $this->belongsToMany(Letter::class)
            ->withPivot('marked');
    }

    public function getDatesAttribute()
    {
        if (empty($this->birth_year) && empty($this->death_year)) {
            return '';
        }

        if ($this->birth_year && $this->death_year) {
            return "({$this->birth_year}–{$this->death_year})";
        }

        if ($this->birth_year) {
            return "({$this->birth_year}–)";
        }

        if ($this->death_year) {
            return "(–{$this->death_year})";
        }
    }

    public function newEloquentBuilder($query): IdentityBuilder
    {
        return new IdentityBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
