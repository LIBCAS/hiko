<?php
// app/Http/Livewire/OcrUpload.php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithFileUploads;
use App\Services\GoogleVisionOCR;
use App\Services\GoogleNaturalLanguageService;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class OcrUpload extends Component
{
    use WithFileUploads;

    public $photo;
    public $ocrText = '';
    public $selectedText = '';
    public $selectedLanguage = 'cs';
    public $isProcessing = false;
    public $tempImageName;
    public $tempImagePath;

    public $metadata = [
        'date_year' => '',
        'date_month' => '',
        'date_day' => '',
        'date_marked' => '',
        'date_uncertain' => false,
        'date_approximate' => false,
        'date_inferred' => false,
        'date_is_range' => false,
        'range_year' => '',
        'range_month' => '',
        'range_day' => '',
        'date_note' => '',

        'author' => '',
        'author_inferred' => false,
        'author_uncertain' => false,
        'author_note' => '',

        'recipient' => '',
        'recipient_inferred' => false,
        'recipient_uncertain' => false,
        'recipient_note' => '',

        'origin' => '',
        'origin_inferred' => false,
        'origin_uncertain' => false,
        'origin_note' => '',

        'destination' => '',
        'destination_inferred' => false,
        'destination_uncertain' => false,
        'destination_note' => '',

        'languages' => [],
        'keywords' => [],
        'abstract_cs' => '',
        'abstract_en' => '',
        'incipit' => '',
        'explicit' => '',
        'mentioned' => [],
        'people_mentioned_note' => '',
        'notes_private' => '',
        'notes_public' => '',

        'related_resources' => [],

        'copies' => [],

        'copyright' => '',

        'status' => '',
    ];

    protected $rules = [
        'photo' => 'required|image|max:10240',
    ];

    protected $listeners = [
        'clearSelection' => 'clearSelectedText',
    ];

    public function uploadAndProcess()
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processOCR();
            $this->performNLPAnalysis();
            $this->dispatchBrowserEvent('ocr-completed');
        } catch (\Exception $e) {
            Log::error('OCR Processing Error: ' . $e->getMessage());
            $this->dispatchBrowserEvent('ocr-failed', ['message' => __('hiko.ocr_processing_error')]);
        } finally {
            $this->isProcessing = false;
        }
    }

    private function saveUploadedFile()
    {
        $this->tempImageName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $this->tempImagePath = "livewire-tmp/{$this->tempImageName}";

        Storage::disk('local')->put($this->tempImagePath, file_get_contents($this->photo->getRealPath()));

        Log::info('File stored at: ' . Storage::disk('local')->path($this->tempImagePath));
    }

    private function processOCR()
    {
        $ocrService = new GoogleVisionOCR([$this->selectedLanguage]);
        $rawText = $ocrService->extractTextFromImage(Storage::disk('local')->path($this->tempImagePath));

        if (!$rawText) {
            throw new \Exception(__('hiko.no_text_detected'));
        }

        $this->ocrText = $this->formatText($rawText);
    }

    private function performNLPAnalysis()
    {
        $nlpService = new GoogleNaturalLanguageService();
        $entities = $nlpService->analyzeEntities($this->ocrText);
        $syntax = $nlpService->analyzeSyntax($this->ocrText);

        Log::info('NLP Syntax Analysis:', $syntax);

        $this->resetMetadata();

        foreach ($entities as $entity) {
            switch ($entity['type']) {
                case 'PERSON':
                    if (empty($this->metadata['author'])) {
                        $this->metadata['author'] = $entity['name'];
                    } elseif (empty($this->metadata['recipient']) && $entity['name'] !== $this->metadata['author']) {
                        $this->metadata['recipient'] = $entity['name'];
                    }
                    break;

                case 'DATE':
                    if (empty($this->metadata['date_year'])) {
                        $this->metadata['date_year'] = $entity['name'];
                    }
                    break;

                case 'LOCATION':
                    if (empty($this->metadata['origin'])) {
                        $this->metadata['origin'] = $entity['name'];
                    } elseif (empty($this->metadata['destination']) && $entity['name'] !== $this->metadata['origin']) {
                        $this->metadata['destination'] = $entity['name'];
                    }
                    break;

                case 'ORGANIZATION':
                    $this->metadata['related_resources'][] = $entity['name'];
                    break;

                case 'EVENT':
                    $this->metadata['status'] = $entity['name'];
                    break;
            }
        }

        $keywords = $this->extractKeywords($syntax);
        $this->metadata['keywords'] = $keywords;

        $this->metadata['languages'][] = $this->selectedLanguage;

        $this->metadata['abstract_cs'] = $this->extractAbstract('cs');
        $this->metadata['abstract_en'] = $this->extractAbstract('en');
        $this->metadata['incipit'] = $this->extractIncipit();
        $this->metadata['explicit'] = $this->extractExplicit();

        $mentioned = $this->extractMentionedEntities($entities);
        $this->metadata['mentioned'] = $mentioned;
    }

    private function resetMetadata()
    {
        $this->metadata = [
            'date_year' => '',
            'date_month' => '',
            'date_day' => '',
            'date_marked' => '',
            'date_uncertain' => false,
            'date_approximate' => false,
            'date_inferred' => false,
            'date_is_range' => false,
            'range_year' => '',
            'range_month' => '',
            'range_day' => '',
            'date_note' => '',

            'author' => '',
            'author_inferred' => false,
            'author_uncertain' => false,
            'author_note' => '',

            'recipient' => '',
            'recipient_inferred' => false,
            'recipient_uncertain' => false,
            'recipient_note' => '',

            'origin' => '',
            'origin_inferred' => false,
            'origin_uncertain' => false,
            'origin_note' => '',

            'destination' => '',
            'destination_inferred' => false,
            'destination_uncertain' => false,
            'destination_note' => '',

            'languages' => [],
            'keywords' => [],
            'abstract_cs' => '',
            'abstract_en' => '',
            'incipit' => '',
            'explicit' => '',
            'mentioned' => [],
            'people_mentioned_note' => '',
            'notes_private' => '',
            'notes_public' => '',

            'related_resources' => [],

            'copies' => [],

            'copyright' => '',

            'status' => '',
        ];
    }

    private function extractKeywords(array $syntax): array
    {
        $keywords = [];
        if (isset($syntax['tokens']) && is_array($syntax['tokens'])) {
            foreach ($syntax['tokens'] as $token) {
                if (isset($token['partOfSpeech']['tag']) && in_array($token['partOfSpeech']['tag'], ['NOUN', 'PROPN'])) {
                    $keywords[] = $token['text']['content'];
                }
            }
        }
        return array_unique($keywords);
    }

    private function extractAbstract(string $language): string
    {
        return '';
    }

    private function extractIncipit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return isset($sentences[0]) ? trim($sentences[0]) . '.' : '';
    }

    private function extractExplicit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return count($sentences) > 1 ? trim($sentences[count($sentences) - 2]) . '.' : '';
    }

    private function extractMentionedEntities(array $entities): array
    {
        $mentioned = [];
        foreach ($entities as $entity) {
            if (in_array($entity['type'], ['PERSON', 'ORGANIZATION', 'LOCATION'])) {
                $mentioned[] = $entity['name'];
            }
        }
        return array_unique($mentioned);
    }

    private function formatText(string $text): string
    {
        $formattedText = preg_replace('/\s*\n\s*/', ' ', $text);
        $formattedText = preg_replace('/\s+/', ' ', $formattedText);
        $formattedText = wordwrap($formattedText, 70, "\n");
        return trim($formattedText);
    }

    public function clearSelectedText()
    {
        $this->selectedText = '';
    }

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
