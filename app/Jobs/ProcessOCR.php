<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Storage;
use Intervention\Image\Facades\Image;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;

class ProcessOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $imagePath;
    protected $boundingBox;
    protected $language;
    protected $cacheKey;

    /**
     * Create a new job instance.
     *
     * @return void
     */
    public function __construct($imagePath, $boundingBox, $language = null, $cacheKey = null)
    {
        $this->imagePath = $imagePath;
        $this->boundingBox = $boundingBox;
        $this->language = $language;
        $this->cacheKey = $cacheKey;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        // Crop the image
        try {
            // Generate a unique filename for the cropped image
            $croppedImageName = 'cropped_' . time() . '.png';
            $croppedImagePath = Storage::disk('public')->path('images/' . $croppedImageName);

            Image::make($this->imagePath)
                ->crop(
                    (int) $this->boundingBox['width'],
                    (int) $this->boundingBox['height'],
                    (int) $this->boundingBox['left'],
                    (int) $this->boundingBox['top']
                )
                ->save($croppedImagePath);
        } catch (\Exception $e) {
            Log::error('Image cropping failed in job: ' . $e->getMessage());
            return;
        }

        try {
            // Initialize the ImageAnnotatorClient with credentials
            $imageAnnotator = new ImageAnnotatorClient([
                'credentials' => config('services.google_cloud.key_file'),
            ]);

            $imageContent = file_get_contents($croppedImagePath);

            // Prepare the image for OCR
            $image = (new \Google\Cloud\Vision\V1\Image())
                        ->setContent($imageContent);

            // Set the language hints if provided
            $imageContext = new \Google\Cloud\Vision\V1\ImageContext();
            if (!empty($this->language)) {
                $imageContext->setLanguageHints([$this->language]);
            }

            // Perform text detection
            $response = $imageAnnotator->textDetection($image, ['imageContext' => $imageContext]);
            $texts = $response->getTextAnnotations();

            if ($response->hasError()) {
                throw new \Exception($response->getError()->getMessage());
            }

            $text = isset($texts[0]) ? $texts[0]->getDescription() : '';

            // Close the client
            $imageAnnotator->close();

            // Optionally delete the cropped image after OCR
            unlink($croppedImagePath);

            // Store the OCR result in cache
            if ($this->cacheKey) {
                Cache::put($this->cacheKey, $text, now()->addHours(2)); // Cache for 2 hours
            }

        } catch (\Exception $e) {
            // Clean up in case of an error
            if (file_exists($croppedImagePath)) {
                unlink($croppedImagePath);
            }

            Log::error('OCR Extraction failed in job: ' . $e->getMessage());
        }
    }
}
