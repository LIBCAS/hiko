<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Laravel\Scout\Searchable;
use App\Builders\ProfessionBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ProfessionCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    use Searchable;

    protected $table = 'global_profession_categories';
    protected $connection = 'tenant';

    public array $translatable = ['name'];

    protected $guarded = ['id'];

    public function searchableAs(): string
    {
        return 'profession_category_index';
    }

    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    public function professions()
    {
        return $this->hasMany('App\Models\Profession', 'profession_category_id');
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
