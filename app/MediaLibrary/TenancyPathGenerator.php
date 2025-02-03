<?php

namespace App\MediaLibrary;

use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Spatie\MediaLibrary\PathGenerator\PathGenerator;
use Stancl\Tenancy\Facades\Tenancy;

class TenancyPathGenerator implements PathGenerator
{
    public function getPath(Media $media): string
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix : 'global';
        return "{$tenantPrefix}/{$media->id}/";
    }

    public function getPathForConversions(Media $media): string
    {
        return $this->getPath($media) . 'conversions/';
    }

    public function getPathForResponsiveImages(Media $media): string
    {
        return $this->getPath($media) . 'responsive-images/';
    }
}
