<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Make sure the user can manage metadata
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function rules(): array
    {
        return [
            // Basic date fields
            'date_year'      => ['nullable', 'integer'],
            'date_month'     => ['nullable', 'integer'],
            'date_day'       => ['nullable', 'integer'],
            'date_marked'    => ['nullable', 'string', 'max:255'],

            // Booleans for date flags
            'date_uncertain'      => ['required', 'boolean'],
            'date_approximate'    => ['required', 'boolean'],
            'date_inferred'       => ['required', 'boolean'],
            'date_is_range'       => ['required', 'boolean'],

            // Range date fields
            'range_year'     => ['nullable', 'integer'],
            'range_month'    => ['nullable', 'integer'],
            'range_day'      => ['nullable', 'integer'],

            'date_note'      => ['nullable', 'string'],

            // Author/recipient/destination/origin flags
            'author_uncertain' => ['required', 'boolean'],
            'author_inferred'  => ['required', 'boolean'],
            'author_note'      => ['nullable', 'string'],

            'recipient_uncertain' => ['required', 'boolean'],
            'recipient_inferred'  => ['required', 'boolean'],
            'recipient_note'      => ['nullable', 'string'],

            'destination_uncertain' => ['required', 'boolean'],
            'destination_inferred'  => ['required', 'boolean'],
            'destination_note'      => ['nullable', 'string'],

            'origin_uncertain' => ['required', 'boolean'],
            'origin_inferred'  => ['required', 'boolean'],
            'origin_note'      => ['nullable', 'string'],

            'people_mentioned_note' => ['nullable', 'string'],

            // JSON array fields in letters table
            'copies' => ['nullable', 'array'],
            // Detailed rules for copies.* subfields
            'copies.*.archive'             => ['nullable', 'string', 'max:255'],
            'copies.*.collection'          => ['nullable', 'string', 'max:255'],
            'copies.*.copy'                => ['nullable', 'string', 'max:255'],
            'copies.*.l_number'            => ['nullable', 'string', 'max:255'],
            'copies.*.location_note'       => ['nullable', 'string'],
            'copies.*.manifestation_notes' => ['nullable', 'string'],
            'copies.*.ms_manifestation'    => ['nullable', 'string', 'max:10'],
            'copies.*.preservation'        => ['nullable', 'string', 'max:50'],
            'copies.*.repository'          => ['nullable', 'string', 'max:255'],
            'copies.*.signature'           => ['nullable', 'string', 'max:255'],
            'copies.*.type'                => ['nullable', 'string', 'max:50'],

            'related_resources' => ['nullable', 'array'],
            'related_resources.*.title' => ['required', 'string', 'max:255'],
            'related_resources.*.link'  => ['nullable', 'url'],

            // Abstract is an array (like {cs:'...', en:'...'})
            'abstract' => ['nullable', 'array'],

            // Simple text fields
            'explicit'  => ['nullable', 'string', 'max:255'],
            'incipit'   => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],

            // semicolon-delimited languages
            'languages' => ['nullable', 'string', 'max:255'],

            'notes_private' => ['nullable', 'string'],
            'notes_public'  => ['nullable', 'string'],

            'status' => ['required', 'string', 'max:255'],

            'approval' => ['required', 'integer', 'in:1,0'],

            // Pivot data arrays (not stored in letters table directly)
            'authors'      => ['nullable', 'array'],
            'recipients'   => ['nullable', 'array'],
            'destinations' => ['nullable', 'array'],
            'origins'      => ['nullable', 'array'],
        ];
    }

    public function attributes(): array
    {
        // Map each field to a localized label if needed
        return collect($this->rules())
            ->keys()
            ->mapWithKeys(fn($field) => [$field => __("hiko.{$field}")])
            ->toArray();
    }

    /**
     * Prepare/transform the input before validation. 
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            // Convert booleans
            'date_uncertain'       => $this->boolDefault('date_uncertain'),
            'date_approximate'     => $this->boolDefault('date_approximate'),
            'date_inferred'        => $this->boolDefault('date_inferred'),
            'date_is_range'        => $this->boolDefault('date_is_range'),
            'author_uncertain'     => $this->boolDefault('author_uncertain'),
            'author_inferred'      => $this->boolDefault('author_inferred'),
            'recipient_uncertain'  => $this->boolDefault('recipient_uncertain'),
            'recipient_inferred'   => $this->boolDefault('recipient_inferred'),
            'destination_uncertain'=> $this->boolDefault('destination_uncertain'),
            'destination_inferred' => $this->boolDefault('destination_inferred'),
            'origin_uncertain'     => $this->boolDefault('origin_uncertain'),
            'origin_inferred'      => $this->boolDefault('origin_inferred'),

            // Convert array of languages -> semicolon string
            'languages' => $this->prepareStringField('languages', ';'),

            // Convert some fields from JSON or string to array
            'copies'            => $this->prepareJsonField('copies'),
            'related_resources' => $this->prepareJsonField('related_resources'),

            // Convert pivot data authors/recipients/origins/destinations if needed
            'authors'      => $this->prepareJsonField('authors'),
            'recipients'   => $this->prepareJsonField('recipients'),
            'destinations' => $this->prepareJsonField('destinations'),
            'origins'      => $this->prepareJsonField('origins'),

            // Merge abstract_cs & abstract_en into "abstract"
            'abstract' => [
                'cs' => $this->input('abstract_cs'),
                'en' => $this->input('abstract_en'),
            ],
        ]);
    }

    /**
     * Convert input to boolean (default false if missing).
     */
    private function boolDefault(string $field, bool $default = false): bool
    {
        $val = $this->input($field);
        return is_null($val) ? $default : filter_var($val, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Convert an array field to a semicolon-delimited string
     */
    private function prepareStringField(string $field, string $delimiter): ?string
    {
        $val = $this->input($field);
        return empty($val) ? null : implode($delimiter, (array)$val);
    }

    /**
     * If the field is an array, return it as is.
     * If it's a JSON string, decode it. Otherwise null.
     */
    private function prepareJsonField(string $field): ?array
    {
        $val = $this->input($field);
        if (is_array($val)) {
            return $val;
        }
        return empty($val) ? null : json_decode($val, true);
    }
}
