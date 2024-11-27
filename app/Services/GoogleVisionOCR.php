<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\ImageContext;
use Illuminate\Support\Facades\Log;

class GoogleVisionOCR
{
    protected $languages;

    public function __construct(array $languages = [])
    {
        $this->languages = $languages;
    }

    /**
     * Extract text from an image using Google Vision OCR.
     *
     * @param string $imagePath
     * @return string|null
     */
    public function extractTextFromImage(string $imagePath): ?string
    {
        $imageAnnotator = new ImageAnnotatorClient([
            'keyFilePath' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);

        $image = file_get_contents($imagePath);
        $response = $imageAnnotator->textDetection($image, [
            'languageHints' => $this->languages,
        ]);

        $texts = $response->getTextAnnotations();
        if (count($texts) > 0) {
            return $texts[0]->getDescription();
        }

        return null;
    }
}
