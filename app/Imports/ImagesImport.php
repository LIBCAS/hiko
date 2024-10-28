<?php

namespace App\Imports;

use App\Models\Letter;
use Illuminate\Contracts\Filesystem\FileNotFoundException;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Storage;

class ImagesImport
{
    /**
     * @throws FileNotFoundException
     */
    public function import(): string
    {
        if (!Storage::disk('local')->exists('imports/letter.json')) {
            return 'Soubor neexistuje';
        }

        collect(json_decode(Storage::disk('local')->get('imports/letter.json')))
            ->reject(function ($letter) {
                return empty($letter->media);
            })
            ->each(function ($letter) {
                $media = $letter->media;
                $letter = Letter::where('id', '=', $letter->id)->first();
                collect($media)
                    ->sortBy('order')
                    ->each(function ($image) use ($letter) {
                        $image->name = Str::uuid() . '.' . pathinfo($image->src)['extension'];

                        if ($this->downloadFile($image->src, $image->name)) {
                            $letter->addMedia(storage_path('app/temp-import/' . $image->name))
                                ->withCustomProperties(['status' => $image->status === 'private' ? 'private' : 'publish'])
                                ->toMediaCollection();

                            $this->removeFile($image->name);
                        }
                    });
            });


        return 'Import obrázků byl úspěšný';
    }

    protected function downloadFile($url, $name): bool
    {
        $fileInfo = pathinfo($url);
        $url = $fileInfo['dirname'] . '/' . rawurlencode($fileInfo['filename'] . '.' . $fileInfo['extension']);

        try {
            Storage::disk('local')->put('temp-import/' . $name, file_get_contents(str_replace('https', 'http', $url)));
            return true;
        } catch (\Exception $e) {
            dump($e->getMessage());
            return false;
        }
    }

    protected function removeFile($name)
    {
        Storage::disk('local')->delete('temp-import/' . $name);
    }
}
