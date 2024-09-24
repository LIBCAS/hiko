<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Laravel\Scout\Searchable;
use App\Builders\LetterBuilder;
use League\Flysystem\FileNotFoundException;
use Spatie\Image\Exceptions\InvalidManipulation;
use Spatie\Image\Manipulations;
use Spatie\MediaLibrary\Models\Media;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;
use Spatie\Translatable\HasTranslations;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;
use Spatie\MediaLibrary\HasMedia\HasMedia;
use Spatie\MediaLibrary\HasMedia\HasMediaTrait;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class Letter extends Model implements HasMedia
{
    use HasTranslations, HasMediaTrait, HasFactory, Searchable;

    protected $connection = 'tenant';  // Ensure the tenant connection is used

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
    
        // Dynamically set the tenant-specific table name
        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = $tenantPrefix . '__letters';  // Tenant-specific table name
        } else {
            // Fallback to a global table if tenancy is not initialized
            //$this->table = 'global_letters';  // Fallback table (if needed)
        }
    }    
    /**
     * @throws FileNotFoundException
     * @throws InvalidManipulation
     */
    public function registerMediaConversions(Media $media = null)
    {
        $this->addMediaConversion('thumb')
            ->width(180);

        if (Storage::disk('local')->exists('public/watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                ->watermark(storage_path('app/public/watermark/logo.png'))
                ->watermarkPosition(Manipulations::POSITION_CENTER);
        }
    }

    public function searchableAs(): string
    {
        return 'letter_index';
    }

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
            tenancy()->tenant->table_prefix . '__identity_letter' // Tenant-specific pivot table
        )
        ->withPivot('position', 'role', 'marked', 'salutation')
        ->orderBy('pivot_position', 'asc');
    }

    public function places(): BelongsToMany
    {
        return $this->belongsToMany(
            Place::class, 
            tenancy()->tenant->table_prefix . '__letter_place' // Tenant-specific pivot table
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
        $tenantPrefix = tenancy()->tenant->table_prefix ?? '';
        $pivotTable = "{$tenantPrefix}__keyword_letter";

        return $this->belongsToMany(
            Keyword::class,
            $pivotTable,
            'letter_id',
            'keyword_id'
        );
    }
    
    public function media(): HasMany
    {
        if (tenancy()->tenant) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
    
            // Dynamically set the tenant-specific media table
            return $this->hasMany(Media::class, 'model_id', 'id')
                        ->where('model_type', Letter::class)
                        ->from($tenantPrefix . '__media'); // Use tenant-specific media table
        }
    
        // Fallback for non-tenant case
        return $this->hasMany(Media::class, 'model_id', 'id')
                    ->where('model_type', Letter::class);
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

        $author = isset($identities['author']) ? $identities['author'][0] : [];
        $recipient = isset($identities['recipient']) ? $identities['recipient'][0] : [];
        $origin = isset($places['origin']) ? $places['origin'][0] : [];
        $destination = isset($places['destination']) ? $places['destination'][0] : [];

        $title = "{$this->pretty_date} ";
        $title .= $author ? $author['name'] . ' ' : '';
        $title .= $origin ? "({$origin['name']}) " : '';
        $title .= $recipient || $destination ? '→ ' : '';
        $title .= $recipient ? $recipient['name'] . ' ' : '';
        $title .= $destination ? "({$destination['name']}) " : '';

        return $title;
    }

    public function newEloquentBuilder($query): LetterBuilder
    {
        return new LetterBuilder($query);
    }

    protected function formatDate($day, $month, $year): string
    {
        $day = $day && $day != 0 ? $day : '?';
        $month = $month && $month != 0 ? $month : '?';
        $year = $year && $year != 0 ? $year : '????';

        if ($year == '????' && $month == '?' && $day == '?') {
            return '?';
        }

        return "{$day}. {$month}. {$year}";
    }

    protected function asJson($value)
    {
        return json_encode($value, JSON_UNESCAPED_UNICODE);
    }
}
