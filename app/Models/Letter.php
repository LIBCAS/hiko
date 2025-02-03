<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Laravel\Scout\Searchable;
use App\Builders\LetterBuilder;
use League\Flysystem\FileNotFoundException;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Exceptions\InvalidManipulation;
use Illuminate\Support\Facades\Storage;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use Spatie\MediaLibrary\HasMedia;
use Spatie\MediaLibrary\InteractsWithMedia;
use Spatie\Translatable\HasTranslations;
use Spatie\Image\Manipulations;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use App\Models\TenantMedia;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

    protected $connection = 'tenant';
    public $translatable = ['abstract'];
    protected $guarded = ['id', 'uuid'];
    protected $table;
    protected $casts = [
        'copies' => 'array',
        'related_resources' => 'array',
    ];

    /**
     * Spatie Media Library conversions registration.
     * Allows defining image manipulations (like thumbnails/watermarks).
     */
    public function registerMediaConversions(Media $media = null) : void
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

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(
            Place::class, 
            tenancy()->tenant->table_prefix . '__letter_place'
        )
        ->withPivot('position', 'role', 'marked')
        ->orderBy('pivot_position', 'asc');
    }

    public function origins(): BelongsToMany
    {
        return $this->places()->where('role', '=', 'origin');
    }

    public function destinations(): BelongsToMany
    {
        return $this->places()->where('role', '=', 'destination');
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

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(
            User::class, 
            tenancy()->tenant->table_prefix . '__letter_user'
        )
        ->withPivot('letter_id', 'user_id');
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
        $identities = $this->identities->groupBy('pivot.role')->toArray();
        $places = $this->places->groupBy('pivot.role')->toArray();

        $author = $identities['author'][0] ?? [];
        $recipient = $identities['recipient'][0] ?? [];
        $origin = $places['origin'][0] ?? [];
        $destination = $places['destination'][0] ?? [];

        $title = "{$this->pretty_date} ";
        $title .= $author['name'] ?? '';
        $title .= $origin ? "({$origin['name']}) " : '';
        $title .= ($recipient || $destination) ? '→ ' : '';
        $title .= $recipient['name'] ?? '';
        $title .= $destination ? "({$destination['name']}) " : '';

        return $title;
    }

    /**
     * Определяет, какую модель медиа использовать.
     * Если аренда активна, используется TenantMedia.
     *
     * @return string
     */
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

    /**
     * Overrides the default Eloquent builder with our custom LetterBuilder, if needed.
     */
    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }
}
