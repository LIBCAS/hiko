<?php

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
    public $selectedLanguage = 'cs';
    public $isProcessing = false;
    public $tempImageName;
    public $tempImagePath;

    public $metadata = [
        'year' => '',
        'month' => '',
        'day' => '',
        'date_marked' => '',
        'author' => '',
        'recipient' => '',
        'origin' => '',
        'destination' => '',
        'keywords' => [],
        'languages' => [],
        'abstract_cs' => '',
        'abstract_en' => '',
        'incipit' => '',
        'explicit' => '',
        'mentioned' => [],
        'status' => '',
    ];

    protected $rules = [
        'photo' => 'required|image|max:10240', // Max 10 MB
    ];

    public function uploadAndProcess()
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processOCR();
            $this->processNLP();
            $this->dispatchBrowserEvent('ocr-completed');
        } catch (\Exception $e) {
            Log::error('OCR Processing Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
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
        Log::info('OCR Text Extracted:', ['text' => $this->ocrText]);
    }

    private function processNLP()
    {
        $nlpService = new GoogleNaturalLanguageService();

        try {
            $entities = $nlpService->analyzeEntities($this->ocrText, $this->selectedLanguage);
            $syntax = $nlpService->analyzeSyntax($this->ocrText, $this->selectedLanguage);

            Log::info('NLP Entities Analysis:', ['entities' => $entities]);
            Log::info('NLP Syntax Analysis:', ['syntax' => $syntax]);

            $this->populateMetadata($entities, $syntax);
        } catch (\Exception $e) {
            Log::error('Error during NLP Analysis:', ['error' => $e->getMessage()]);
            throw $e;
        }
    }

    private function populateMetadata(array $entities, array $syntax)
    {
        $this->resetMetadata();

        foreach ($entities as $entity) {
            switch ($entity['type']) {
                case 'PERSON':
                    $this->assignPersonEntity($entity['name']);
                    break;
                case 'LOCATION':
                    $this->assignLocationEntity($entity['name']);
                    break;
                case 'DATE':
                    $this->processDateEntity($entity['name']);
                    break;
                case 'EVENT':
                    $this->metadata['status'] = $entity['name'];
                    break;
                default:
                    Log::info("Unhandled entity type: {$entity['type']} with value: {$entity['name']}");
            }
        }

        $this->metadata['keywords'] = $this->extractKeywords($syntax, $entities);
        $this->metadata['languages'][] = $this->selectedLanguage;
        $this->metadata['abstract_cs'] = $this->generateAbstract('cs', $this->ocrText);
        $this->metadata['abstract_en'] = $this->generateAbstract('en', $this->ocrText);
        $this->metadata['incipit'] = $this->extractIncipit();
        $this->metadata['explicit'] = $this->extractExplicit();
        $this->metadata['mentioned'] = $this->extractMentionedEntities($entities);
    }

    private function assignPersonEntity(string $name)
    {
        if (empty($this->metadata['author'])) {
            $this->metadata['author'] = $name;
        } elseif (empty($this->metadata['recipient'])) {
            $this->metadata['recipient'] = $name;
        }
    }

    private function assignLocationEntity(string $name)
    {
        if (empty($this->metadata['origin'])) {
            $this->metadata['origin'] = $name;
        } elseif (empty($this->metadata['destination'])) {
            $this->metadata['destination'] = $name;
        }
    }

    private function processDateEntity(string $dateString)
    {
        $this->metadata['date_marked'] = $dateString;

        $formatter = new \IntlDateFormatter(
            $this->selectedLanguage . '_' . strtoupper($this->selectedLanguage),
            \IntlDateFormatter::FULL,
            \IntlDateFormatter::NONE,
            null,
            \IntlDateFormatter::GREGORIAN,
            "dd MMMM yyyy"
        );

        $timestamp = $formatter->parse($dateString);

        if ($timestamp !== false) {
            $dateComponents = getdate($timestamp);
            $this->metadata['year'] = $dateComponents['year'] ?? '';
            $this->metadata['month'] = $dateComponents['mon'] ?? '';
            $this->metadata['day'] = $dateComponents['mday'] ?? '';
        }
    }

    private function resetMetadata()
    {
        $this->metadata = [
            'year' => '',
            'month' => '',
            'day' => '',
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
            'recipient' => '',
            'origin' => '',
            'destination' => '',
            'keywords' => [],
            'languages' => [],
            'abstract_cs' => '',
            'abstract_en' => '',
            'incipit' => '',
            'explicit' => '',
            'mentioned' => [],
            'status' => '',
        ];
    }    

    private function extractKeywords(array $syntax, array $entities): array
    {
        $keywords = [];

        foreach ($syntax['tokens'] ?? [] as $token) {
            if (in_array($token['partOfSpeech']['tag'], ['NOUN', 'PROPN']) && strlen($token['text']['content']) > 2) {
                $keywords[] = $token['text']['content'];
            }
        }

        foreach ($entities as $entity) {
            $keywords[] = $entity['name'];
        }

        return array_unique($keywords);
    }

    private function generateAbstract(string $language, string $text): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $text, -1, PREG_SPLIT_NO_EMPTY);

        if (!$sentences) {
            return '';
        }

        $abstract = $sentences[0];
        if (count($sentences) > 1) {
            $abstract .= ' ' . $sentences[1];
        }

        return $abstract;
    }

    private function extractIncipit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return $sentences[0] ?? '';
    }

    private function extractExplicit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return count($sentences) > 1 ? $sentences[count($sentences) - 1] : '';
    }

    private function extractMentionedEntities(array $entities): array
    {
        return array_unique(array_column($entities, 'name'));
    }

    private function formatText(string $text): string
    {
        $formattedText = preg_replace('/\s*\n\s*/', ' ', $text);
        return trim($formattedText);
    }

    public function render()
    {
        return view('livewire.ocr-upload');
    }
}
