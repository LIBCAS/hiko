<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Spatie\MediaLibrary\MediaCollections\Models\Media;
use Illuminate\Support\Facades\Schema;

class ImageController extends Controller
{
 public function __invoke(Letter $letter, $imageId)
 {
     // Determine tenant-specific table prefix or fallback to global
     $tenantPrefix = tenancy()->initialized && tenancy()->tenant? tenancy()->tenant->table_prefix : 'global';
     // Build tenant-specific media table name
     $mediaTable = "{$tenantPrefix}__media";
     // Check if the media table exists
     if (!Schema::hasTable($mediaTable)) {
         abort(404, "Media table `{$mediaTable}` does not exist.");
     }
      // Query the media from the tenant-specific media table
     $image = Media::on('tenant')
         ->from($mediaTable)
         ->where('id', $imageId)
         ->where('model_id', $letter->id)
         ->where('model_type', Letter::class)
         ->first();

     // Abort if image not found or status is not 'publish'
      if (!$image || $image->getCustomProperty('status') !== 'publish') {
         abort(404, 'Image not found or inaccessible.');
     }
     // Serve the appropriate image (thumbnail by default, full image with watermark if specified)
    $size = request()->query('size', 'thumb');
    return $this->serveImage($image, $size);
 }

 /**
  * Serve the image based on the requested size.
  *
  * @param Media $image
  * @param string $size
  * @return \Illuminate\Http\Response
  */
 protected function serveImage(Media $image, string $size)
 {
     if ($size === 'thumb') {
         return response()->file($image->getPath('thumb'));
     }

     return config('hiko.show_watermark')
         ? response()->file($image->getPath('watermark'))
         : response()->file($image->getPath());
 }
}
