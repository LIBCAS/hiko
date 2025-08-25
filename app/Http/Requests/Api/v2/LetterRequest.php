<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LocationType;
use App\Models\Letter;
use App\Models\Location;

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
            'date_year'           => ['nullable', 'integer', 'digits_between:1,4'],
            'date_month'          => ['nullable', 'integer', 'between:1,12'],
            'date_day'            => ['nullable', 'integer', 'between:1,31'],
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
            'copies.*.archive'             => ['nullable', 'string', 'max:255', Rule::in(Location::query()->where('type', LocationType::Archive->value)->pluck('name')->toArray())],
            'copies.*.collection'          => ['nullable', 'string', 'max:255', Rule::in(Location::query()->where('type', LocationType::Collection->value)->pluck('name')->toArray())],
            'copies.*.copy'                => ['nullable', 'string', 'max:255'],
            'copies.*.l_number'            => ['nullable', 'string', 'max:255'],
            'copies.*.location_note'       => ['nullable', 'string'],
            'copies.*.manifestation_notes' => ['nullable', 'string'],
            'copies.*.ms_manifestation'    => ['nullable', 'string', 'max:10'],
            'copies.*.preservation'        => ['nullable', 'string', 'max:50'],
            'copies.*.repository'          => ['nullable', 'string', 'max:255', Rule::in(Location::query()->where('type', LocationType::Repository->value)->pluck('name')->toArray())],
            'copies.*.signature'           => ['nullable', 'string', 'max:255'],
            'copies.*.type'                => ['nullable', 'string', 'max:50'],

            // Another JSON array
            'related_resources'            => ['nullable', 'array'],
            'related_resources.*.title'    => ['required', 'string'],
            'related_resources.*.link'     => ['nullable', 'url'],

            'abstract' => ['nullable', 'array'],
            'abstract.cs' => ['nullable', 'string'],
            'abstract.en' => ['nullable', 'string'],

            // Simple text fields
            'explicit'   => ['nullable', 'string', 'max:255'],
            'incipit'    => ['nullable', 'string', 'max:255'],
            'copyright'  => ['nullable', 'string', 'max:255'],

            // 'languages' is stored as a semicolon-delimited string
            'languages'  => ['nullable', 'string', 'max:255'],

            'notes_private' => ['nullable', 'string'],
            'notes_public'  => ['nullable', 'string'],

            'status' => ['sometimes', 'string', Rule::in([Letter::PUBLISHED, Letter::DRAFT])],
            'approval' => ['sometimes', 'integer', Rule::in([Letter::APPROVED, Letter::NOT_APPROVED])],

            // Pivot fields we do NOT store in letters table, but still validate as arrays
            'authors'      => ['nullable', 'array'],
            'authors.*.id' => ['required', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'recipients'   => ['nullable', 'array'],
            'recipients.*.id' => ['required', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'mentioned' => ['nullable', 'array'],
            'mentioned.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'destinations' => ['nullable', 'array'],
            'destinations.*.id' => ['required', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__places,id'],
            'origins'      => ['nullable', 'array'],
            'origins.*.id' => ['required', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__places,id'],

            // IMPORTANT: keywords => array of integer IDs
            'keywords'     => ['nullable', 'array'],
            'keywords.*'   => ['regex:/^(local|global)-\d+$/'],

            'local_keywords' => ['sometimes', 'array'],
            'local_keywords.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__keywords,id'],
            'global_keywords' => ['sometimes', 'array'],
            'global_keywords.*' => ['integer', 'exists:global_keywords,id'],

            // Content fields
            'content' => ['nullable', 'string'],
            'content_stripped' => ['nullable', 'string'],
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

            // Handle `status` and `approval` with default values
            'status' => $this->input('status', Letter::DRAFT),
            'approval' => $this->input('approval', Letter::NOT_APPROVED),

            // Convert array of languages -> semicolon string
            'languages'             => $this->prepareStringField('languages', ';'),

            // Convert 'copies', 'related_resources', 'authors', etc. from JSON or array
            'copies'            => $this->prepareJsonField('copies'),
            'related_resources' => $this->prepareJsonField('related_resources'),
            'authors'           => $this->prepareJsonField('authors'),
            'recipients'        => $this->prepareJsonField('recipients'),
            'destinations'      => $this->prepareJsonField('destinations'),
            'origins'           => $this->prepareJsonField('origins'),

            // Handle abstract
            'abstract' => $this->normalizeAbstract($this->input('abstract')),
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

    /**
     * Normalize the `abstract` field
     */
    private function normalizeAbstract($abstract): array
    {
        return [
            'cs' => $abstract['cs'] ?? null,
            'en' => $abstract['en'] ?? null,
        ];
    }
}
