<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Google\Cloud\Vision\V1\ImageAnnotatorClient;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;

class ProcessOCR implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The path to the image file.
     *
     * @var string
     */
    protected $imagePath;

    /**
     * The Media model ID.
     *
     * @var int
     */
    protected $mediaId;

    /**
     * Create a new job instance.
     *
     * @param string  $imagePath
     * @param int     $mediaId
     * @return void
     */
    public function __construct($imagePath, $mediaId)
    {
        $this->imagePath = $imagePath;
        $this->mediaId = $mediaId;
    }

    /**
     * Execute the job.
     *
     * @return void
     */
    public function handle()
    {
        try {
            // Initialize the ImageAnnotatorClient
            $imageAnnotator = new ImageAnnotatorClient([
                'credentials' => config('services.google_cloud.key_file'),
            ]);

            $imageContent = file_get_contents($this->imagePath);

            // Perform text detection
            $response = $imageAnnotator->documentTextDetection($imageContent);
            $annotation = $response->getFullTextAnnotation();

            if ($response->hasError()) {
                throw new \Exception($response->getError()->getMessage());
            }

            $extractedText = $annotation ? $annotation->getText() : '';

            // Close the client
            $imageAnnotator->close();

            // Update the Media model with extracted text
            $media = \App\Models\Media::find($this->mediaId);
            if ($media) {
                $media->custom_properties = array_merge($media->custom_properties ?? [], [
                    'extracted_text' => $extractedText,
                ]);
                $media->save();
            }

        } catch (\Exception $e) {
            Log::error('OCR extraction failed in ProcessOCR job: ' . $e->getMessage());
        }
    }
}
