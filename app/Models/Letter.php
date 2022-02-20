<?php

namespace App\Models;

use App\Models\User;
use App\Models\Place;
use App\Models\Keyword;
use App\Models\Identity;
use Spatie\MediaLibrary\Models\Media;
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

    public function getPrettyDateAttribute()
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    public function getPrettyRangeDateAttribute()
    {
        return $this->formatDate($this->range_day, $this->range_month, $this->range_year);
    }

    public function getNameAttribute()
    {
        $identities = $this->identities->groupBy('pivot.role')->toArray();
        $places = $this->places->groupBy('pivot.role')->toArray();

        $author = isset($identities['author']) ? $identities['author'][0] : [];
        $recipient = isset($identities['recipient']) ? $identities['recipient'][0] : [];
        $origin = isset($places['origin']) ? $places['origin'][0] : [];
        $destination = isset($places['destination']) ? $places['destination'][0] : [];

        $title = "{$this->pretty_date} ";
        $title .= $author ? $author['name'] . ' ' : '';
        $title .= $origin ? "({$origin['name']}) " : '';
        $title .= $recipient || $destination ? 'to ' : '';
        $title .= $recipient ? $recipient['name'] . ' ' : '';
        $title .= $destination ? "({$destination['name']}) " : '';

        return $title;
    }

    protected function formatDate($day, $month, $year)
    {
        $day = $day && $day != 0 ? $day : '?';
        $month = $month && $month != 0 ? $month : '?';
        $year = $year && $year != 0 ? $year : '????';

        if ($year == '????' && $month == '?' && $day == '?') {
            return '?';
        }

        return "{$day}. {$month}. {$year}";
    }
}
