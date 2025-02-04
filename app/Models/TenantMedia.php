<?php

namespace App\Models;

use Spatie\MediaLibrary\MediaCollections\Models\Media as BaseMedia;
use Spatie\MediaLibrary\UrlGenerator\LocalUrlGenerator;
use Stancl\Tenancy\Facades\Tenancy;
use Illuminate\Support\Str;

class TenantMedia extends BaseMedia
{
    protected $table;

    public function __construct(array $attributes = [])
    {
        parent::__construct($attributes);

        if (function_exists('tenancy') && tenancy()->initialized) {
            $this->setTable(tenancy()->tenant->table_prefix . '__media');
        }
    }

    public function newQuery()
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            return parent::newQuery()->from(tenancy()->tenant->table_prefix . '__media');
        }

        return parent::newQuery();
    }

    public static function boot()
    {
        parent::boot();

        static::creating(function ($media) {
            if (function_exists('tenancy') && tenancy()->initialized) {
                $media->setTable(tenancy()->tenant->table_prefix . '__media');
            }

            // ✅ Ensure unique filename
            if (!$media->file_name) {
                $media->file_name = Str::uuid() . '.' . pathinfo($media->name, PATHINFO_EXTENSION);
            }

            // ✅ Remove 'conversions_disk' column to avoid SQL errors
            unset($media->attributes['conversions_disk']);

            // ✅ Move `generated_conversions` from `custom_properties` to its own JSON column
            if (isset($media->custom_properties['generated_conversions'])) {
                $media->generated_conversions = json_encode($media->custom_properties['generated_conversions']);
                unset($media->custom_properties['generated_conversions']);
            }
        });

        static::updating(function ($media) {
            // ✅ Remove 'conversions_disk' to prevent update errors
            unset($media->attributes['conversions_disk']);

            // ✅ Move `generated_conversions` to its correct column on update
            if (isset($media->custom_properties['generated_conversions'])) {
                $media->generated_conversions = json_encode($media->custom_properties['generated_conversions']);
                unset($media->custom_properties['generated_conversions']);
            }
        });
    }

    /**
     * ✅ Fix UUID issues by ensuring correct filename structure.
     */
    public function getUuidAttribute()
    {
        return pathinfo($this->file_name, PATHINFO_FILENAME);
    }

    public function setUuidAttribute($value)
    {
        $this->attributes['file_name'] = $value . '.' . pathinfo($this->name, PATHINFO_EXTENSION);
    }

    public function getHighestOrderNumber(): int
    {
        return static::query()
            ->from(tenancy()->tenant->table_prefix . '__media')
            ->where('model_type', $this->model_type)
            ->where('model_id', $this->model_id)
            ->max('order_column') ?? 0;
    }

    public function getUrl(string $conversionName = ''): string
    {
        if (function_exists('tenancy') && tenancy()->initialized) {
            $tenantPrefix = tenancy()->tenant->table_prefix;
    
            // ✅ Check if a conversion exists and return its URL
            if ($conversionName && isset($this->generated_conversions[$conversionName]) && $this->generated_conversions[$conversionName] === true) {
                return asset("storage/{$tenantPrefix}/{$this->id}/conversions/{$this->uuid}-{$conversionName}.{$this->extension}");
            }
    
            // ✅ Fallback to original image
            return asset("storage/{$tenantPrefix}/{$this->id}/{$this->file_name}");
        }
    
        return parent::getUrl($conversionName);
    }            
}
