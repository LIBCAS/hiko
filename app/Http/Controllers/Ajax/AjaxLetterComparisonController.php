<?php

namespace App\Http\Controllers\Ajax;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use App\Models\Tenant;
use Symfony\Component\HttpFoundation\JsonResponse;

class AjaxLetterComparisonController extends Controller
{
    /**
     * Handle the letter comparison process via AJAX.
     */
    public function search(Request $request): JsonResponse
    {    
        // Validate incoming request data
        $validated = $request->validate([
            'compare_type' => 'required|in:full_text,other_columns',
            'tenant_to_compare' => 'required|exists:tenants,name',
        ]);

        // Retrieve the current tenant based on the authenticated user's tenant_id
        $currentTenant = Tenant::find(auth()->user()->tenant_id);

        // Check if the current tenant exists
        if (!$currentTenant) {
            return response()->json(['error' => 'Current tenant not found.']);
        }

        try {
            // Fetch letters data for the current tenant
            $currentTenantLetters = $this->getLettersData($currentTenant->table_prefix, $validated['compare_type']);

            // Fetch the comparison tenant based on the provided tenant name
            $comparisonTenant = Tenant::where('name', $validated['tenant_to_compare'])->firstOrFail();

            // Fetch letters data for the comparison tenant
            $comparisonTenantLetters = $this->getLettersData($comparisonTenant->table_prefix, $validated['compare_type']);

            // Calculate similarity between the two sets of letters
            // Pass the comparison tenant's name as the fourth argument to assign it correctly in the results
            $similarityResults = $this->calculateSimilarity(
                $currentTenantLetters, 
                $comparisonTenantLetters, 
                $validated['compare_type'], 
                $comparisonTenant->name
            );

            // Return the similarity results as a JSON response
            return response()->json(['results' => $similarityResults]);

        } catch (\Exception $e) {
            // Handle any exceptions that occur during the comparison process
            return response()->json(['error' => 'Failed to retrieve comparison results.']);
        }
    }

    /**
     * Retrieve letters data from a tenant's letters table based on the comparison type.
     *
     * @param string $tablePrefix
     * @param string $compareType
     * @return array
     */
    private function getLettersData(string $tablePrefix, string $compareType): array
    {
        // Define the columns to select based on the comparison type
        $columns = $compareType === 'full_text' ? ['id', 'content'] : ['id', 'uuid', 'abstract', 'incipit'];

        // Construct the table name using the tenant's table prefix
        $tableName = "{$tablePrefix}__letters";

        // Fetch the letters data from the tenant's letters table
        return DB::connection('tenant')
                 ->table($tableName)
                 ->select($columns)
                 ->get()
                 ->toArray();
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
                    // Skip letters with missing or empty content
                    continue;
                }
                $textA = $currentLetter->content;
            } else {
                // For other_columns, concatenate abstract and incipit
                if (!isset($currentLetter->id) || !isset($currentLetter->abstract) || !isset($currentLetter->incipit)) {
                    // Skip letters with missing abstract or incipit
                    continue;
                }
                // Decode JSON fields if necessary
                $abstract = json_decode($currentLetter->abstract, true);
                $abstractText = (isset($abstract['cs']) && !empty(trim($abstract['cs']))) ? $abstract['cs'] : '';
                $abstractText .= ' ' . ((isset($abstract['en']) && !empty(trim($abstract['en']))) ? $abstract['en'] : '');
                $abstractText = trim($abstractText);
                $incipitText = (isset($currentLetter->incipit) && !empty(trim($currentLetter->incipit))) ? $currentLetter->incipit : '';
                $textA = trim($abstractText . ' ' . $incipitText);

                // Skip if the concatenated text is empty
                if (empty($textA)) {
                    continue;
                }
            }

            foreach ($tenantLetters as $tenantLetter) {
                if ($compareType === 'full_text') {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->content) || empty(trim($tenantLetter->content))) {
                        // Skip tenant letters with missing or empty content
                        continue;
                    }
                    $textB = $tenantLetter->content;
                } else {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->abstract) || !isset($tenantLetter->incipit)) {
                        // Skip tenant letters with missing abstract or incipit
                        continue;
                    }
                    $abstract = json_decode($tenantLetter->abstract, true);
                    $abstractText = (isset($abstract['cs']) && !empty(trim($abstract['cs']))) ? $abstract['cs'] : '';
                    $abstractText .= ' ' . ((isset($abstract['en']) && !empty(trim($abstract['en']))) ? $abstract['en'] : '');
                    $abstractText = trim($abstractText);
                    $incipitText = (isset($tenantLetter->incipit) && !empty(trim($tenantLetter->incipit))) ? $tenantLetter->incipit : '';
                    $textB = trim($abstractText . ' ' . $incipitText);

                    // Skip if the concatenated text is empty
                    if (empty($textB)) {
                        continue;
                    }
                }

                // Vectorize text
                $vectorA = $this->vectorizeText($textA);
                $vectorB = $this->vectorizeText($textB);

                // Calculate cosine similarity
                $similarityScore = $this->calculateCosineSimilarity($vectorA, $vectorB);

                // Threshold: Only include results with similarity above 69%
                if ($similarityScore > 0.6) {
                    $results[] = [
                        'letter_id' => $currentLetter->id,
                        'tenant' => $comparisonTenantName, // Assign the correct tenant name
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
     * @param string|null $text
     * @return array
     */
    private function vectorizeText(?string $text): array
    {
        $tfidf = [];
        $terms = $this->tokenizeText($text ?? '');
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
     * @param string|null $text
     * @return array
     */
    private function tokenizeText(?string $text): array
    {
        return preg_split('/\W+/', strtolower($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);
    }

    /**
     * Retrieve document frequency for a term.
     * Note: This is currently a placeholder.
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
