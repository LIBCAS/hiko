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

    public function getAllLetters()
    {
        $letters = collect();
    
        foreach ($this->prefixes as $prefix) {
            $tableName = $prefix . '__letters';
    
            if (!Schema::connection('hikomulti')->hasTable($tableName)) {
                continue;
            }
    
            try {
                $lettersFromTable = DB::connection('hikomulti')
                    ->table($tableName)
                    ->select('explicit', 'content', 'date_computed', 'id') //TODO: add more fields
                    ->get();
    
                $lettersFromTable->each(function ($letter) use ($prefix) {
                    $letter->prefix = $prefix;
                });
    
                $letters = $letters->merge($lettersFromTable);
            } catch (\Exception $e) {
                return response()->json(['error' => $e->getMessage()], 500);
            }
        }
        return $this->normalizeAndHashLetters($letters);
    }
    
    public function normalizeText($text)
    {
        return strtolower(trim(preg_replace('/\s+/', ' ', $text)));
    }
    
    public function normalizeAndHashLetters($letters)
    {
        return $letters->map(function ($letter) {
            if (isset($letter->explicit) && isset($letter->content)) {
                $normalizedContent = $this->normalizeText($letter->explicit . ' ' . $letter->content . ' ' . $letter->date_computed);
                $letter->content_normalized = $normalizedContent;
                $letter->hash = $this->hashLetter($letter);
            } else {
                $letter->content_normalized = $this->normalizeText('');
                $letter->hash = $this->hashLetter($letter);
            }
            return $letter;
        });
    }
    
    public function hashLetter($letter)
    {
        $significantFields = $letter->explicit . ' ' . $letter->content . ' ' . $letter->date_computed;
        return hash('sha256', $this->normalizeText($significantFields));
    }
    public function normalizeLetters($letters)
    {
        return $letters->map(function ($letter) {
            if (isset($letter->explicit)) {
                $letter->content_normalized = $this->normalizeText($letter->explicit . ' ' . $letter->content . ' ' . $letter->date_computed);
            } else {
                $letter->content_normalized = $this->normalizeText('');
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
    
    public function findPotentialDuplicates($letters)
    {
        $hashMap = [];
        $duplicates = [];

        foreach ($letters as $letter) {

            if (isset($hashMap[$letter->hash]) && $hashMap[$letter->hash]->id != $letter->id) {
                $duplicates[] = [
                    'letter1' => $hashMap[$letter->hash],
                    'letter2' => $letter,
                    'similarity' => 1.0
                ];
            } else {
                $hashMap[$letter->hash] = $letter;
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
                    'similarity' => $duplicate['similarity']
                ]);
            }
        }
    }

    public function processDuplicates()
    {
        $letters = $this->getAllLetters();
        $normalizedLetters = $this->normalizeLetters($letters);
        $potentialDuplicates = $this->findPotentialDuplicates($normalizedLetters);
        $this->markDuplicates($potentialDuplicates);

        return $potentialDuplicates;
    }
}
