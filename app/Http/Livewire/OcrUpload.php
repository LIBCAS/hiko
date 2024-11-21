<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GoogleVisionOCR;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OcrUpload extends Component
{
    use WithFileUploads;

    public $photo;
    public $ocrText = '';
    public $selectedText = '';
    public $isProcessing = false;
    public $tempImageName;
    public $tempImagePath;

    protected $rules = [
        'photo' => 'required|image|max:10240', // 10MB Max
    ];

    protected $listeners = [
        'clearSelection' => 'clearSelectedText',
    ];

    /**
     * Handle the photo upload and OCR processing.
     */
    public function uploadAndProcess()
    {
        $this->validate();

        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processOCR();

            $this->dispatchBrowserEvent('ocr-completed');
        } catch (\Exception $e) {
            Log::error('OCR Processing Error: ' . $e->getMessage());
            $this->dispatchBrowserEvent('ocr-failed', ['message' => 'An error occurred during OCR processing.']);
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Save the uploaded file to a tenant-specific storage location.
     *
     * @throws \Exception
     */
    private function saveUploadedFile()
    {
        try {
            $this->tempImageName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
            $this->tempImagePath = "livewire-tmp/{$this->tempImageName}";

            // Save the uploaded image in the specified directory
            Storage::disk('local')->put($this->tempImagePath, file_get_contents($this->photo->getRealPath()));

            Log::info('File stored at: ' . Storage::disk('local')->path($this->tempImagePath));
        } catch (\Exception $e) {
            throw new \Exception('Failed to store the uploaded file.');
        }
    }

    /**
     * Process the uploaded file using Google Vision OCR.
     *
     * @throws \Exception
     */
    private function processOCR()
    {
        $ocrService = new GoogleVisionOCR();
        $rawText = $ocrService->extractTextFromImage(Storage::disk('local')->path($this->tempImagePath));

        if (!$rawText) {
            throw new \Exception('No text detected in the uploaded image.');
        }

        // Format the extracted text
        $this->ocrText = $this->formatText($rawText);
    }

    /**
     * Format the extracted text for improved readability.
     *
     * @param string $text
     * @return string
     */
    private function formatText(string $text): string
    {
        // Remove unnecessary line breaks and spaces
        $formattedText = preg_replace('/\s*\n\s*/', ' ', $text); // Remove unnecessary newlines
        $formattedText = preg_replace('/\s+/', ' ', $formattedText); // Remove redundant spaces
        $formattedText = wordwrap($formattedText, 70, "\n"); // Reintroduce logical line breaks

        return trim($formattedText);
    }

    /**
     * Clear the selected text.
     */
    public function clearSelectedText()
    {
        $this->selectedText = '';
    }

    /**
     * Allow the user to manually delete the temporary file.
     */
    public function deleteTemporaryFile()
    {
        if ($this->tempImagePath && Storage::disk('local')->exists($this->tempImagePath)) {
            Storage::disk('local')->delete($this->tempImagePath);
            Log::info("Temporary file deleted: {$this->tempImagePath}");
            $this->tempImagePath = null; // Clear the path after deletion
        }
    }

    /**
     * Automatically clean up temporary files on destruction.
     */
    public function __destruct()
    {
        $this->cleanupTemporaryFile();
    }

    /**
     * Clean up the temporary file.
     */
    protected function cleanupTemporaryFile()
    {
        if ($this->tempImagePath && Storage::disk('local')->exists($this->tempImagePath)) {
            Storage::disk('local')->delete($this->tempImagePath);
            Log::info("Temporary file deleted: {$this->tempImagePath}");
        }
    }

    public function render()
    {
        return view('livewire.ocr-upload');
    }
}
