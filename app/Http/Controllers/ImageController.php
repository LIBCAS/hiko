<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Support\Facades\Log;

class ImageController extends Controller
{
    public function __invoke(Letter $letter, $imageId)
    {
        $image = $letter->getMedia()
            ->where('id', $imageId)
            ->first();

        if (!$image) {
            Log::error('❌ Image Not Found', ['image_id' => $imageId]);
            abort(404);
        }

        if ($image->getCustomProperty('status') !== 'publish') {
            Log::error('❌ Image Not Published', ['image_id' => $imageId]);
            abort(404);
        }

        // Debugging: Log the paths to understand what's being returned
        Log::info('Original Image Path: ' . $image->getPath());
        Log::info('Thumbnail Path: ' . $image->getPath('thumb'));
        Log::info('Watermark Path: ' . $image->getPath('watermark'));

        if (!request()->has('size') || request()->query('size') === 'thumb') {
            Log::info('📸 Serving Thumbnail', ['path' => $image->getPath('thumb')]);
            return response()->file($image->getPath('thumb'));
        }

        Log::info('📸 Serving Full Image', ['path' => $image->getPath()]);

        return config('hiko.show_watermark')
            ? response()->file($image->getPath('watermark'))
            : response()->file($image->getPath());
    }
}
