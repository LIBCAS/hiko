<?php

namespace App\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Collection;

class DuplicateDetectionService
{
    protected $prefixes;

    public function __construct(array $prefixes)
    {
        $this->prefixes = $prefixes;
    }

    public function getAllLetters($compareMethod = 'full_texts')
    {
        $letters = collect();
        $columns = $compareMethod === 'meta_data' ? [
            'id', 'date_computed', 'explicit', 'incipit', 'copies', 'languages', 'date_note', 'recipient_note', 'author_note', 
            'destination_note', 'origin_note', 'abstract', 'notes_public' // Added new fields
        ] : [
            'id', 'content',
        ];

        foreach ($this->prefixes as $prefix) {
            $tableName = $prefix . '__letters';
        
            if (!Schema::connection('hiko_historicka_korespondence_cz')->hasTable($tableName)) {
                continue;
            }
        
            try {
                // Fetch up to 200 records from the current table
                $allLetters = DB::connection('hiko_historicka_korespondence_cz')->table($tableName)
                    ->select($columns)
                    ->whereNotNull('date_computed')
                    ->when($compareMethod === 'full_texts', function ($query) {
                        return $query->whereNotNull('content');
                    })
                    ->orderBy('id')
                    ->limit(200)
                    ->get();
        
                // Process records in chunks of 100
                $allLetters->chunk(100)->each(function ($lettersFromTable) use (&$letters, $prefix) {
                    $lettersFromTable->each(function ($letter) use ($prefix) {
                        $letter->prefix = $prefix;
                    });
                    $letters = $letters->merge($lettersFromTable);
                });
            } catch (\Exception $e) {
                \Log::error('Error retrieving letters from table ' . $tableName . ': ' . $e->getMessage());
            }
        }        

        return $letters;
    }

    public function normalizeText(...$textParts)
    {
        $text = implode(' ', $textParts);
        return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }

    public function normalizeLetters($letters, $compareMethod = 'full_texts')
    {
        return $letters->map(function ($letter) use ($compareMethod) {
            if ($compareMethod === 'full_texts') {
                if (isset($letter->content)) {
                    $normalizedContent = $this->normalizeText($letter->content);
                    $letter->content_normalized = $normalizedContent;
                    unset($letter->content);
                }
            } else {
                $normalizedContent = $this->normalizeText(
                    $letter->date_computed ?? '',
                    $letter->explicit ?? '',
                    $letter->incipit ?? '',
                    $letter->copies ?? '',
                    $letter->languages ?? '',
                    $letter->recipient_note ?? '',
                    $letter->author_note ?? '',
                    $letter->date_note ?? '',
                    $letter->destination_note ?? '', // Added new fields
                    $letter->origin_note ?? '', // Added new fields
                    $letter->abstract ?? '', // Added new fields
                    $letter->notes_public ?? '' // Added new fields
                );
                $letter->content_normalized = $normalizedContent;
            }
            return $letter;
        });
    }

    public function calculateSimilarity($text1, $text2)
    {
        $words1 = explode(' ', $text1);
        $words2 = explode(' ', $text2);

        $intersection = count(array_intersect($words1, $words2));
        $union = count(array_unique(array_merge($words1, $words2)));

        return $union > 0 ? round($intersection / $union, 3) : 0;
    }

    public function findPotentialDuplicates($letters, $threshold = 0.5)
    {
        $duplicates = [];
        $lettersArray = $letters->toArray();
        $targetActive = count($this->prefixes) > 1;

        foreach ($lettersArray as $i => $letter1) {
            for ($j = $i + 1; $j < count($lettersArray); $j++) {
                $letter2 = $lettersArray[$j];
                if ($targetActive && $letter1->prefix !== $letter2->prefix || !$targetActive && $letter1->prefix === $letter2->prefix) {
                    $similarity = $this->calculateSimilarity($letter1->content_normalized, $letter2->content_normalized);
                    if ($similarity >= $threshold) {
                        $duplicates[] = [
                            'letter1' => $letter1,
                            'letter2' => $letter2,
                            'similarity' => $similarity,
                        ];
                    }
                }
            }
        }

        return collect($duplicates)->sortByDesc('similarity')->values()->all();
    }

    public function markDuplicates($duplicates)
    {
        foreach ($duplicates as $duplicate) {
            $existingDuplicate = DB::table('duplicates')
                ->where('letter1_id', $duplicate['letter1']->id)
                ->where('letter2_id', $duplicate['letter2']->id)
                ->exists();

            if (!$existingDuplicate) {
                DB::table('duplicates')->insert([
                    'letter1_id' => $duplicate['letter1']->id,
                    'letter2_id' => $duplicate['letter2']->id,
                    'letter1_prefix' => $duplicate['letter1']->prefix,
                    'letter2_prefix' => $duplicate['letter2']->prefix,
                    'similarity' => $duplicate['similarity'],
                ]);
            }
        }
    }

    public function processDuplicates($compareMethod = 'full_texts')
    {
        $letters = $this->getAllLetters($compareMethod);
        $normalizedLetters = $this->normalizeLetters($letters, $compareMethod);
        $potentialDuplicates = $this->findPotentialDuplicates($normalizedLetters);
        $this->markDuplicates($potentialDuplicates);

        return $potentialDuplicates;
    }
}
