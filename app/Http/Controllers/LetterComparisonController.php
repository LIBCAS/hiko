<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Tenant;
use Illuminate\Support\Facades\DB;
use Stancl\Tenancy\Facades\Tenancy;
use Symfony\Component\HttpFoundation\JsonResponse;

class LetterComparisonController extends Controller
{
    protected array $rules = [
        'compare_type' => 'required|in:full_text,other_columns',
        'tenant_to_compare' => 'required|exists:tenants,name',
    ];

    public function index()
    {
        return view('pages.compare-letters.index', [
            'title' => __('hiko.compare_letters_comparision'),
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate($this->rules);
        $currentTenant = Tenant::find(auth()->user()->tenant_id);

        if (!$currentTenant) {
            return response()->json(['error' => 'Current tenant not found.']);
        }

        try {
            $currentTenantLetters = $this->getLettersData($currentTenant->table_prefix, $validated['compare_type']);
            $comparisonTenant = Tenant::where('name', $validated['tenant_to_compare'])->firstOrFail();
            $comparisonTenantLetters = $this->getLettersData($comparisonTenant->table_prefix, $validated['compare_type']);
            $similarityResults = $this->calculateSimilarity($currentTenantLetters, $comparisonTenantLetters, $validated['compare_type']);

            return response()->json(['results' => $similarityResults]);

        } catch (\Exception $e) {
            return response()->json(['error' => 'Failed to retrieve comparison results.']);
        }
    }

    private function getLettersData(string $tablePrefix, string $compareType): array
    {
        $columns = $compareType === 'full_text' ? ['id', 'content'] : ['id', 'uuid', 'abstract', 'incipit'];
        
        return DB::connection('tenant')->table("{$tablePrefix}__letters")->select($columns)->get()->toArray();
    }

    private function calculateSimilarity(array $currentLetters, array $tenantLetters, string $compareType): array
    {
        $results = [];

        foreach ($currentLetters as $currentLetter) {
            // Determine the text to compare based on compare_type
            if ($compareType === 'full_text') {
                if (!isset($currentLetter->id) || !isset($currentLetter->content)) {
                    continue;
                }
                $textA = $currentLetter->content;
            } else {
                if (!isset($currentLetter->id) || !isset($currentLetter->abstract) || !isset($currentLetter->incipit)) {
                    continue;
                }
                $abstract = json_decode($currentLetter->abstract, true);
                $abstractText = $abstract['cs'] ?? '' . ' ' . ($abstract['en'] ?? '');
                $incipitText = $currentLetter->incipit ?? '';
                $textA = trim($abstractText . ' ' . $incipitText);
            }

            foreach ($tenantLetters as $tenantLetter) {
                if ($compareType === 'full_text') {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->content)) {
                        continue;
                    }
                    $textB = $tenantLetter->content;
                } else {
                    if (!isset($tenantLetter->id) || !isset($tenantLetter->abstract) || !isset($tenantLetter->incipit)) {
                        continue;
                    }
                    $abstract = json_decode($tenantLetter->abstract, true);
                    $abstractText = $abstract['cs'] ?? '' . ' ' . ($abstract['en'] ?? '');
                    $incipitText = $tenantLetter->incipit ?? '';
                    $textB = trim($abstractText . ' ' . $incipitText);
                }

                $vectorA = $this->vectorizeText($textA);
                $vectorB = $this->vectorizeText($textB);
                $similarityScore = $this->calculateCosineSimilarity($vectorA, $vectorB);

                if ($similarityScore > 0.6) {
                    $results[] = [
                        'letter_id' => $currentLetter->id,
                        'tenant' => $tenantLetter->tenant ?? 'unknown',
                        'similarity' => round($similarityScore * 100, 2),
                        'match_info' => "Matched with {$tenantLetter->id}",
                    ];
                }
            }
        }

        return $results;
    }

    private function isValidResultStructure(array $result): bool
    {
        return !array_diff_key(array_flip(['letter_id', 'tenant', 'similarity', 'match_info']), $result);
    }

    private function vectorizeText(?string $text): array
    {
        $tfidf = [];
        $terms = $this->tokenizeText($text ?? '');
        $totalTerms = count($terms);

        foreach ($terms as $term) {
            $tf = substr_count($text, $term) / $totalTerms;
            $idf = log(1 + (1 / $this->getDocumentFrequency($term)));
            $tfidf[$term] = $tf * $idf;
        }
        return $tfidf;
    }

    private function calculateCosineSimilarity(array $vectorA, array $vectorB): float
    {
        $dotProduct = $magnitudeA = $magnitudeB = 0;
        foreach ($vectorA as $term => $value) {
            $dotProduct += $value * ($vectorB[$term] ?? 0);
            $magnitudeA += $value ** 2;
        }
        foreach ($vectorB as $value) {
            $magnitudeB += $value ** 2;
        }
        return $magnitudeA && $magnitudeB ? $dotProduct / (sqrt($magnitudeA) * sqrt($magnitudeB)) : 0;
    }

    private function tokenizeText(?string $text): array
    {
        return preg_split('/\W+/', strtolower($text ?? ''), -1, PREG_SPLIT_NO_EMPTY);
    }

    private function getDocumentFrequency(string $term): int
    {
        $currentTenantId = auth()->id();
        $currentTenant = Tenant::find($currentTenantId);
        if (!$currentTenant) {
            return 1;
        }

        $tableName = "{$currentTenant->table_prefix}__letters";

        try {
            $count = DB::connection('tenant')
                ->table($tableName)
                ->where('content', 'LIKE', "%{$term}%")
                ->count();

            return $count > 0 ? $count : 1;
        } catch (\Exception $e) {
            return 1;
        }
    }
}
