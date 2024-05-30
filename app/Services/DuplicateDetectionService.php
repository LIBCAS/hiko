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
            'id', 'date_computed', 'explicit', 'incipit', 'copies', 'languages', 'date_note', 'recipient_note', 'author_note'
        ] : [
            'id', 'content',
        ];

        foreach ($this->prefixes as $prefix) {
            $tableName = $prefix . '__letters';

            if (!Schema::connection('hikomulti')->hasTable($tableName)) {
                continue;
            }

            try {
                $lettersFromTable = DB::connection('hikomulti')
                    ->table($tableName)
                    ->select($columns)
                    ->whereNotNull('date_computed')
                    ->when($compareMethod === 'full_texts', function ($query) {
                        return $query->whereNotNull('content');
                    })
                    ->get();

                $lettersFromTable->each(function ($letter) use ($prefix) {
                    $letter->prefix = $prefix;
                });

                $letters = $letters->merge($lettersFromTable);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
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
                if (isset($letter->date_computed) || isset($letter->explicit) || isset($letter->incipit) || isset($letter->copies) || isset($letter->languages) || isset($letter->recipient_note) || isset($letter->author_note) || isset($letter->date_note)) {
                    $normalizedContent = $this->normalizeText($letter->date_computed, $letter->explicit, $letter->incipit, $letter->copies, $letter->languages, $letter->recipient_note, $letter->author_note, $letter->date_note);
                    $letter->content_normalized = $normalizedContent;
                } else {
                    $letter->content_normalized = $this->normalizeText('');
                }
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

        $similarity = $intersection / $union;

        return round($similarity, 3);
    }

    public function findPotentialDuplicates($letters, $threshold = 0.5)
    {
        $duplicates = [];
        $lettersArray = $letters->toArray();
        $batchSize = 1000;
        $numBatches = ceil(count($lettersArray) / $batchSize);
        $targetActive = count($this->prefixes) > 1;
    
        for ($batch = 0; $batch < $numBatches; $batch++) {
            for ($i = $batch * $batchSize; $i < min(($batch + 1) * $batchSize, count($lettersArray)); $i++) {
                for ($j = $i + 1; $j < count($lettersArray); $j++) {
                    if ($targetActive && $lettersArray[$i]->prefix !== $lettersArray[$j]->prefix) {
                        $similarity = $this->calculateSimilarity($lettersArray[$i]->content_normalized, $lettersArray[$j]->content_normalized);
                        if ($similarity >= $threshold) {
                            $duplicates[] = [
                                'letter1' => $lettersArray[$i],
                                'letter2' => $lettersArray[$j],
                                'similarity' => $similarity,
                            ];
                        }
                    } elseif (!$targetActive && $lettersArray[$i]->prefix === $lettersArray[$j]->prefix) {
                        $similarity = $this->calculateSimilarity($lettersArray[$i]->content_normalized, $lettersArray[$j]->content_normalized);
                        if ($similarity >= $threshold) {
                            $duplicates[] = [
                                'letter1' => $lettersArray[$i],
                                'letter2' => $lettersArray[$j],
                                'similarity' => $similarity,
                            ];
                        }
                    }
                }
            }
        }
    
        return $duplicates;
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
