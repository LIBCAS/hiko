<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Item extends Model
{

  protected $casts = [
    'metadata' => 'array',
  ];


    protected $fillable = [
        'title',
        'full_text',
        'metadata',
        'summary',
        'language'
      ];
}
