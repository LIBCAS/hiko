<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Config;
use Exception;

class OcrUpload extends Component
{
    use WithFileUploads;

    /**
     * Uploaded photo or document.
     *
     * @var \Livewire\Features\SupportFileUploads\TemporaryUploadedFile|null
     */
    public $photo;

    /**
     * Indicates if the upload is being processed.
     *
     * @var bool
     */
    public $isProcessing = false;

    /**
     * Recognized text from the OCR process.
     *
     * @var string
     */
    public $ocrText = '';

    /**
     * Extracted metadata from the OCR process.
     *
     * @var array
     */
    public $metadata = [];

    /**
     * Validation rules for the component.
     *
     * @var array
     */
    protected $rules = [
        'photo' => 'required|file|mimes:jpeg,jpg,png,pdf|max:10240', // Max 10MB
    ];

    /**
     * Custom validation messages.
     *
     * @var array
     */
    protected $messages = [
        'photo.required' => 'Please upload a document.',
        'photo.file' => 'The uploaded file must be a valid file.',
        'photo.mimes' => 'Only JPEG, PNG, and PDF files are allowed.',
        'photo.max' => 'The uploaded file must not exceed 10MB.',
    ];

    /**
     * Upload and process the handwritten letter using Gemini 2.0 Flash.
     *
     * @param DocumentService $documentService
     * @return void
     */
    public function uploadAndProcess(DocumentService $documentService)
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            // Save the uploaded file to temporary storage
            $filePath = $this->saveUploadedFile();

            // Process the handwritten letter
            $result = $documentService->processHandwrittenLetter($filePath);

            // Assign recognized text
            $this->ocrText = $result['recognized_text'] ?? 'No text found';

            // Assign metadata
            $this->metadata = $result['metadata'] ?? [];

            // Flash success message
            session()->flash('message', 'OCR processing completed successfully.');

            // Reset only the photo after processing
            $this->reset(['photo']);
            $this->resetValidation();

        } catch (Exception $e) {
            // Log the error with detailed information
            Log::error('Gemini 2.0 Flash OCR Processing Error: ' . $e->getMessage(), [
                'exception' => $e
            ]);

            // Flash error message to the user
            session()->flash('error', 'There was an error processing the document: ' . $e->getMessage());
        } finally {
            // Ensure that processing state is reset
            $this->isProcessing = false;

            // Clean up the uploaded file from temporary storage
            $this->cleanupUploadedFile($filePath ?? null);
        }
    }

    /**
     * Save the uploaded file to temporary storage.
     *
     * @return string
     * @throws Exception
     */
    private function saveUploadedFile(): string
    {
        $file = $this->photo;
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath = "temp/ocr/{$fileName}";

        // Store the file in the local disk's temp directory
        if (!Storage::disk('local')->putFileAs('temp/ocr', $file, $fileName)) {
            throw new Exception("Failed to save the uploaded file.");
        }

        return Storage::disk('local')->path("temp/ocr/{$fileName}");
    }

    /**
     * Clean up the uploaded file from temporary storage.
     *
     * @param string|null $filePath
     * @return void
     */
    private function cleanupUploadedFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('local')->exists($filePath)) {
            Storage::disk('local')->delete($filePath);
        }
    }

    /**
     * Reset the form to its initial state.
     *
     * @return void
     */
    public function resetForm()
    {
        $this->reset(['photo', 'ocrText', 'metadata']);
        $this->resetValidation();
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
