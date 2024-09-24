<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class GlobalProfession extends Model
{
    protected $connection = 'mysql';
    protected $table = 'global_professions';
    protected $guarded = ['id'];

    protected $casts = [
        'name' => 'array',
    ];
    
    public function getTranslation($key, $locale)
    {
        $value = $this->$key;
        if (is_array($value)) {
            return $value[$locale] ?? null;
        } else {
            $array = json_decode($value, true);
            return $array[$locale] ?? null;
        }
    }
}
