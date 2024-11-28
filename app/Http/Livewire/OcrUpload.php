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

    // Form Fields
    public $selectedLanguage = 'cs';
    public $photo;
    public $isProcessing = false;

    // Temporary File Handling
    public $tempImageName;
    public $tempImagePath;

    // OCR and Metadata
    public $ocrText = '';
    public $metadata = [
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
        'photo' => 'required|image|max:10240', // Max 10MB
        'selectedLanguage' => 'required|in:cs,en,de,fr,es',
    ];

    protected $listeners = [
        'clearSelection' => 'clearSelectedText',
    ];

    /**
     * Handle the upload and processing of the document.
     *
     * @param GoogleDocumentAIService $documentAIService
     * @return void
     */
    public function uploadAndProcess(GoogleDocumentAIService $documentAIService)
    {
        $this->validate();
        $this->isProcessing = true;

        try {
            $this->saveUploadedFile();
            $this->processDocumentAI($documentAIService);
            $this->dispatchBrowserEvent('ocr-completed');
        } catch (\Exception $e) {
            Log::error('Document Processing Error: ' . $e->getMessage());
            $this->dispatchBrowserEvent('ocr-failed', ['message' => __('hiko.ocr_processing_error')]);
        } finally {
            $this->isProcessing = false;
            $this->deleteTemporaryFile(); // Delete the file after processing
        }
    }

    /**
     * Save the uploaded file temporarily.
     *
     * @return void
     */
    private function saveUploadedFile()
    {
        $this->tempImageName = Str::uuid() . '.' . $this->photo->getClientOriginalExtension();
        $this->tempImagePath = "livewire-tmp/{$this->tempImageName}";

        Storage::disk('local')->put($this->tempImagePath, file_get_contents($this->photo->getRealPath()));

        Log::info('File stored at: ' . Storage::disk('local')->path($this->tempImagePath));
    }

    /**
     * Process the document using Google Document AI.
     *
     * @param GoogleDocumentAIService $documentAIService
     * @return void
     */
    private function processDocumentAI(GoogleDocumentAIService $documentAIService)
    {
        $absolutePath = Storage::disk('local')->path($this->tempImagePath);
        $result = $documentAIService->processDocument($absolutePath, $this->selectedLanguage);

        // Log the raw result for debugging
        Log::info('Document AI Result:', $result);

        $this->ocrText = $this->formatText($result['text']);

        // Reset metadata before processing new entities
        $this->resetMetadata();

        foreach ($result['entities'] as $entity) {
            $this->mapEntityToMetadata($entity);
        }

        // Further processing: keyword extraction, abstracts, etc.
        $this->metadata['keywords'] = $this->extractKeywords($result['text']);
        $this->metadata['languages'][] = $this->selectedLanguage;
        $this->metadata['abstract_cs'] = $this->extractAbstract('cs');
        $this->metadata['abstract_en'] = $this->extractAbstract('en');
        $this->metadata['incipit'] = $this->extractIncipit();
        $this->metadata['explicit'] = $this->extractExplicit();
        $this->metadata['mentioned'] = $this->extractMentionedEntities($result['entities']);
    }

    /**
     * Map a single entity to the corresponding metadata field.
     *
     * @param array $entity
     * @return void
     */
    private function mapEntityToMetadata(array $entity)
    {
        // Log each entity being processed
        Log::info("Mapping entity: Type={$entity['type']}, Name={$entity['name']}, Confidence={$entity['confidence']}");

        switch ($entity['type']) {
            case 'PERSON':
                $this->processPersonEntity($entity);
                break;

            case 'DATE':
                $this->processDateEntity($entity);
                break;

            case 'LOCATION':
                $this->processLocationEntity($entity);
                break;

            case 'ORGANIZATION':
                $this->metadata['related_resources'][] = $entity['name'];
                break;

            case 'EVENT':
                $this->metadata['status'] = $entity['name'];
                break;

            // Add more cases as needed based on the entities returned by Document AI
            case 'EMAIL':
                $this->metadata['notes_private'] .= $entity['name'] . "\n";
                break;

            case 'PHONE_NUMBER':
                $this->metadata['notes_public'] .= $entity['name'] . "\n";
                break;

            // Default case for unhandled entities
            default:
                Log::warning("Unhandled entity type: {$entity['type']} with value: {$entity['name']}");
                break;
        }
    }

    /**
     * Process a date entity.
     *
     * @param array $entity
     * @return void
     */
    private function processDateEntity(array $entity)
    {
        $dateString = $entity['mentionText'];
        Log::info("Processing date entity: {$dateString}");

        $this->metadata['date_marked'] = $dateString;

        // Normalize the input: trim spaces
        $normalizedDateString = trim($dateString);
        Log::info("Normalized date entity: {$normalizedDateString}");

        // Use IntlDateFormatter to parse the date
        $formatter = new \IntlDateFormatter(
            "{$this->selectedLanguage}_{$this->selectedLanguage}", // Locale (e.g., 'cs_CS')
            \IntlDateFormatter::FULL, // Date format
            \IntlDateFormatter::NONE, // No time format
            null, // Use the default timezone
            \IntlDateFormatter::GREGORIAN, // Calendar type
            "dd MMMM yyyy" // Expected pattern for parsing
        );

        $timestamp = $formatter->parse($normalizedDateString);

        if ($timestamp === false) {
            Log::warning("Failed to parse date for: {$normalizedDateString}");
            $this->metadata['date_uncertain'] = true;
            return;
        }

        // Extract components from the timestamp
        $dateComponents = getdate($timestamp);

        $this->metadata['day'] = $dateComponents['mday'];
        $this->metadata['month'] = $dateComponents['mon'];
        $this->metadata['year'] = $dateComponents['year'];

        Log::info("Parsed date successfully: Year = {$this->metadata['year']}, Month = {$this->metadata['month']}, Day = {$this->metadata['day']}");

        // Assuming high confidence implies inferred and certain
        $this->metadata['date_inferred'] = $entity['confidence'] < 0.9 ? true : false;
        $this->metadata['date_uncertain'] = $entity['confidence'] < 0.7 ? true : false;
    }

    /**
     * Process a person entity.
     *
     * @param array $entity
     * @return void
     */
    private function processPersonEntity(array $entity)
    {
        $personName = $entity['name'];
        $confidence = $entity['confidence'];

        if (empty($this->metadata['author'])) {
            $this->metadata['author'] = $personName;
            $this->metadata['author_inferred'] = $confidence < 0.9 ? true : false;
            $this->metadata['author_uncertain'] = $confidence < 0.7 ? true : false;
        } elseif (empty($this->metadata['recipient']) && $personName !== $this->metadata['author']) {
            $this->metadata['recipient'] = $personName;
            $this->metadata['recipient_inferred'] = $confidence < 0.9 ? true : false;
            $this->metadata['recipient_uncertain'] = $confidence < 0.7 ? true : false;
        }

        Log::info("Processed person entity: {$personName}, Inferred: {$this->metadata['author_inferred']}, Uncertain: {$this->metadata['author_uncertain']}");
    }

    /**
     * Process a location entity.
     *
     * @param array $entity
     * @return void
     */
    private function processLocationEntity(array $entity)
    {
        $locationName = $entity['name'];
        $confidence = $entity['confidence'];

        if (empty($this->metadata['origin'])) {
            $this->metadata['origin'] = $locationName;
            $this->metadata['origin_inferred'] = $confidence < 0.9 ? true : false;
            $this->metadata['origin_uncertain'] = $confidence < 0.7 ? true : false;
        } elseif (empty($this->metadata['destination']) && $locationName !== $this->metadata['origin']) {
            $this->metadata['destination'] = $locationName;
            $this->metadata['destination_inferred'] = $confidence < 0.9 ? true : false;
            $this->metadata['destination_uncertain'] = $confidence < 0.7 ? true : false;
        }

        Log::info("Processed location entity: {$locationName}, Inferred: {$this->metadata['origin_inferred']}, Uncertain: {$this->metadata['origin_uncertain']}");
    }

    /**
     * Reset the metadata to its default state.
     *
     * @return void
     */
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

    /**
     * Extract abstract based on language.
     *
     * @param string $language
     * @return string
     */
    private function extractAbstract(string $language): string
    {
        // Split the OCR text into sentences
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);

        // If there are no sentences, return an empty string
        if (empty($sentences)) {
            return '';
        }

        // Basic summarization: Pick the first sentence as a fallback
        $abstract = trim($sentences[0]);

        // Language-specific summarization logic
        if ($language === 'cs') {
            $abstract .= ' ' . $this->findRelevantSentence($sentences, 'cs');
        } elseif ($language === 'en') {
            $abstract .= ' ' . $this->findRelevantSentence($sentences, 'en');
        }

        return $abstract;
    }

    /**
     * Find a relevant sentence based on language-specific keywords.
     *
     * @param array $sentences
     * @param string $language
     * @return string
     */
    private function findRelevantSentence(array $sentences, string $language): string
    {
        $relevanceKeywords = [
            'cs' => ['důležitý', 'zpráva', 'data'], // Example Czech keywords for relevance
            'en' => ['important', 'information', 'summary'], // Example English keywords for relevance
        ];

        $keywords = $relevanceKeywords[$language] ?? [];

        foreach ($sentences as $sentence) {
            foreach ($keywords as $keyword) {
                if (stripos($sentence, $keyword) !== false) {
                    return $sentence; // Return the first matching sentence
                }
            }
        }

        return ''; // Return an empty string if no relevant sentence is found
    }

    /**
     * Extract keywords from the text.
     *
     * @param string $text
     * @return array
     */
    private function extractKeywords(string $text): array
    {
        // Simple keyword extraction based on word length and frequency
        $words = str_word_count(strtolower($text), 1);
        $words = array_filter($words, function ($word) {
            return strlen($word) > 2;
        });

        $frequency = array_count_values($words);
        arsort($frequency);

        // Get top 10 keywords
        return array_slice(array_keys($frequency), 0, 10);
    }

    /**
     * Extract the first sentence as incipit.
     *
     * @return string
     */
    private function extractIncipit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return isset($sentences[0]) ? trim($sentences[0]) . '.' : '';
    }

    /**
     * Extract the penultimate sentence as explicit.
     *
     * @return string
     */
    private function extractExplicit(): string
    {
        $sentences = preg_split('/(?<=[.?!])\s+/', $this->ocrText, -1, PREG_SPLIT_NO_EMPTY);
        return count($sentences) > 1 ? trim($sentences[count($sentences) - 2]) . '.' : '';
    }

    /**
     * Extract mentioned entities.
     *
     * @param array $entities
     * @return array
     */
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

    /**
     * Format the extracted text for better readability.
     *
     * @param string $text
     * @return string
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
     *
     * @return void
     */
    public function clearSelectedText()
    {
        $this->selectedText = '';
    }

    /**
     * Delete the temporary uploaded file.
     *
     * @return void
     */
    public function deleteTemporaryFile()
    {
        if ($this->tempImagePath && Storage::disk('local')->exists($this->tempImagePath)) {
            Storage::disk('local')->delete($this->tempImagePath);
            Log::info("Temporary file deleted: {$this->tempImagePath}");
            $this->tempImagePath = null;
        }
    }

    /**
     * Render the Livewire component view.
     *
     * @return \Illuminate\View\View
     */
    public function render()
    {
        return view('livewire.ocr-upload');
    }
}
