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

    /**
     * The connection name for the model.
     *
     * @var string
     */
    protected $connection = 'tenant';

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = ['abstract'];

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'uuid',
        'name',
        'title',
        'content',
        'status',
        'date_computed',
        'pretty_date',
        'date_is_range',
        'pretty_range_date',
        'date_marked',
        'date_uncertain',
        'date_approximate',
        'date_inferred',
        'date_note',
        'author_inferred',
        'author_uncertain',
        'author_note',
        'recipient_inferred',
        'recipient_uncertain',
        'recipient_note',
        'origin_inferred',
        'origin_uncertain',
        'origin_note',
        'destination_inferred',
        'destination_uncertain',
        'destination_note',
        'languages',
        'keywords',
        'abstract_cs',
        'abstract_en',
        'incipit',
        'explicit',
        'mentioned',
        'people_mentioned_note',
        'notes_private',
        'notes_public',
        'copyright',
        'status',
        // Add any other necessary fields here
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'copies' => 'array',
        'related_resources' => 'array',
        'date_uncertain' => 'boolean',
        'date_approximate' => 'boolean',
        'date_inferred' => 'boolean',
        'date_is_range' => 'boolean',
        'author_inferred' => 'boolean',
        'author_uncertain' => 'boolean',
        'recipient_inferred' => 'boolean',
        'recipient_uncertain' => 'boolean',
        'origin_inferred' => 'boolean',
        'origin_uncertain' => 'boolean',
        'destination_inferred' => 'boolean',
        'destination_uncertain' => 'boolean',
    ];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * Letter constructor.
     *
     * Dynamically sets the table name based on tenancy.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (tenant()) { // Assuming tenant() is a helper to check tenancy
            $tenantPrefix = tenant()->table_prefix;
            $this->table = "{$tenantPrefix}__letters";
        } else {
            $this->table = 'global_letters';
        }
    }

    /**
     * Register the media conversions for the model.
     *
     * @param \Spatie\MediaLibrary\MediaCollections\Models\Media|null $media
     * @return void
     */
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

    /**
     * The identities that belong to the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function identities(): BelongsToMany
    {
        $pivotTable = tenant() 
            ? tenant()->table_prefix . '__identity_letter'
            : 'global_identity_letter';

        return $this->belongsToMany(
            Identity::class,
            $pivotTable,
            'letter_id',
            'identity_id'
        )->withPivot(['role', 'position', 'marked', 'salutation']);
    }

    /**
     * The places that belong to the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function places(): BelongsToMany
    {
        $pivotTable = tenant() 
            ? tenant()->table_prefix . '__letter_place'
            : 'global_letter_place';

        return $this->belongsToMany(
            Place::class,
            $pivotTable,
            'letter_id',
            'place_id'
        )->withPivot(['role', 'position', 'marked']);
    }

    /**
     * The origins associated with the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function origins(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'origin');
    }

    /**
     * The destinations associated with the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function destinations(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'destination');
    }

    /**
     * The keywords associated with the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     * @throws \Exception
     */
    public function keywords(): BelongsToMany
    {
        if (!tenant()) { // Assuming tenant() returns null if not initialized
            throw new \Exception('Tenancy is not initialized.');
        }

        $pivotTable = tenant()->table_prefix . '__keyword_letter';

        return $this->belongsToMany(
            Keyword::class,
            $pivotTable,
            'letter_id',
            'keyword_id'
        );
    }

    /**
     * Get all media associated with the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        return $this->morphMany(Media::class, 'model')
            ->where('model_type', self::class);
    }

    /**
     * The authors of the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function authors(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'author');
    }

    /**
     * The recipients of the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function recipients(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'recipient');
    }

    /**
     * The mentioned identities in the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function mentioned(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'mentioned');
    }

    /**
     * The users associated with the letter.
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsToMany
     */
    public function users(): BelongsToMany
    {
        $pivotTable = tenant() 
            ? tenant()->table_prefix . '__letter_user'
            : 'global_letter_user';

        return $this->belongsToMany(
            User::class,
            $pivotTable,
            'letter_id',
            'user_id'
        )->withPivot(['letter_id', 'user_id']);
    }

    /**
     * Accessor for the pretty_date attribute.
     *
     * @return string
     */
    public function getPrettyDateAttribute(): string
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    /**
     * Format the date components into a readable string.
     *
     * @param mixed $day
     * @param mixed $month
     * @param mixed $year
     * @return string
     */
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

    /**
     * Define a new Eloquent builder for the model.
     *
     * @param \Illuminate\Database\Query\Builder $query
     * @return \App\Builders\LetterBuilder
     */
    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }

    /**
     * Scope a query to only include published letters.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopePublished($query)
    {
        return $query->where('status', 'publish');
    }

    /**
     * Searchable array for Laravel Scout.
     *
     * @return array
     */
    public function toSearchableArray()
    {
        return [
            'uuid' => $this->uuid,
            'name' => $this->name,
            'content' => $this->content,
            'languages' => $this->languages,
            'keywords' => $this->keywords->pluck('name')->toArray(),
            // Add other searchable fields as needed
        ];
    }
}
