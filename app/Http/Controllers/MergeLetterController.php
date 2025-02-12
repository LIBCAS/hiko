<?php

namespace App\Http\Controllers;

use App\Models\Letter;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class MergeLetterController extends Controller
{
    /**
     * Display the merge letters form with paginated letters.
     */
    public function mergeForm()
    {
        $tenantTable = tenancy()->tenant->table_prefix . '__letters';

        $letters = Letter::query()->from($tenantTable)
            ->orderBy('id', 'asc')
            ->paginate(10);

        return view('pages.letters.merge', [
            'letters'    => $letters,
            'pagination' => $letters,
            'title'      => __('hiko.merge_letters'),
        ]);
    }

    /**
     * Process the merge of exactly two selected letters.
     */
    public function merge(Request $request)
    {
        $tenantTable = tenancy()->tenant->table_prefix . '__letters';

        $validated = $request->validate([
            'primary_id'   => "required|integer|exists:{$tenantTable},id",
            'secondary_id' => "required|integer|exists:{$tenantTable},id",
        ]);

        if ($validated['primary_id'] == $validated['secondary_id']) {
            return redirect()->back()->withErrors([
                'secondary_id' => __('hiko.merge_same_not_allowed'),
            ]);
        }

        DB::transaction(function () use ($validated, $tenantTable) {
            $primary = Letter::query()->from($tenantTable)->findOrFail($validated['primary_id']);
            $secondary = Letter::query()->from($tenantTable)->findOrFail($validated['secondary_id']);

            // Merge scalar fields.
            $fieldsToMerge = [
                'date_year', 'date_month', 'date_day', 
                'date_marked', 'date_note',
                'date_uncertain', 'date_approximate', 'date_inferred', 'date_is_range',
                'range_year', 'range_month', 'range_day',
                'author_inferred', 'author_uncertain', 'author_note',
                'recipient_inferred', 'recipient_uncertain', 'recipient_note',
                'destination_inferred', 'destination_uncertain', 'destination_note',
                'origin_inferred', 'origin_uncertain', 'origin_note',
                'people_mentioned_note',
                'explicit', 'incipit', 'copyright',
                'notes_private', 'notes_public',
                'content'
            ];

            foreach ($fieldsToMerge as $field) {
                $oldPrimary = $primary->{$field};
                $oldSecondary = $secondary->{$field};
                $primary->{$field} = $this->mergeScalarField($primary->{$field}, $secondary->{$field});
                Log::info("Field '{$field}' merged: primary was [{$oldPrimary}], secondary was [{$oldSecondary}], result is [{$primary->{$field}}]");
            }

            // Merge language field.
            $primaryLangs = $primary->languages ? array_map('trim', explode(';', $primary->languages)) : [];
            $secondaryLangs = $secondary->languages ? array_map('trim', explode(';', $secondary->languages)) : [];
            $mergedLangs = array_unique(array_filter(array_merge($primaryLangs, $secondaryLangs)));
            $primary->languages = implode('; ', $mergedLangs);
            Log::info("Languages merged: " . $primary->languages);

            // Merge JSON fields.
            $jsonFields = ['copies', 'related_resources', 'abstract'];
            foreach ($jsonFields as $field) {
                $oldPrimaryJson = json_encode($primary->{$field});
                $oldSecondaryJson = json_encode($secondary->{$field});
                $primary->{$field} = $this->mergeJsonField($primary->{$field}, $secondary->{$field});
                Log::info("JSON field '{$field}' merged: primary was [{$oldPrimaryJson}], secondary was [{$oldSecondaryJson}], result is [" . json_encode($primary->{$field}) . "]");
            }

            // Transfer media (and any other relationships as needed).
            foreach ($secondary->media as $media) {
                if (!$primary->media()->where('file_name', $media->file_name)->exists()) {
                    $media->model_id = $primary->id;
                    $media->save();
                }
            }
            Log::info("Merged letter ID {$secondary->id} into letter ID {$primary->id}");

            // Delete the secondary letter.
            $secondary->delete();

            $primary->save();
            Log::info("Final state of primary letter (ID {$primary->id}): " . json_encode($primary->getAttributes()));
        });

        return redirect()->route('letters.merge.form')
                         ->with('success', __('hiko.merged'));
    }

    /**
     * Merge a scalar field.
     * For numeric fields, return primary if nonzero; for text fields, if primary is empty, use secondary,
     * otherwise append if the secondary is not already contained.
     */
    private function mergeScalarField($primaryValue, $secondaryValue, $delimiter = '; ')
    {
        if (is_numeric($primaryValue) && is_numeric($secondaryValue)) {
            return $primaryValue ? $primaryValue : $secondaryValue;
        }
        if (empty($primaryValue) && !empty($secondaryValue)) {
            return $secondaryValue;
        } elseif (!empty($primaryValue) && !empty($secondaryValue)) {
            if (stripos($primaryValue, $secondaryValue) === false) {
                return $primaryValue . $delimiter . $secondaryValue;
            }
        }
        return $primaryValue;
    }

    /**
     * Merge JSON fields (assumed to be arrays).
     */
    private function mergeJsonField($primaryData, $secondaryData)
    {
        $primaryArray = is_array($primaryData) ? $primaryData : [];
        $secondaryArray = is_array($secondaryData) ? $secondaryData : [];
        if (empty($primaryArray)) {
            return $secondaryArray;
        }
        if (empty($secondaryArray)) {
            return $primaryArray;
        }
        $merged = array_merge($primaryArray, $secondaryArray);
        $unique = array_map('unserialize', array_unique(array_map('serialize', $merged)));
        return $unique;
    }
}
