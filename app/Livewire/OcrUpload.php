<?php

namespace App\Livewire;

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
        'photo' => 'required|file|mimes:jpeg,jpg,png,pdf|max:20480', // Max 20MB
    ];

    /**
     * Custom validation messages using translation keys.
     *
     * @var array
     */
    protected $messages = [];

    /**
     * Life cycle method to initialize component properties.
     *
     * @return void
     */
    public function mount()
    {
        $this->messages = [
            'photo.required' => __('hiko.photo_required'),
            'photo.file'   => __('hiko.photo_file'),
            'photo.mimes'  => __('hiko.photo_mimes'),
            'photo.max'    => __('hiko.photo_max'),
        ];
    }

    /**
     * Upload and process the document using Gemini 2.0 Flash.
     *
     * @param DocumentService $documentService
     * @return void
     */
    public function uploadAndProcess(DocumentService $documentService)
    {
        $this->validate();
        $this->isProcessing = true;
        $filePath = null;

        try {
            // 1. Save the uploaded file to temporary storage
            $filePath = $this->saveUploadedFile();

            // 2. Process the document using the new processDocument method
            $result = $documentService->processDocument($filePath);

            // 3. Assign recognized text
            $this->ocrText = $result['recognized_text'] ?? __('hiko.no_text_found');

            // 4. Assign metadata
            $this->metadata = $result['metadata'] ?? [];

            // 5. Flash success message
            session()->flash('message', __('hiko.ocr_processing_completed_successfully'));

            // 6. Reset only the photo after processing
            $this->reset(['photo']);
            $this->resetValidation();
        } catch (Exception $e) {
            // Log the error with detailed information
            Log::error('Gemini 2.0 Flash OCR Processing Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Flash error message to the user
            session()->flash('error', __('hiko.error_processing_document') . ' ' . $e->getMessage());
        } finally {
            // Ensure that processing state is reset
            $this->isProcessing = false;

            // Clean up the uploaded file from temporary storage
            $this->cleanupUploadedFile($filePath ?? null);

            DocumentService::cleanupTempFiles();
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
            throw new Exception(__('hiko.failed_to_save_the_uploaded_file'));
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
            'ocrText' => $this->ocrText,
            'metadata' => $this->metadata,
        ]);
    }
}
