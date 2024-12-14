<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Illuminate\Support\Facades\Log;

class LetterComparisonService
{
    private const SIMILARITY_THRESHOLD = 0.6;
    private const DEFAULT_DOCUMENT_COUNT = 10000; // Used if you don't have actual IDF data

    /**
     * Perform the letter comparison using TF-IDF and cosine similarity.
     *
     * @param string $compareType 'full_text' or 'other_columns'
     * @param string $tenantToCompare Name of the tenant to compare with
     * @param string $currentTenantPrefix Table prefix of the current tenant
     * @return array Array of similarity results
     */
    public function search(string $compareType, string $tenantToCompare, string $currentTenantPrefix): array
    {
        try {
            $currentTenant = Tenant::where('table_prefix', $currentTenantPrefix)->firstOrFail();
            $comparisonTenant = Tenant::where('name', $tenantToCompare)->firstOrFail();

            // Fetch letters from both tenants
            $currentTenantLetters = $this->getLettersData($currentTenant->table_prefix, $compareType);
            $comparisonTenantLetters = $this->getLettersData($comparisonTenant->table_prefix, $compareType);

            if (empty($currentTenantLetters) || empty($comparisonTenantLetters)) {
                return [];
            }

            // Extract text from each letter
            $currentTexts = array_map(fn($l) => $this->extractText($l, $compareType), $currentTenantLetters);
            $comparisonTexts = array_map(fn($l) => $this->extractText($l, $compareType), $comparisonTenantLetters);

            // Filter out empty texts
            $currentNonEmpty = [];
            $comparisonNonEmpty = [];
            foreach ($currentTenantLetters as $i => $letter) {
                if (!empty($currentTexts[$i])) {
                    $currentNonEmpty[] = ['letter' => $letter, 'text' => $currentTexts[$i]];
                }
            }
            foreach ($comparisonTenantLetters as $j => $letter) {
                if (!empty($comparisonTexts[$j])) {
                    $comparisonNonEmpty[] = ['letter' => $letter, 'text' => $comparisonTexts[$j]];
                }
            }

            if (empty($currentNonEmpty) || empty($comparisonNonEmpty)) {
                return [];
            }

            // Build a combined vocabulary from both sets
            $allTexts = array_merge(array_column($currentNonEmpty, 'text'), array_column($comparisonNonEmpty, 'text'));
            // Tokenize all texts and build a global term frequency map
            $documents = [];
            foreach ($allTexts as $text) {
                $terms = $this->tokenizeText($text);
                $documents[] = $terms;
            }

            // Compute IDF
            $idf = $this->computeIDF($documents);

            // Precompute TF-IDF vectors for all letters
            $currentVectors = [];
            foreach ($currentNonEmpty as $item) {
                $currentVectors[] = $this->computeTFIDFVector($item['text'], $idf);
            }

            $comparisonVectors = [];
            foreach ($comparisonNonEmpty as $item) {
                $comparisonVectors[] = $this->computeTFIDFVector($item['text'], $idf);
            }

            // Calculate similarities
            $results = [];
            foreach ($currentNonEmpty as $i => $cLetter) {
                foreach ($comparisonNonEmpty as $j => $tLetter) {
                    $similarityScore = $this->calculateCosineSimilarity($currentVectors[$i], $comparisonVectors[$j]);
                    if ($similarityScore > self::SIMILARITY_THRESHOLD) {
                        $results[] = [
                            'letter_id' => $cLetter['letter']->id,
                            'tenant' => $comparisonTenant->name,
                            'similarity' => round($similarityScore * 100, 2),
                            'match_info' => "Matched with Letter ID {$tLetter['letter']->id}",
                        ];
                    }
                }
            }

            return $results;
        } catch (\Exception $e) {
            Log::error("Letter comparison failed: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Fetch letters data depending on compareType.
     */
    private function getLettersData(string $tablePrefix, string $compareType): array
    {
        try {
            if ($compareType === 'full_text') {
                $columns = ['id', 'content'];
            } else {
                $columns = ['id', 'abstract', 'incipit'];
            }

            $tableName = "{$tablePrefix}__letters";
            return DB::connection('tenant')->table($tableName)->select($columns)->get()->toArray();
        } catch (\Exception $e) {
            Log::error("Failed to fetch letters data from {$tablePrefix}__letters: {$e->getMessage()}");
            return [];
        }
    }

    /**
     * Extract the text for comparison from a letter record based on compareType.
     */
    private function extractText(object $letter, string $compareType): string
    {
        if ($compareType === 'full_text') {
            return isset($letter->content) && !empty(trim($letter->content)) ? trim($letter->content) : '';
        }

        // other_columns: combine abstract and incipit
        $abstractText = '';
        if (!empty($letter->abstract)) {
            $abstract = json_decode($letter->abstract, true);
            if (is_array($abstract)) {
                $abstractText = trim(($abstract['cs'] ?? '') . ' ' . ($abstract['en'] ?? ''));
            }
        }

        $incipitText = isset($letter->incipit) ? trim($letter->incipit) : '';
        $combined = trim($abstractText . ' ' . $incipitText);
        return $combined;
    }

    /**
     * Tokenize text into an array of lowercase terms.
     */
    private function tokenizeText(string $text): array
    {
        return preg_split('/\W+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY) ?: [];
    }

    /**
     * Compute IDF values for all terms.
     */
    private function computeIDF(array $documents): array
    {
        $docCount = count($documents);
        $termDocFrequency = [];

        foreach ($documents as $terms) {
            $uniqueTerms = array_unique($terms);
            foreach ($uniqueTerms as $term) {
                if (!isset($termDocFrequency[$term])) {
                    $termDocFrequency[$term] = 0;
                }
                $termDocFrequency[$term]++;
            }
        }

        // IDF = log(total_docs / (1 + docFreq))
        $idf = [];
        foreach ($termDocFrequency as $term => $freq) {
            $idf[$term] = log(($docCount + 1) / (1 + $freq));
        }

        return $idf;
    }

    /**
     * Compute TF-IDF vector for a given text.
     */
    private function computeTFIDFVector(string $text, array $idf): array
    {
        $terms = $this->tokenizeText($text);
        if (empty($terms)) {
            return [];
        }

        $totalTerms = count($terms);
        $termCounts = array_count_values($terms);
        $vector = [];

        foreach ($termCounts as $term => $count) {
            if (isset($idf[$term])) {
                $tf = $count / $totalTerms;
                $vector[$term] = $tf * $idf[$term];
            }
        }

        return $vector;
    }

    /**
     * Calculate cosine similarity between two TF-IDF vectors.
     */
    private function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        // Use only terms from the smaller vector to reduce time
        // if needed. For now, we use vectorA terms.
        foreach ($vectorA as $term => $valA) {
            $dotProduct += $valA * ($vectorB[$term] ?? 0);
            $magnitudeA += $valA ** 2;
        }

        // Sum magnitudes
        foreach ($vectorB as $valB) {
            $magnitudeB += $valB ** 2;
        }

        if ($magnitudeA > 0 && $magnitudeB > 0) {
            return $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB));
        }

        return 0.0;
    }
}
