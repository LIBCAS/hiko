<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Exception;

class OcrUpload extends Component
{
    use WithFileUploads;

    public $photo;
    public $isProcessing = false;
    public $ocrText = '';
    public $metadata = [];

    protected $rules = [
        'photo' => 'required|mimes:jpeg,jpg,png,pdf|max:10240', // Max 10MB
    ];

    /**
     * Upload and process the handwritten letter using Gemini 2.0 Flash.
     *
     * @return void
     */
    public function uploadAndProcess()
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $filePath = $this->saveUploadedFile();
            $result = DocumentService::processHandwrittenLetter($filePath);

            // Assign recognized text
            $this->ocrText = $result['recognized_text'] ?? 'No text found';

            // Assign metadata
            $this->metadata = $result['metadata'] ?? [];

            session()->flash('message', 'OCR processing completed successfully.');
        } catch (Exception $e) {
            Log::error('Gemini 2.0 Flash OCR Processing Error: ' . $e->getMessage());
            session()->flash('error', 'There was an error processing the document.');
        } finally {
            $this->isProcessing = false;
        }
    }

    /**
     * Save the uploaded file to storage.
     *
     * @return string
     * @throws Exception
     */
    private function saveUploadedFile(): string
    {
        $fileName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $filePath = "uploads/ocr/{$fileName}";

        if (!Storage::disk('public')->put($filePath, file_get_contents($this->photo->getRealPath()))) {
            throw new Exception("Failed to save the uploaded file.");
        }

        return Storage::disk('public')->path($filePath);
    }

    /**
     * Render the Livewire component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.ocr-upload', [
            'ocrText'  => $this->ocrText,
            'metadata' => $this->metadata,
        ]);
    }
}
