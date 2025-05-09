<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

/**
 * LetterRequest handles validation rules for creating/updating letters.
 * This includes JSON fields like 'copies' and pivot arrays like 'authors' and 'keywords'.
 */
class LetterRequest extends FormRequest
{
    /**
     * Ensure the user is authorized to manage metadata.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    /**
     * The validation rules for incoming letter data.
     */
    public function rules(): array
    {
        return [
            // Basic date fields
            'date_year'           => ['nullable', 'integer'],
            'date_month'          => ['nullable', 'integer'],
            'date_day'            => ['nullable', 'integer'],
            'date_marked'         => ['nullable', 'string', 'max:255'],

            // Boolean flags for date
            'date_uncertain'      => ['required', 'boolean'],
            'date_approximate'    => ['required', 'boolean'],
            'date_inferred'       => ['required', 'boolean'],
            'date_is_range'       => ['required', 'boolean'],

            // Range date fields
            'range_year'          => ['nullable', 'integer'],
            'range_month'         => ['nullable', 'integer'],
            'range_day'           => ['nullable', 'integer'],

            'date_note'           => ['nullable', 'string'],

            // Author flags & note
            'author_uncertain'    => ['required', 'boolean'],
            'author_inferred'     => ['required', 'boolean'],
            'author_note'         => ['nullable', 'string'],

            // Recipient flags & note
            'recipient_uncertain' => ['required', 'boolean'],
            'recipient_inferred'  => ['required', 'boolean'],
            'recipient_note'      => ['nullable', 'string'],

            // Destination flags & note
            'destination_uncertain' => ['required', 'boolean'],
            'destination_inferred'  => ['required', 'boolean'],
            'destination_note'      => ['nullable', 'string'],

            // Origin flags & note
            'origin_uncertain'    => ['required', 'boolean'],
            'origin_inferred'     => ['required', 'boolean'],
            'origin_note'         => ['nullable', 'string'],

            // People mentioned note
            'people_mentioned_note' => ['nullable', 'string'],

            // JSON array fields
            'copies' => ['nullable', 'array'],
            // Subfields for copies
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

            // Another JSON array
            'related_resources'            => ['nullable', 'array'],
            'related_resources.*.title'    => ['required', 'string'],
            'related_resources.*.link'     => ['nullable', 'url'],

            // Abstract is stored as JSON by Spatie\Translatable
            // but we treat it here as a simple array
            'abstract'   => ['nullable', 'array'],
            // 'abstract_cs' / 'abstract_en' are combined later, see prepareForValidation.

            // Simple text fields
            'explicit'   => ['nullable', 'string', 'max:255'],
            'incipit'    => ['nullable', 'string', 'max:255'],
            'copyright'  => ['nullable', 'string', 'max:255'],

            // 'languages' is stored as a semicolon-delimited string
            'languages'  => ['nullable', 'string', 'max:255'],

            'notes_private' => ['nullable', 'string'],
            'notes_public'  => ['nullable', 'string'],

            'status'         => ['required', 'string', 'max:255'],
            'approval'       => ['required', 'integer', 'in:1,0'],

            // Pivot fields we do NOT store in letters table, but still validate as arrays
            'authors'      => ['nullable', 'array'],
            'recipients'   => ['nullable', 'array'],
            'destinations' => ['nullable', 'array'],
            'origins'      => ['nullable', 'array'],

            // IMPORTANT: keywords => array of integer IDs
            'keywords'     => ['nullable', 'array'],
            'keywords.*'   => ['regex:/^(local|global)-\d+$/'],
        ];
    }

    /**
     * Provide human-friendly names for each attribute, if needed for error messages.
     */
    public function attributes(): array
    {
        return collect($this->rules())
            ->keys()
            ->mapWithKeys(fn($field) => [$field => __("hiko.{$field}")])
            ->toArray();
    }

    /**
     * Prepare/transform input before validation: convert booleans, decode JSON, etc.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_uncertain'         => $this->boolDefault('date_uncertain'),
            'date_approximate'       => $this->boolDefault('date_approximate'),
            'date_inferred'          => $this->boolDefault('date_inferred'),
            'date_is_range'          => $this->boolDefault('date_is_range'),
            'author_uncertain'       => $this->boolDefault('author_uncertain'),
            'author_inferred'        => $this->boolDefault('author_inferred'),
            'recipient_uncertain'    => $this->boolDefault('recipient_uncertain'),
            'recipient_inferred'     => $this->boolDefault('recipient_inferred'),
            'destination_uncertain'  => $this->boolDefault('destination_uncertain'),
            'destination_inferred'   => $this->boolDefault('destination_inferred'),
            'origin_uncertain'       => $this->boolDefault('origin_uncertain'),
            'origin_inferred'        => $this->boolDefault('origin_inferred'),

            // Convert array of languages -> semicolon string
            'languages'             => $this->prepareStringField('languages', ';'),

            // Convert 'copies', 'related_resources', 'authors', etc. from JSON or array
            'copies'            => $this->prepareJsonField('copies'),
            'related_resources' => $this->prepareJsonField('related_resources'),
            'authors'           => $this->prepareJsonField('authors'),
            'recipients'        => $this->prepareJsonField('recipients'),
            'destinations'      => $this->prepareJsonField('destinations'),
            'origins'           => $this->prepareJsonField('origins'),

            // Merge abstract_cs / abstract_en into single 'abstract' array
            'abstract' => [
                'cs' => $this->input('abstract_cs'),
                'en' => $this->input('abstract_en'),
            ],
        ]);
    }

    /**
     * Convert a checkbox-like field to boolean with a default of false if missing.
     */
    private function boolDefault(string $field, bool $default = false): bool
    {
        $value = $this->input($field);
        return is_null($value) ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Convert an array field to a semicolon-delimited string. If empty, return null.
     */
    private function prepareStringField(string $field, string $delimiter): ?string
    {
        $val = $this->input($field);
        return empty($val) ? null : implode($delimiter, (array)$val);
    }

    /**
     * If input is an array, return it.
     * If it's a JSON string, decode. Otherwise return null if empty.
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
