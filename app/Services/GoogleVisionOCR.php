<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Log;

class GoogleVisionOCR
{
    protected $client;
    protected $languages;

    public function __construct(array $languages = [])
    {
        $this->client = new ImageAnnotatorClient([
            'credentials' => config('services.google_cloud.key_file'),
        ]);

        $this->languages = $languages;
    }

    /**
     * Extract text from an image using Google Vision API.
     *
     * @param string $imagePath
     * @return string
     */
    public function extractTextFromImage(string $imagePath): string
    {
        $imageContent = file_get_contents($imagePath);

        $response = $this->client->documentTextDetection($imageContent, [
            'languageHints' => $this->languages,
        ]);

        $annotation = $response->getFullTextAnnotation();

        if ($annotation) {
            return $annotation->getText();
        } else {
            return '';
        }
    }
}
