<?php

namespace App\Models;

use App\Models\Place;
use App\Models\Keyword;
use App\Models\Identity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Letter extends Model implements HasMedia
{
    use HasTranslations;
    use HasMediaTrait;
    use HasFactory;

    public $translatable = ['abstract'];

    protected $guarded = ['id', 'uuid'];

    protected $casts = [
        'copies' => 'array',
        'related_resources' => 'array',
    ];

    public function identities()
    {
        return $this->belongsToMany(Identity::class)
            ->withPivot('position', 'role', 'marked', 'salutation')
            ->orderBy('pivot_position', 'asc');
    }

    public function places()
    {
        return $this->belongsToMany(Place::class)
            ->withPivot('position', 'role', 'marked')
            ->orderBy('pivot_position', 'asc');
    }

    public function origins()
    {
        return $this->places()->where('role', '=', 'origin');
    }

    public function destinations()
    {
        return $this->places()->where('role', '=', 'destination');
    }

    public function keywords()
    {
        return $this->belongsToMany(Keyword::class);
    }

    public function authors()
    {
        return $this->identities()->where('role', '=', 'author');
    }

    public function recipients()
    {
        return $this->identities()->where('role', '=', 'recipient');
    }

    public function mentioned()
    {
        return $this->identities()->where('role', '=', 'mentioned');
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            $user = Auth::user();
            $user = $user ? $user->name : User::first()->name;
            $model->uuid = Str::uuid();
            $model->history = date('Y-m-d H:i:s') . ' – ' . $user . "\n";
        });

        self::updating(function ($model) {
            $user = Auth::user();
            $user = $user ? $user->name : User::first()->name;
            $model->history = $model->history . date('Y-m-d H:i:s') . ' – ' . $user . "\n";
        });
    }
}
