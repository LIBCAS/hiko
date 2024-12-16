<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DocumentService;
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
        session()->flash('message', __('hiko.text_saved'));
    }

    protected $rules = [
        'photo' => 'required|required|mimes:jpeg,jpg,png,pdf|max:10240',
        'selectedLanguage' => 'required|in:cs,en,de,fr,es',
    ];

    protected $listeners = [
        'clearSelection' => 'clearSelectedText',
    ];

    public function uploadAndProcess(DocumentService $documentService)
    {
        $this->validate();

        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processWithGemini($documentService);
            $this->dispatch('ocr-completed');
            session()->flash('message', __('hiko.ocr_completed'));

        } catch (\Exception $e) {
            Log::error('Document Processing Error: ' . $e->getMessage());
            $this->dispatch('ocr-failed', ['message' => __('hiko.ocr_processing_error')]);
             session()->flash('error', __('hiko.ocr_processing_error'));
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

    private function processWithGemini(DocumentService $documentService)
    {
        $absolutePath = Storage::path($this->tempImagePath);

        try {
            $result = $documentService->processHandwrittenLetter($absolutePath, $this->selectedLanguage);

            Log::info('Gemini Result:', $result);

            $this->ocrText = $result['full_text'] ?? '';
            $this->metadata = $result['metadata'] ?? [];

        } catch (\Exception $e) {
            Log::error('Error processing document with Gemini: ' . $e->getMessage());
            $this->ocrText = '';
            $this->metadata = [];
              session()->flash('error', $e->getMessage());
        }
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
