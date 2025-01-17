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
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\Image\Manipulations;
use Illuminate\Support\Facades\Storage;
use League\Flysystem\FileNotFoundException;
use TeamTNT\TNTSearch\Indexer\TNTIndexer;

class Letter extends Model implements HasMedia
{
    use HasTranslations, InteractsWithMedia, HasFactory, Searchable;

    // Constants for approval
    const APPROVED = 1;
    const NOT_APPROVED = 0;

    /**
     * The database connection name.
     *
     * @var string
     */
    protected $connection = 'tenant';

    /**
     * The translatable attributes.
     *
     * @var array
     */
    public $translatable = ['abstract'];

    /**
     * The guarded attributes.
     *
     * @var array
     */
    protected $guarded = ['id', 'uuid'];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'approval' => 'integer',
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

        // Gracefully handle tenancy initialization
        if (function_exists('tenancy') && tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
            $this->table = "{$tenantPrefix}__letters";
        } else {
            // Prevent errors if tenancy is not initialized
            $this->table = 'default_letters'; // Or throw an exception if required
        }
    } 

    public function registerMediaConversions(?\Spatie\MediaLibrary\MediaCollections\Models\Media $media = null): void
    {
        $this->addMediaConversion('thumb')
             ->width(180);
    
        if (Storage::disk('local')->exists('public/watermark/logo.png')) {
            $this->addMediaConversion('watermark')
                 ->watermark(storage_path('app/public/watermark/logo.png'))
                 ->watermarkPosition(Manipulations::POSITION_CENTER);
        }
    }    

    public function identities(): BelongsToMany
    {
        $pivotTable = tenancy()->initialized
            ? tenancy()->tenant->table_prefix . '__identity_letter'
            : 'default_identity_letter';

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

    public function keywords(): \Illuminate\Database\Eloquent\Relations\BelongsToMany
    {
        $pivotTable = tenancy()->initialized 
            ? tenancy()->tenant->table_prefix . '__keyword_letter' 
            : 'global_keyword_letter';
    
        return $this->belongsToMany(
            GlobalKeyword::class,
            $pivotTable,
            'letter_id',    // Foreign key on the pivot table referencing the letters table
            'keyword_id',   // Foreign key on the pivot table referencing the keywords table
            'id',           // Local key on the letters table
            'id'            // Local key on the keywords table
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
