<?php

namespace App\Services;

use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Google\Cloud\Vision\V1\ImageContext;
use Illuminate\Support\Facades\Log;

class GoogleVisionOCR
{
    protected $imageAnnotator;
    protected $languageHints;

    public function __construct(array $languageHints = [])
    {
        $this->imageAnnotator = new ImageAnnotatorClient([
            'credentials' => env('GOOGLE_APPLICATION_CREDENTIALS'),
        ]);
        $this->languageHints = $languageHints;
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

            // Set language hints in ImageContext
            $imageContext = new ImageContext([
                'language_hints' => $this->languageHints,
            ]);

            $response = $this->imageAnnotator->documentTextDetection($image, [
                'imageContext' => $imageContext,
            ]);

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
