<?php

namespace App\Http\Controllers;

use App\Models\Letter;

class ImageController extends Controller
{
    public function __invoke(Letter $letter, $imageId)
    {
        $image = $letter->getMedia()
            ->where('id', $imageId)
            ->first();

        if (!$image || $image->getCustomProperty('status') !== 'publish') {
            abort(404);
        }

        if (!request()->has('size') || request()->query('size') === 'thumb') {
            return response()->file($image->getPath('thumb'));
        }

        return config('hiko.show_watermark')
            ? response()->file($image->getPath('watermark'))
            : response()->file($image->getPath());
    }
}
