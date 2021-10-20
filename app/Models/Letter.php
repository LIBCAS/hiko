<?php

namespace App\Models;

use App\Models\User;
use App\Models\Place;
use App\Models\Keyword;
use App\Models\Identity;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Model;
use Spatie\Translatable\HasTranslations;
use Spatie\MediaLibrary\Models\Media;
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

    public function registerMediaConversions(Media $media = null)
    {
        $this->addMediaConversion('thumb')
            ->width(320);
    }

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

    public function users()
    {
        return $this->belongsToMany(User::class);
    }

    protected static function boot()
    {
        parent::boot();

        self::creating(function ($model) {
            self::computeDate($model);
            $user = Auth::user();
            $user = $user ? $user : User::first();
            $model->uuid = Str::uuid();
            $model->history = date('Y-m-d H:i:s') . ' – ' . $user->name . "\n";
            $model->date_computed = self::computeDate($model);
        });

        self::created(function ($model) {
            $user = Auth::user();
            $user = $user ? $user : User::first();
            $model->users()->attach($user->id);
        });

        self::updating(function ($model) {
            $user = Auth::user();
            $model->history = $model->history . date('Y-m-d H:i:s') . ' – ' . $user->name . "\n";
            $model->date_computed = self::computeDate($model);
            $model->users()->syncWithoutDetaching($user->id);
        });
    }

    protected static function computeDate($model)
    {
        $dates = [
            $model->date_year ? (string) $model->date_year : '0001',
            $model->date_month ? str_pad($model->date_month, 2, '0', STR_PAD_LEFT) : '01',
            $model->date_day ? str_pad($model->date_day, 2, '0', STR_PAD_LEFT) : '01',
        ];

        return implode('-', $dates);
    }
}
