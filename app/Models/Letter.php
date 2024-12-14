<?php

namespace App\Models;

use App\Builders\LetterBuilder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Laravel\Scout\Searchable;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\MediaLibrary\HasMedia;
use Spatie\Translatable\HasTranslations;
use Illuminate\Support\Facades\Storage;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\MediaCollections\Models\Media;

class Letter extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasFactory, Searchable;

    protected $connection = 'tenant';

    public $translatable = ['abstract'];
    protected $guarded = ['id', 'uuid'];
    protected $table;
    protected $casts = [
        'copies' => 'array',
        'related_resources' => 'array',
    ];

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = "{$tenantPrefix}__letters";
        } else {
            $this->table = 'global_letters';
        }
    }

    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(180)
            ->sharpen(10)
            ->nonQueued();

        if (Storage::disk('public')->exists('watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                ->watermark(storage_path('app/public/watermark/logo.png'))
                ->watermarkPosition(Manipulations::POSITION_CENTER)
                ->watermarkOpacity(50)
                ->nonQueued();
        }
    }

    public function identities(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__identity_letter'
            : 'global_identity_letter';

        return $this->belongsToMany(
            Identity::class,
            $pivotTable,
            'letter_id',
            'identity_id'
        )->withPivot(['role', 'position', 'marked', 'salutation']);
    }

    public function places(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__letter_place'
            : 'global_letter_place';

        return $this->belongsToMany(
            Place::class,
            $pivotTable,
            'letter_id',
            'place_id'
        )->withPivot(['role', 'position', 'marked']);
    }

    public function origins(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'origin');
    }

    public function destinations(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'destination');
    }

    public function keywords(): BelongsToMany
    {
        if (!tenancy()->initialized) {
            throw new \Exception('Tenancy is not initialized.');
        }

        $pivotTable = tenancy()->tenant->table_prefix . '__keyword_letter';

        return $this->belongsToMany(
            Keyword::class,
            $pivotTable,
            'letter_id',
            'keyword_id'
        );
    }

    public function media(): MorphMany
    {
        return $this->morphMany(\App\Models\Media::class, 'model')
            ->where('model_type', self::class);
    }

    public function authors(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'author');
    }

    public function recipients(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'recipient');
    }

    public function mentioned(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'mentioned');
    }

    public function users(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__letter_user'
            : 'global_letter_user';

        return $this->belongsToMany(
            User::class,
            $pivotTable,
            'letter_id',
            'user_id'
        )->withPivot(['letter_id', 'user_id']);
    }

    public function getPrettyDateAttribute(): string
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    protected function formatDate($day, $month, $year): string
    {
        $day = ($day && $day != 0) ? $day : '?';
        $month = ($month && $month != 0) ? $month : '?';
        $year = ($year && $year != 0) ? $year : '????';

        if ($year === '????' && $month === '?' && $day === '?') {
            return '?';
        }

        return "{$day}. {$month}. {$year}";
    }

    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }
}
