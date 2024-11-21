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
    public $selectedLanguage = 'cs'; // Default language
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
     * Save the uploaded file to the `local` storage disk.
     */
    private function saveUploadedFile()
    {
        $this->tempImageName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $this->tempImagePath = "livewire-tmp/{$this->tempImageName}";

        Storage::disk('local')->put($this->tempImagePath, file_get_contents($this->photo->getRealPath()));

        Log::info('File stored at: ' . Storage::disk('local')->path($this->tempImagePath));
    }

    /**
     * Process the uploaded file using Google Vision OCR.
     */
    private function processOCR()
    {
        $ocrService = new GoogleVisionOCR([$this->selectedLanguage]);
        $rawText = $ocrService->extractTextFromImage(Storage::disk('local')->path($this->tempImagePath));

        if (!$rawText) {
            throw new \Exception('No text detected in the uploaded image.');
        }

        $this->ocrText = $this->formatText($rawText);
    }

    /**
     * Format the extracted text for better readability.
     */
    private function formatText(string $text): string
    {
        $formattedText = preg_replace('/\s*\n\s*/', ' ', $text);
        $formattedText = preg_replace('/\s+/', ' ', $formattedText);
        $formattedText = wordwrap($formattedText, 70, "\n");
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
            $this->tempImagePath = null; 
        }
    }

    public function render()
    {
        return view('livewire.ocr-upload');
    }
}
