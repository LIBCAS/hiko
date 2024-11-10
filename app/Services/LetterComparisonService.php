<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use App\Models\Tenant;

class LetterComparisonService
{
    /**
     * Handle the letter comparison process.
     *
     * @param string $compareType Type of comparison (e.g., 'full_text').
     * @param string $tenantToCompare Name of the tenant to compare with.
     * @param string $currentTenantPrefix Table prefix of the current tenant.
     * @return array Array of similarity results.
     * @throws \Exception If tenants are not found or data fetching fails.
     */
    public function search(string $compareType, string $tenantToCompare, string $currentTenantPrefix): array
    {
        // Fetch current tenant based on table_prefix
        $currentTenant = Tenant::where('table_prefix', $currentTenantPrefix)->first();

        if (!$currentTenant) {
            throw new \Exception('Current tenant not found.');
        }

        // Fetch comparison tenant
        $comparisonTenant = Tenant::where('name', $tenantToCompare)->first();

        if (!$comparisonTenant) {
            throw new \Exception('Comparison tenant not found.');
        }

        // Fetch letters data
        $currentTenantLetters = $this->getLettersData($currentTenant->table_prefix, $compareType);
        $comparisonTenantLetters = $this->getLettersData($comparisonTenant->table_prefix, $compareType);

        // Calculate similarity
        $similarityResults = $this->calculateSimilarity($currentTenantLetters, $comparisonTenantLetters, $compareType, $comparisonTenant->name);

        return $similarityResults;
    }

    /**
     * Get letters data from a tenant's letters table.
     *
     * @param string $tablePrefix
     * @param string $compareType
     * @return array
     * @throws \Exception If data fetching fails.
     */
    private function getLettersData(string $tablePrefix, string $compareType): array
    {
        $columns = $compareType === 'full_text' ? ['id', 'content'] : ['id', 'uuid', 'abstract', 'incipit'];

        $tableName = "{$tablePrefix}__letters";

        try {
            $letters = DB::connection('tenant')
                ->table($tableName)
                ->select($columns)
                ->get()
                ->toArray();

            return $letters;
        } catch (\Exception $e) {
            throw $e;
        }
    }

    /**
     * Calculate similarity between two sets of letters.
     *
     * @param array $currentLetters
     * @param array $tenantLetters
     * @param string $compareType
     * @param string $comparisonTenantName
     * @return array
     */
    private function calculateSimilarity(array $currentLetters, array $tenantLetters, string $compareType, string $comparisonTenantName): array
    {
        $results = [];

        foreach ($currentLetters as $currentLetter) {
            // Determine the text to compare based on compare_type
            if ($compareType === 'full_text') {
                if (!isset($currentLetter->id) || !isset($currentLetter->content) || empty(trim($currentLetter->content))) {
                    continue;
                }
                $textA = $currentLetter->content;
            } else {
                // For other_columns, concatenate abstract and incipit
                if (!isset($currentLetter->id) || !isset($currentLetter->abstract) || !isset($currentLetter->incipit)) {
                    continue;
                }
                // Decode JSON fields if necessary
                $abstract = json_decode($currentLetter->abstract, true);
                $abstractText = isset($abstract['cs']) && !empty(trim($abstract['cs'])) ? $abstract['cs'] : '';
                $abstractText .= ' ' . (isset($abstract['en']) && !empty(trim($abstract['en'])) ? $abstract['en'] : '');
                $abstractText = trim($abstractText);
                $incipitText = isset($currentLetter->incipit) && !empty(trim($currentLetter->incipit)) ? $currentLetter->incipit : '';
                $textA = trim($abstractText . ' ' . $incipitText);

                if (empty($textA)) {
                    continue;
                }
            }

            foreach ($tenantLetters as $tenantLetter) {
                if ($compareType === 'full_text') {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->content) || empty(trim($tenantLetter->content))) {
                        continue;
                    }
                    $textB = $tenantLetter->content;
                } else {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->abstract) || !isset($tenantLetter->incipit)) {
                        continue;
                    }
                    $abstract = json_decode($tenantLetter->abstract, true);
                    $abstractText = isset($abstract['cs']) && !empty(trim($abstract['cs'])) ? $abstract['cs'] : '';
                    $abstractText .= ' ' . (isset($abstract['en']) && !empty(trim($abstract['en'])) ? $abstract['en'] : '');
                    $abstractText = trim($abstractText);
                    $incipitText = isset($tenantLetter->incipit) && !empty(trim($tenantLetter->incipit)) ? $tenantLetter->incipit : '';
                    $textB = trim($abstractText . ' ' . $incipitText);

                    if (empty($textB)) {
                        continue;
                    }
                }

                // Vectorize text
                $vectorA = $this->vectorizeText($textA);
                $vectorB = $this->vectorizeText($textB);

                // Calculate cosine similarity
                $similarityScore = $this->calculateCosineSimilarity($vectorA, $vectorB);

                // Threshold
                if ($similarityScore > 0.6) {
                    $results[] = [
                        'letter_id' => $currentLetter->id,
                        'tenant' => $comparisonTenantName, // Assign correct tenant name
                        'similarity' => round($similarityScore * 100, 2),
                        'match_info' => "Matched with Letter ID {$tenantLetter->id}",
                    ];
                }
            }
        }

        return $results;
    }

    /**
     * Vectorize the given text using TF-IDF.
     *
     * @param string $text
     * @return array
     */
    private function vectorizeText(string $text): array
    {
        $tfidf = [];
        $terms = $this->tokenizeText($text);
        $totalTerms = count($terms);

        if ($totalTerms === 0) {
            return $tfidf;
        }

        // Calculate term frequency (TF)
        $termCounts = array_count_values($terms);
        $tf = [];
        foreach ($termCounts as $term => $count) {
            $tf[$term] = $count / $totalTerms;
        }

        // Calculate inverse document frequency (IDF)
        $idf = [];
        foreach ($tf as $term => $termFreq) {
            $idf[$term] = log(1 + (1 / $this->getDocumentFrequency($term)));
        }

        // Calculate TF-IDF
        foreach ($tf as $term => $termFreq) {
            $tfidf[$term] = $termFreq * $idf[$term];
        }

        return $tfidf;
    }

    /**
     * Tokenize text into individual terms.
     *
     * @param string $text
     * @return array
     */
    private function tokenizeText(string $text): array
    {
        return preg_split('/\W+/', strtolower($text), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Retrieve document frequency for a term.
     * 
     * **Important:** Implement this method to return the actual document frequency from your data source.
     *
     * @param string $term
     * @return int
     */
    private function getDocumentFrequency(string $term): int
    {
        // Implement actual DF calculation based on your data
        // For demonstration, returning a fixed value
        return 1;
    }

    /**
     * Calculate cosine similarity between two vectors.
     *
     * @param array $vectorA
     * @param array $vectorB
     * @return float
     */
    private function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = 0.0;
        $magnitudeA = 0.0;
        $magnitudeB = 0.0;

        foreach ($vectorA as $term => $value) {
            $dotProduct += $value * ($vectorB[$term] ?? 0);
            $magnitudeA += $value ** 2;
        }

        foreach ($vectorB as $value) {
            $magnitudeB += $value ** 2;
        }

        return ($magnitudeA > 0 && $magnitudeB > 0) ? ($dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB))) : 0;
    }
}
