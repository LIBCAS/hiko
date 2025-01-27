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
     * Uploaded photos or documents.
     *
     * @var array
     */
    public $photos = [];

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
     * Stored file paths to pass to the main form.
     *
     * @var array
     */
    public $uploadedFiles = [];

    /**
     * Validation rules for the component.
     *
     * @var array
     */
    protected $rules = [
        'photos.*' => 'file|mimes:jpeg,jpg,png,doc,docx,pdf|max:20480', // Max 20MB per file
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
            'photos.*.file' => __('hiko.photo_file'),
            'photos.*.mimes' => __('hiko.photo_mimes'),
            'photos.*.max' => __('hiko.photo_max'),
        ];
    }

      /**
     * Upload and process the documents using Gemini 2.0 API.
     *
     * @param DocumentService $documentService
     * @return void
     */
    public function uploadAndProcess(DocumentService $documentService)
    {
        $this->validate();

        if (count($this->photos) === 0) {
            $this->addError('photos', __('hiko.no_files_selected'));
            return;
        }

        if (count($this->photos) > 100) {
            $this->addError('photos', __('hiko.max_files_exceeded'));
            return;
        }

        $this->isProcessing = true;
        $filePaths = [];
        $aggregatedOcrText = '';
        $aggregatedMetadata = [];
         $this->uploadedFiles = []; // Reset the uploaded files array

        try {
             foreach ($this->photos as $photo) {
                // Save the uploaded file to public storage
                $filePath = $this->saveUploadedFile($photo);
                $filePaths[] = $filePath;
                $this->uploadedFiles[] = $filePath;

                // Process the document using the DocumentService
                $result = $documentService->processDocument($filePath);

                // Aggregate recognized text
                $aggregatedOcrText .= ($result['recognized_text'] ?? '') . "\n";

                // Aggregate metadata
                 if (isset($result['metadata']) && is_array($result['metadata'])) {
                    foreach ($result['metadata'] as $key => $value) {
                        if (!isset($aggregatedMetadata[$key])) {
                            $aggregatedMetadata[$key] = $value;
                        } else {
                            // Handle aggregation based on value type
                            if (is_array($aggregatedMetadata[$key]) && is_array($value)) {
                                $aggregatedMetadata[$key] = array_merge($aggregatedMetadata[$key], $value);
                            } else {
                                $aggregatedMetadata[$key] = $value;
                            }
                        }
                    }
                }
            }

           // Assign aggregated recognized text and metadata
            $this->ocrText = trim($aggregatedOcrText);
            $this->metadata = $aggregatedMetadata;

            // Flash success message
            session()->flash('message', __('hiko.ocr_processing_completed_successfully'));

            // Reset the form after processing
             $this->reset(['photos']);
            $this->resetValidation();


        } catch (Exception $e) {
            // Log the error with detailed information
            Log::error('Gemini 2.0 API OCR Processing Error: ' . $e->getMessage(), [
                'exception' => $e,
            ]);

            // Flash error message to the user
            session()->flash('error', __('hiko.error_processing_documents') . ' ' . $e->getMessage());
        } finally {
           // Ensure that processing state is reset
            $this->isProcessing = false;

             // Clean up the uploaded files from temporary storage
             foreach ($filePaths as $path) {
                 $this->cleanupUploadedFile($path);
             }

             DocumentService::cleanupTempFiles();
        }
    }

   /**
     * Save the uploaded file to temporary storage.
     *
     * @param \Illuminate\Http\UploadedFile $file
     * @return string
     * @throws Exception
     */
    private function saveUploadedFile($file): string
    {
        $fileName = Str::uuid() . '.' . $file->getClientOriginalExtension();
        $filePath =  "media/{$fileName}";


         // Store the file in the local disk's temp directory
         if (!Storage::disk('public')->putFileAs('media', $file, $fileName)) {
             throw new Exception(__('hiko.failed_to_save_the_uploaded_file'));
         }

          return Storage::disk('public')->path("media/{$fileName}");

    }

    /**
     * Clean up the uploaded file from temporary storage.
     *
     * @param string|null $filePath
     * @return void
     */
     private function cleanupUploadedFile(?string $filePath): void
    {
        if ($filePath && Storage::disk('public')->exists($filePath)) {
            Storage::disk('public')->delete($filePath);
        }
    }


    /**
     * Reset the form to its initial state.
     *
     * @return void
     */
    public function resetForm()
    {
        $this->reset(['photos', 'ocrText', 'metadata', 'uploadedFiles']);
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
