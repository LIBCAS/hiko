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
use Spatie\MediaLibrary\MediaCollections\Models\Media as SpatieMedia;
use Spatie\Image\Manipulations;
use Illuminate\Support\Facades\Storage;

/**
 * The Letter model represents an entry in the tenant-based "letters" table,
 * implementing Spatie Media Library, text search (Scout), and translatable fields.
 */
class Letter extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasFactory, Searchable;

    // Constants for approval status
    const APPROVED = 1;
    const NOT_APPROVED = 0;

    /**
     * Because we're in a multi-tenant setup, we point to a 'tenant' connection.
     */
    protected $connection = 'tenant';

    /**
     * Spatie's translatable fields (stored as JSON).
     */
    public $translatable = ['abstract'];

    /**
     * We guard 'id' and 'uuid' from mass-assignment.
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * The table name is set dynamically in the constructor depending on the current tenant prefix.
     */
    protected $table;

    /**
     * Here we define how each column from the letters table is cast.
     * This ensures JSON fields become arrays, booleans become bool, etc.
     */
    protected $casts = [
        // Basic integers
        'date_year'   => 'integer',
        'date_month'  => 'integer',
        'date_day'    => 'integer',
        'range_year'  => 'integer',
        'range_month' => 'integer',
        'range_day'   => 'integer',

        // Booleans for tinyint(1)
        'date_uncertain'         => 'boolean',
        'date_approximate'       => 'boolean',
        'date_inferred'          => 'boolean',
        'date_is_range'          => 'boolean',
        'author_uncertain'       => 'boolean',
        'author_inferred'        => 'boolean',
        'recipient_uncertain'    => 'boolean',
        'recipient_inferred'     => 'boolean',
        'destination_uncertain'  => 'boolean',
        'destination_inferred'   => 'boolean',
        'origin_uncertain'       => 'boolean',
        'origin_inferred'        => 'boolean',
        'approval'               => 'boolean',

        // date_computed is a DATE column => cast to date (Carbon)
        'date_computed' => 'date',

        // JSON array fields
        'copies'            => 'array',
        'related_resources' => 'array',

        // Strings
        'date_marked'           => 'string',
        'date_note'             => 'string',
        'author_note'           => 'string',
        'recipient_note'        => 'string',
        'destination_note'      => 'string',
        'origin_note'           => 'string',
        'people_mentioned_note' => 'string',
        'explicit'              => 'string',
        'incipit'               => 'string',
        'content'               => 'string',
        'content_stripped'      => 'string',
        'history'               => 'string',
        'copyright'             => 'string',
        'languages'             => 'string',
        'notes_private'         => 'string',
        'notes_public'          => 'string',
        'status'                => 'string',
    ];

    /**
     * Constructor sets the table name with tenant prefix if tenancy is initialized.
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $prefix = tenancy()->tenant->table_prefix;
            $this->table = "{$prefix}__letters";
        } else {
            $this->table = 'default_letters'; // fallback
        }
    }

    /**
     * Spatie Media Library conversions registration.
     * Allows defining image manipulations (like thumbnails/watermarks).
     */
    public function registerMediaConversions(?SpatieMedia $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(180);

        if (Storage::disk('local')->exists('public/watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                 ->watermark(storage_path('app/public/watermark/logo.png'))
                 ->watermarkPosition(Manipulations::POSITION_CENTER);
        }
    }

    /**
     * Many-to-many to Identity model (with pivot: role, position, marked, salutation).
     */
    public function identities(): BelongsToMany
    {
        $pivot = function_exists('tenancy') && tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__identity_letter'
            : 'default_identity_letter';

        return $this->belongsToMany(
            Identity::class,
            $pivot,
            'letter_id',
            'identity_id'
        )->withPivot(['role', 'position', 'marked', 'salutation']);
    }

    /**
     * Many-to-many to Place model (with pivot: role, position, marked).
     */
    public function places(): BelongsToMany
    {
        $pivot = function_exists('tenancy') && tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__letter_place'
            : 'global_letter_place';

        return $this->belongsToMany(
            Place::class,
            $pivot,
            'letter_id',
            'place_id'
        )->withPivot(['role', 'position', 'marked']);
    }

    /**
     * Helper to filter pivot "places" by role = 'origin'.
     */
    public function origins(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'origin');
    }

    /**
     * Helper to filter pivot "places" by role = 'destination'.
     */
    public function destinations(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'destination');
    }

    /**
     * Many-to-many with global or tenant-based Keyword model.
     */
    public function keywords(): BelongsToMany
    {
        $pivot = function_exists('tenancy') && tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__keyword_letter'
            : 'global_keyword_letter';
    
        return $this->belongsToMany(GlobalKeyword::class, $pivot, 'letter_id', 'keyword_id');
    }    

    /**
     * Polymorphic relationship to Media model, restricting model_type = Letter class.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(\App\Models\Media::class, 'model')
                    ->where('model_type', self::class);
    }

    /**
     * Relationship to get authors from pivot (role = 'author').
     */
    public function authors(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'author');
    }

    /**
     * Relationship to get recipients from pivot (role = 'recipient').
     */
    public function recipients(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'recipient');
    }

    /**
     * Relationship to get "mentioned" from pivot (role = 'mentioned').
     */
    public function mentioned(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'mentioned');
    }

    /**
     * Many-to-many to User model via letter_user pivot table.
     */
    public function users(): BelongsToMany
    {
        $pivot = function_exists('tenancy') && tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__letter_user'
            : 'global_letter_user';

        return $this->belongsToMany(
            User::class,
            $pivot,
            'letter_id',
            'user_id'
        )->withPivot(['letter_id', 'user_id']);
    }

    /**
     * Returns a nicely formatted string from day/month/year fields,
     * or '?' if they are missing.
     */
    public function getPrettyDateAttribute(): string
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    /**
     * Formats day/month/year with fallback placeholders.
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
     * Overrides the default Eloquent builder with our custom LetterBuilder, if needed.
     */
    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }
}
