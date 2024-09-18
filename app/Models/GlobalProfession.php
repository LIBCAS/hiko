<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;

class GlobalProfession extends Model
{
    use HasTranslations;

    public $translatable = ['name'];
    public $timestamps = true;

    protected $connection = 'mysql';
    protected $table = 'global_professions';
    protected $guarded = ['id'];
}