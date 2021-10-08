<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Keyword extends Model
{
    use HasTranslations;
    use HasFactory;

    public $translatable = ['name'];

    protected $guarded = ['id'];
}
