<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

class Religion extends Model
{
    protected $table = 'religions';
    protected $fillable = ['slug','name','is_active','sort_order','path_text','lower_path_text'];
    protected $casts = ['is_active' => 'bool'];

    public function scopeActive(Builder $q): Builder { return $q->where('is_active', true); }
    public function scopeOrderForTree(Builder $q): Builder { return $q->orderBy('sort_order')->orderBy('name'); }

    public function pathText(): string
    {
        return $this->path_text ?? $this->name;
    }
}
