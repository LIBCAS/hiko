<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Stancl\Tenancy\Database\Models\Tenant as BaseTenant;
use Stancl\Tenancy\Database\Concerns\HasDatabase;
use Stancl\Tenancy\Database\Concerns\HasDomains;

class Tenant extends BaseTenant
{
    use HasFactory, HasDomains;

    protected $connection = 'mysql';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'name',
        'table_prefix',
        'main_character',
        'metadata_default_locale',
        'version',
        'show_watermark',
        'public_url',
        'data'
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'data' => 'array',
        'show_watermark' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime'
    ];

    public static function getCustomColumns(): array
    {
        return [
            'id',
            'name',
            'table_prefix',
            'main_character',
            'metadata_default_locale',
            'version',
            'show_watermark',
            'public_url',
            'created_at',
            'updated_at',
        ];
    }

    public function displayName(?string $locale = null): string
    {
        $locale = in_array($locale, ['cs', 'en'], true) ? $locale : app()->getLocale();
        $displayNames = $this->applicationDisplayNames();

        if (!empty($displayNames[$locale])) {
            return $displayNames[$locale];
        }

        return trim(trans('hiko.correspondence', [], $locale) . ' ' . $this->name);
    }

    public function applicationDisplayNames(): array
    {
        return [
            'cs' => $this->getAttribute('application_name_cs'),
            'en' => $this->getAttribute('application_name_en'),
        ];
    }

    public function setApplicationDisplayNames(string $cs, string $en): void
    {
        $this->setAttribute('application_name_cs', $cs);
        $this->setAttribute('application_name_en', $en);
    }
}
