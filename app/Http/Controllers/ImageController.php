<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Spatie\MediaLibrary\Models\Media;

class ImageController extends Controller
{
    public function __invoke(Letter $letter, $imageId)
    {
        // Get tenant-specific table prefix
        $tenantPrefix = tenancy()->tenant->table_prefix;

        // Query the media from the tenant-specific media table
        $image = Media::from($tenantPrefix . '__media')
            ->where('id', $imageId)
            ->where('model_id', $letter->id)
            ->where('model_type', Letter::class)
            ->first();

        // Abort if image not found or status is not 'publish'
        if (!$image || $image->getCustomProperty('status') !== 'publish') {
            abort(404);
        }

        // Serve the appropriate image (thumbnail by default, full image with watermark if specified)
        if (!request()->has('size') || request()->query('size') === 'thumb') {
            return response()->file($image->getPath('thumb'));
        }

        return config('hiko.show_watermark')
            ? response()->file($image->getPath('watermark'))
            : response()->file($image->getPath());
    }
}
