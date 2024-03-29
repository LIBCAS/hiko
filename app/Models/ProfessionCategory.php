<?php

namespace App\Models;

use App\Builders\ProfessionBuilder;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;

class ProfessionCategory extends Model
{
    use HasFactory;
    use HasTranslations;
    use Searchable;

    protected $connection = 'tenant';

    public $translatable = ['name'];

    protected $guarded = ['id'];

    public function searchableAs()
    {
        return 'profession_category_index';
    }

    public function toSearchableArray()
    {
        return [
            'id' => $this->id,
            'cs' => $this->getTranslation('name', 'cs'),
            'en' => $this->getTranslation('name', 'en'),
        ];
    }

    public function identities()
    {
        return $this->belongsToMany(Identity::class);
    }

    public function newEloquentBuilder($query)
    {
        return new ProfessionBuilder($query);
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
