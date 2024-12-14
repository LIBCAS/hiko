<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GoogleDocumentAIService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OcrUpload extends Component
{
    use WithFileUploads;

    public $selectedLanguage = 'cs';
    public $photo;
    public $isProcessing = false;
    public $tempImageName;
    public $tempImagePath;
    public $ocrText = '';
    public $metadata = [];
    public $filterMetadata = '';

    public function saveOcrText()
    {
        // Save the OCR text and metadata to the database
        $this->validate(['ocrText' => 'required|string']);
        $letter = new \App\Models\Letter();
        $letter->content = $this->ocrText;
        //$letter->metadata = json_encode($this->metadata);
        $letter->save();

        $this->dispatch('ocr-saved');
        Log::info("OCR Text and metadata saved: {$letter->id}");
    }

    protected $rules = [
        'photo' => 'required|required|mimes:jpeg,jpg,png,pdf|max:10240',
        'selectedLanguage' => 'required|in:cs,en,de,fr,es',
    ];

    protected $listeners = [
        'clearSelection' => 'clearSelectedText',
    ];

    public function uploadAndProcess(GoogleDocumentAIService $documentAIService)
    {
        $this->validate();

        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processDocumentAI($documentAIService);
            $this->dispatch('ocr-completed'); // Corrected for Livewire's `dispatch`
        } catch (\Exception $e) {
            Log::error('Document Processing Error: ' . $e->getMessage());
            $this->dispatch('ocr-failed', ['message' => __('hiko.ocr_processing_error')]); // Corrected for Livewire's `dispatch`
        } finally {
            $this->isProcessing = false;
            $this->deleteTemporaryFile();
        }
    }

    private function saveUploadedFile()
    {
        $this->tempImageName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $this->tempImagePath = "livewire-tmp/{$this->tempImageName}";

        Storage::put($this->tempImagePath, file_get_contents($this->photo->getRealPath()));

        Log::info('File stored at: ' . Storage::path($this->tempImagePath));
    }

    private function processDocumentAI(GoogleDocumentAIService $documentAIService)
    {
        $absolutePath = Storage::path($this->tempImagePath);
    
        try {
            $result = $documentAIService->processDocument($absolutePath, $this->selectedLanguage);
    
            Log::info('Document AI Result:', $result);
    
            $this->ocrText = $result['text'] ?? '';
            $this->metadata = $this->mapMetadata($result['entities']);
        } catch (\Exception $e) {
            Log::error('Error processing document AI: ' . $e->getMessage());
            $this->ocrText = '';
            $this->metadata = [];
        }
    }
    
    private function mapMetadata(array $entities): array
    {
        $metadata = [];
    
        foreach ($entities as $entity) {
            switch ($entity['type']) {
                case 'PERSON':
                    $metadata['author'] = $entity['name'] ?? '';
                    break;
                case 'DATE':
                    $metadata['date_marked'] = $entity['mentionText'] ?? '';
                    break;
                case 'LOCATION':
                    if (empty($metadata['origin'])) {
                        $metadata['origin'] = $entity['name'] ?? '';
                    } else {
                        $metadata['destination'] = $entity['name'] ?? '';
                    }
                    break;
                case 'KEYWORD':
                    $metadata['keywords'][] = $entity['name'] ?? '';
                    break;
                default:
                    Log::info("Unhandled metadata entity type: {$entity['type']}");
                    break;
            }
        }
    
        return $metadata;
    }    

    public function clearSelectedText()
    {
        $this->ocrText = '';
    }

    private function deleteTemporaryFile()
    {
        if ($this->tempImagePath && Storage::exists($this->tempImagePath)) {
            Storage::delete($this->tempImagePath);
            Log::info("Temporary file deleted: {$this->tempImagePath}");
        }
    }

    public function render()
    {
        return view('livewire.ocr-upload');
    }
}
