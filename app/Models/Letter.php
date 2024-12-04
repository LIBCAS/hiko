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
use Illuminate\Support\Facades\Log;


class Letter extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasFactory, Searchable;

    /**
     * The database connection that should be used by the model.
     *
     * @var string
     */
    protected $connection = 'tenant';  // Ensure the tenant connection is used

    /**
     * The attributes that are translatable.
     *
     * @var array
     */
    public $translatable = ['abstract'];

    /**
     * The attributes that aren't mass assignable.
     *
     * @var array
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table;

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'copies' => 'array',
        'related_resources' => 'array',
    ];

    /**
     * Constructor to dynamically set the table name based on tenancy.
     *
     * @param array $attributes
     */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        // Dynamically set the tenant-specific table name
        if (tenancy()->initialized) { // Correctly check if tenancy is initialized
            $tenantPrefix = $this->getTenantPrefix();
            $this->table = "{$tenantPrefix}__letters";  // Tenant-specific table name
        } else {
            // Fallback to a global table if tenancy is not initialized
            $this->table = 'global_letters';  // Ensure this table exists
        }
    }

    /**
     * Get the tenant's table prefix.
     *
     * @return string
     */
    protected function getTenantPrefix(): string
    {
        return tenancy()->tenant->table_prefix; // Adjust based on actual tenant data structure
    }

    /**
     * Register media conversions for the Spatie Media Library.
     *
     * @param Media|null $media
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->width(180)
            ->sharpen(10)
            ->nonQueued(); // Ensure conversions are performed synchronously

        if (Storage::disk('public')->exists('watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                ->watermark(storage_path('app/public/watermark/logo.png'))
                ->watermarkPosition(Manipulations::POSITION_CENTER)
                ->watermarkOpacity(50)
                ->nonQueued();
        }
    }

    /**
     * Define the identities relationship.
     *
     * @return BelongsToMany
     */
    public function identities(): BelongsToMany
    {
        try {
            // Determine the pivot table name based on tenancy
            $pivotTable = tenancy()->initialized
                ? "{$this->getTenantPrefix()}__identity_letter"
                : 'global_identity_letter';

            return $this->belongsToMany(
                Identity::class,
                $pivotTable,
                'letter_id',
                'identity_id'
            )->withPivot(['role', 'position', 'marked', 'salutation']);
        } catch (\Exception $e) {
            Log::error('Error in Letter::identities relationship: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Define the places relationship.
     *
     * @return BelongsToMany
     */
    public function places(): BelongsToMany
    {
        try {
            $pivotTable = tenancy()->initialized
                ? "{$this->getTenantPrefix()}__letter_place"
                : 'global_letter_place';

            return $this->belongsToMany(
                Place::class,
                $pivotTable,
                'letter_id',
                'place_id'
            )->withPivot(['role', 'position', 'marked']);
        } catch (\Exception $e) {
            Log::error('Error in Letter::places relationship: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Define the origins relationship.
     *
     * @return BelongsToMany
     */
    public function origins(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'origin');
    }

    /**
     * Define the destinations relationship.
     *
     * @return BelongsToMany
     */
    public function destinations(): BelongsToMany
    {
        return $this->places()->wherePivot('role', 'destination');
    }

    /**
     * Define the keywords relationship.
     *
     * @return BelongsToMany
     */
    public function keywords(): BelongsToMany
    {
        try {
            $pivotTable = tenancy()->initialized
                ? "{$this->getTenantPrefix()}__keyword_letter"
                : 'global_keyword_letter';

            return $this->belongsToMany(
                Keyword::class,
                $pivotTable,
                'letter_id',
                'keyword_id'
            );
        } catch (\Exception $e) {
            Log::error('Error in Letter::keywords relationship: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Define the media relationship using Spatie Media Library.
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphMany
     */
    public function media(): MorphMany
    {
        try {
            $modelType = self::class;

            if (tenancy()->initialized) {
                $tenantPrefix = $this->getTenantPrefix();
                $mediaTable = "{$tenantPrefix}__media";

                return $this->morphMany(Media::class, 'model')
                            ->where('model_type', $modelType)
                            ->from($mediaTable);
            }

            return $this->morphMany(Media::class, 'model')
                        ->where('model_type', $modelType)
                        ->from('global_media');
        } catch (\Exception $e) {
            Log::error('Error in Letter::media relationship: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Define the authors relationship.
     *
     * @return BelongsToMany
     */
    public function authors(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'author');
    }

    /**
     * Define the recipients relationship.
     *
     * @return BelongsToMany
     */
    public function recipients(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'recipient');
    }

    /**
     * Define the mentioned relationship.
     *
     * @return BelongsToMany
     */
    public function mentioned(): BelongsToMany
    {
        return $this->identities()->wherePivot('role', 'mentioned');
    }

    /**
     * Define the users relationship.
     *
     * @return BelongsToMany
     */
    public function users(): BelongsToMany
    {
        try {
            $pivotTable = tenancy()->initialized
                ? "{$this->getTenantPrefix()}__letter_user"
                : 'global_letter_user';

            return $this->belongsToMany(
                User::class,
                $pivotTable,
                'letter_id',
                'user_id'
            )->withPivot(['letter_id', 'user_id']);
        } catch (\Exception $e) {
            Log::error('Error in Letter::users relationship: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Accessor for the pretty date attribute.
     *
     * @return string
     */
    public function getPrettyDateAttribute(): string
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    /**
     * Accessor for the pretty range date attribute.
     *
     * @return string
     */
    public function getPrettyRangeDateAttribute(): string
    {
        return $this->formatDate($this->range_day, $this->range_month, $this->range_year);
    }

    /**
     * Accessor for the name attribute.
     *
     * @return string
     */
    public function getNameAttribute(): string
    {
        $identities = $this->identities()
            ->select([
                'identities.id as identity_id', // Explicit alias
                'identities.name as identity_name',
                'identity_letter.role as pivot_role',
            ])
            ->get()
            ->groupBy('pivot_role') // Group by role
            ->toArray();

        $places = $this->places()
            ->select([
                'places.id as place_id', // Explicit alias
                'places.name as place_name',
                'letter_place.role as pivot_role',
            ])
            ->get()
            ->groupBy('pivot_role') // Group by role
            ->toArray();

        // Build title with basic data
        $titleParts = [$this->pretty_date];

        $author = $identities['author'][0]['identity_name'] ?? null;
        $recipient = $identities['recipient'][0]['identity_name'] ?? null;
        $origin = $places['origin'][0]['place_name'] ?? null;
        $destination = $places['destination'][0]['place_name'] ?? null;

        if ($author) $titleParts[] = $author;
        if ($origin) $titleParts[] = "({$origin})";
        if ($recipient || $destination) $titleParts[] = '→';
        if ($recipient) $titleParts[] = $recipient;
        if ($destination) $titleParts[] = "({$destination})";

        return implode(' ', $titleParts);
    }

    /**
     * Override the Eloquent builder for custom query logic.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return LetterBuilder
     */
    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
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
     * Encode attributes to JSON without escaping Unicode characters.
     *
     * @param mixed $value
     * @return string
     */
    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }

    /**
     * Get the indexable data array for Laravel Scout.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        // Define the array of data to be indexed by Scout
        return [
            'id' => $this->id,
            'title' => $this->title,
            'content_stripped' => $this->content_stripped,
            'abstract' => $this->abstract,
            // Add other fields as needed
        ];
    }

    /**
     * Specify the Scout index name.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'letters_index';
    }
}
