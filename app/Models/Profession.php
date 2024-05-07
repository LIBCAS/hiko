<?php

namespace App\Models;

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

    public array $translatable = ['name'];

    protected $guarded = ['id'];

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

    public function newEloquentBuilder($query):ProfessionBuilder
    {
        return new ProfessionBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
