<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use App\Builders\LetterBuilder;
use App\Models\Identity;
use App\Models\GlobalIdentity;
use App\Models\Place;
use App\Models\Keyword;
use App\Models\GlobalKeyword;
use App\Models\User;
use App\Models\Manifestation;
use League\Flysystem\FileNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Exceptions\InvalidManipulation;
use Illuminate\Support\Facades\Storage;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;
use Spatie\Image\Enums\AlignPosition;
use Spatie\Image\Enums\Unit;
use Spatie\Image\Enums\Fit;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TenantMedia;
use App\Models\GlobalPlace;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;


use OpenApi\Attributes as OA;

#[OA\Schema(
    schema: "Letter",
    required: ["id", "uuid", "created_at", "updated_at"],
    properties: [
        new OA\Property(property: "id", type: "integer", readOnly: true),
        new OA\Property(property: "uuid", type: "string", format: "uuid", readOnly: true),
        new OA\Property(property: "date_year", type: "integer", nullable: true),
        new OA\Property(property: "date_month", type: "integer", nullable: true),
        new OA\Property(property: "date_day", type: "integer", nullable: true),
        new OA\Property(property: "date_marked", type: "string", nullable: true),
        new OA\Property(property: "date_uncertain", type: "boolean"),
        new OA\Property(property: "date_approximate", type: "boolean"),
        new OA\Property(property: "date_inferred", type: "boolean"),
        new OA\Property(property: "date_is_range", type: "boolean"),
        new OA\Property(property: "date_note", type: "string", nullable: true),
        new OA\Property(property: "range_year", type: "integer", nullable: true),
        new OA\Property(property: "range_month", type: "integer", nullable: true),
        new OA\Property(property: "range_day", type: "integer", nullable: true),
        new OA\Property(property: "author_uncertain", type: "boolean"),
        new OA\Property(property: "author_inferred", type: "boolean"),
        new OA\Property(property: "author_note", type: "string", nullable: true),
        new OA\Property(property: "recipient_uncertain", type: "boolean"),
        new OA\Property(property: "recipient_inferred", type: "boolean"),
        new OA\Property(property: "recipient_note", type: "string", nullable: true),
        new OA\Property(property: "destination_uncertain", type: "boolean"),
        new OA\Property(property: "destination_inferred", type: "boolean"),
        new OA\Property(property: "destination_note", type: "string", nullable: true),
        new OA\Property(property: "origin_uncertain", type: "boolean"),
        new OA\Property(property: "origin_inferred", type: "boolean"),
        new OA\Property(property: "origin_note", type: "string", nullable: true),
        new OA\Property(property: "people_mentioned_note", type: "string", nullable: true),
        new OA\Property(property: "copies", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "related_resources", type: "array", items: new OA\Items(type: "object")),
        new OA\Property(property: "abstract", type: "object", properties: [
            new OA\Property(property: "cs", type: "string"),
            new OA\Property(property: "en", type: "string")
        ]),
        new OA\Property(property: "explicit", type: "string", nullable: true),
        new OA\Property(property: "incipit", type: "string", nullable: true),
        new OA\Property(property: "copyright", type: "string", nullable: true),
        new OA\Property(property: "languages", type: "string", nullable: true),
        new OA\Property(property: "notes_private", type: "string", nullable: true),
        new OA\Property(property: "notes_public", type: "string", nullable: true),
        new OA\Property(property: "status", type: "string", enum: ["publish", "draft"]),
        new OA\Property(property: "content", type: "string", nullable: true),
        new OA\Property(property: "created_at", type: "string", format: "date-time", readOnly: true),
        new OA\Property(property: "updated_at", type: "string", format: "date-time", readOnly: true)
    ]
)]
class Letter extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasFactory, Searchable;

    /**
    * Constructor sets the table name with tenant prefix if tenancy is initialized.
    */
    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->setTable(tenancy()->tenant->table_prefix . '__letters');
        }
    }
    /**
     * Constants for approval status
     */
    const APPROVED = 1;
    const NOT_APPROVED = 0;
    /**
     * Constants for letter status
     */
    const PUBLISHED = "publish";
    const DRAFT = "draft";

    protected $connection = 'tenant';
    public $translatable = ['abstract'];
    protected $guarded = ['id', 'uuid', 'date_computed'];
    protected $table;
    protected $casts = [
        'related_resources' => 'array',
    ];

    public function scopePublished($query)
    {
        return $query->where('status', self::PUBLISHED);
    }

    public function registerMediaCollections(): void
    {
        $this->addMediaCollection('default')
            ->useDisk('public')  // Define the disk for the collection
            ->storeConversionsOnDisk('public');
            //->withResponsiveImages();
    }

    public function registerMediaConversions(?Media $media = null): void
    {
        $this->addMediaConversion('thumb')
            ->performOnCollections('default')
            ->width(180)
            ->keepOriginalImageFormat()
            ->nonQueued();

        // Ensure watermark conversion
        if (Storage::disk('public')->exists('watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                ->watermark(
                    storage_path('app/public/watermark/logo.png'),
                    AlignPosition::Center,
                    0,
                    0,
                    Unit::Pixel,
                    0,
                    Unit::Pixel,
                    0,
                    Unit::Pixel,
                    Fit::Contain,
                    50
                )
                ->performOnCollections('default')
                ->keepOriginalImageFormat()
                ->nonQueued();
        }
    }

    /**
     * Определяет, как будет индексироваться модель для Laravel Scout.
     *
     * @return string
     */
    public function searchableAs(): string
    {
        return 'letter_index';
    }

    /**
     * Model to the array.
     *
     * @return array
     */
    public function toSearchableArray(): array
    {
        return [
            'id' => $this->id,
            'nameNgrams' => (new TNTIndexer)->buildTrigrams($this->content_stripped),
            'content_stripped' => $this->content_stripped,
        ];
    }

    public function identities(): BelongsToMany
    {
        return $this->belongsToMany(
            Identity::class,
            tenancy()->tenant->table_prefix . '__identity_letter'
        )
        ->withPivot('position', 'role', 'marked', 'salutation')
        ->orderBy('pivot_position', 'asc');
    }

    public function localIdentities(): BelongsToMany
    {
        return $this->identities();
    }

    public function globalIdentities(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalIdentity::class,
            tenancy()->tenant->table_prefix . '__identity_letter',
            'letter_id',
            'global_identity_id'
        )
        ->withPivot('position', 'role', 'marked', 'salutation')
        ->orderBy('pivot_position', 'asc');
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(
            Place::class,
            tenancy()->tenant->table_prefix . '__letter_place'
        )
        ->withPivot('position', 'role', 'marked')
        ->orderBy('pivot_position', 'asc');
    }

    public function localPlaces(): BelongsToMany
    {
        return $this->places();
    }

    public function globalPlaces(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalPlace::class,
            tenancy()->tenant->table_prefix . '__letter_place',
            'letter_id',
            'global_place_id'
        )
        ->withPivot('position', 'role', 'marked')
        ->orderBy('pivot_position', 'asc');
    }

    public function origins(): BelongsToMany
    {
        return $this->places()->where('role', '=', 'origin');
    }

    public function globalOrigins(): BelongsToMany
    {
        return $this->globalPlaces()->where('role', '=', 'origin');
    }

    public function destinations(): BelongsToMany
    {
        return $this->places()->where('role', '=', 'destination');
    }

    public function globalDestinations(): BelongsToMany
    {
        return $this->globalPlaces()->where('role', '=', 'destination');
    }

    public function getAllPlacesAttribute()
    {
        $local = $this->localPlaces->map(function ($place) {
            $place->type = 'local';
            return $place;
        });

        $global = $this->globalPlaces->map(function ($place) {
            $place->type = 'global';
            return $place;
        });

        return $local->merge($global);
    }

    public function getAllOriginsAttribute()
    {
        $local = $this->origins->map(function ($place) {
            $place->type = 'local';
            return $place;
        });

        $global = $this->globalOrigins->map(function ($place) {
            $place->type = 'global';
            return $place;
        });

        return $local->merge($global);
    }

    public function getAllDestinationsAttribute()
    {
        $local = $this->destinations->map(function ($place) {
            $place->type = 'local';
            return $place;
        });

        $global = $this->globalDestinations->map(function ($place) {
            $place->type = 'global';
            return $place;
        });

        return $local->merge($global);
    }

    public function keywords(): BelongsToMany
    {
        return $this->belongsToMany(
            Keyword::class,
            tenancy()->tenant->table_prefix . '__keyword_letter'
        )
        ->withPivot('keyword_id', 'letter_id')
        ->orderBy('pivot_keyword_id', 'asc');
    }

    public function localKeywords(): BelongsToMany
    {
        return $this->keywords();
    }

    public function globalKeywords(): BelongsToMany
    {
        return $this->belongsToMany(
            GlobalKeyword::class,
            tenancy()->tenant->table_prefix . '__keyword_letter',
            'letter_id',
            'global_keyword_id'
        );
    }

    public function getAllKeywordsAttribute()
    {
        $local = $this->localKeywords->map(function ($keyword) {
            $keyword->type = 'local';
            return $keyword;
        });

        $global = $this->globalKeywords->map(function ($keyword) {
            $keyword->type = 'global';
            return $keyword;
        });

        return $local->merge($global);
    }

    /**
     * Polymorphic relationship to Media model, restricting model_type = Letter class.
     */
    public function media(): MorphMany
    {
        return $this->morphMany(TenantMedia::class, 'model')->orderBy('order_column');
    }

    public function authors(): BelongsToMany
    {
        return $this->identities()->where('role', '=', 'author');
    }

    public function recipients(): BelongsToMany
    {
        return $this->identities()->where('role', '=', 'recipient');
    }

    public function mentioned(): BelongsToMany
    {
        return $this->identities()->where('role', '=', 'mentioned');
    }

    public function globalAuthors(): BelongsToMany
    {
        return $this->globalIdentities()->where('role', '=', 'author');
    }

    public function globalRecipients(): BelongsToMany
    {
        return $this->globalIdentities()->where('role', '=', 'recipient');
    }

    public function globalMentioned(): BelongsToMany
    {
        return $this->globalIdentities()->where('role', '=', 'mentioned');
    }

    // public function getAuthorsAttribute()
    // {
    //     return $this->authors->concat($this->globalAuthors);
    // }

    // public function getRecipientsAttribute()
    // {
    //     return $this->recipients->concat($this->globalRecipients);
    // }

    // public function getMentionedAttribute()
    // {
    //     return $this->mentioned->concat($this->globalMentioned);
    // }

    public function getAllAuthorsAttribute()
    {
        return $this->authors->concat($this->globalAuthors);
    }

    public function getAllRecipientsAttribute()
    {
        return $this->recipients->concat($this->globalRecipients);
    }

    public function getAllMentionedAttribute()
    {
        return $this->mentioned->concat($this->globalMentioned);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class,
            tenancy()->tenant->table_prefix . '__letter_user'
        )
        ->withPivot('letter_id', 'user_id');
    }

    public function manifestations()
    {
        return $this->hasMany(Manifestation::class);
    }

    // Backward compatibility accessor for 'copies'
    // This allows existing views (index, show) to keep working without major changes yet
    public function getCopiesAttribute()
    {
        return $this->manifestations->map(function ($m) {
            return [
                'id' => $m->id,
                'repository' => $this->formatLocationForForm($m->repository, $m->globalRepository), // Format: ['value' => 'scope-id', 'label' => 'Name (Scope)']
                'archive'    => $this->formatLocationForForm($m->archive, $m->globalArchive),
                'collection' => $this->formatLocationForForm($m->collection, $m->globalCollection),
                'signature' => $m->signature,
                'type' => $m->type,
                'preservation' => $m->preservation,
                'copy' => $m->copy,
                'l_number' => $m->l_number,
                'manifestation_notes' => $m->manifestation_notes,
                'location_note' => $m->location_note,
            ];
        })->toArray();
    }

    public function getPrettyDateAttribute(): string
    {
        return $this->formatDate($this->date_day, $this->date_month, $this->date_year);
    }

    public function getPrettyRangeDateAttribute(): string
    {
        return $this->formatDate($this->range_day, $this->range_month, $this->range_year);
    }

    public function getNameAttribute(): string
    {
        $authors = $this->all_authors;
        $recipients = $this->all_recipients;

        $origins = $this->all_origins;
        $destinations = $this->all_destinations;

        $authorName = $authors->first()?->name ?? '';
        $recipientName = $recipients->first()?->name ?? '';
        $originName = $origins->first()?->name ?? '';
        $destinationName = $destinations->first()?->name ?? '';

        $title = "{$this->pretty_date} ";
        $title .= $authorName;
        $title .= $originName ? "({$originName}) " : '';
        $title .= ($recipientName || $destinationName) ? '→ ' : '';
        $title .= $recipientName;
        $title .= $destinationName ? "({$destinationName}) " : '';

        return $title;
    }

    public function getMediaModel(): string
    {
        return TenantMedia::class;
    }

    protected function formatDate($day, $month, $year): string
    {
        $day = $day && $day != 0 ? $day : '?';
        $month = $month && $month != 0 ? $month : '?';
        $year = $year && $year != 0 ? $year : '????';

        if ($year === '????' && $month === '?' && $day === '?') {
            return '?';
        }

        return "{$day}. {$month}. {$year}";
    }

    protected function formatLocationForForm($local, $global)
    {
        if ($global) {
            return [
                'value' => 'global-' . $global->id,
                'label' => $global->name . ' (' . __('hiko.global') . ')',
            ];
        }

        if ($local) {
            return [
                'value' => 'local-' . $local->id,
                'label' => $local->name . ' (' . __('hiko.local') . ')',
            ];
        }

        return null;
    }

    /**
     * Overrides the default Eloquent builder with our custom LetterBuilder, if needed.
     */
    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }
}
