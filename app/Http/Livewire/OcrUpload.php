<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\DocumentService;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class OcrUpload extends Component
{
    use WithFileUploads;

    public $photo;
    public $isProcessing = false;
    public $ocrText = '';
    public $metadata = [];

    protected $rules = [
        'photo' => 'required|mimes:jpeg,jpg,png,pdf|max:10240',
    ];

    public function uploadAndProcess()
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $filePath = $this->saveUploadedFile();
            $result = DocumentService::processHandwrittenLetter($filePath);

            // Use incipit or explicit if full_text is not available
            $this->ocrText = $result['incipit'] ?? $result['explicit'] ?? __('hiko.no_text_found');
            $this->metadata = $result;

            session()->flash('message', __('hiko.ocr_completed'));
        } catch (\Exception $e) {
            Log::error('OCR Processing Error: ' . $e->getMessage());
            session()->flash('error', __('hiko.ocr_processing_error'));
        } finally {
            $this->isProcessing = false;
        }
    }

    private function saveUploadedFile(): string
    {
        $fileName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $filePath = "uploads/ocr/{$fileName}";

        Storage::put($filePath, file_get_contents($this->photo->getRealPath()));

        return Storage::path($filePath);
    }

    public function render()
    {
        return view('livewire.ocr-upload', [
            'ocrText' => $this->ocrText,
            'metadata' => $this->metadata,
        ]);
    }
}
