<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\Feature\Type;
use Illuminate\Support\Facades\Log;

class GoogleVisionOCR
{
    protected $imageAnnotator;

    public function __construct()
    {
        $this->imageAnnotator = new ImageAnnotatorClient([
            'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);
    }

    /**
     * Extract text from an image using Google Vision OCR.
     *
     * @param string $imagePath
     * @return string
     */
    public function extractTextFromImage(string $imagePath): string
    {
        try {
            $image = file_get_contents($imagePath);

            $response = $this->imageAnnotator->documentTextDetection($image);

            $fullTextAnnotation = $response->getFullTextAnnotation();

            if ($fullTextAnnotation) {
                return $fullTextAnnotation->getText();
            }

            return '';
        } catch (\Exception $e) {
            Log::error('Google Vision OCR Error: ' . $e->getMessage());
            return '';
        }
    }

    /**
     * Close the ImageAnnotatorClient when done.
     */
    public function __destruct()
    {
        if ($this->imageAnnotator) {
            $this->imageAnnotator->close();
        }
    }
}
