<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    /**
     * Define validation rules for the request.
     */
    public function rules(): array
    {
        return [
            'date_year' => ['nullable', 'integer'],
            'date_month' => ['nullable', 'integer'],
            'date_day' => ['nullable', 'integer'],
            'date_marked' => ['nullable', 'string', 'max:255'],
            'date_uncertain' => ['required', 'boolean'],
            'date_approximate' => ['required', 'boolean'],
            'date_inferred' => ['required', 'boolean'],
            'date_is_range' => ['required', 'boolean'],
            'range_year' => ['nullable', 'integer'],
            'range_month' => ['nullable', 'integer'],
            'range_day' => ['nullable', 'integer'],
            'date_note' => ['nullable', 'string'],
            'author_uncertain' => ['required', 'boolean'],
            'author_inferred' => ['required', 'boolean'],
            'author_note' => ['nullable', 'string'],
            'recipient_uncertain' => ['required', 'boolean'],
            'recipient_inferred' => ['required', 'boolean'],
            'recipient_note' => ['nullable', 'string'],
            'destination_uncertain' => ['required', 'boolean'],
            'destination_inferred' => ['required', 'boolean'],
            'destination_note' => ['nullable', 'string'],
            'origin_uncertain' => ['required', 'boolean'],
            'origin_inferred' => ['required', 'boolean'],
            'origin_note' => ['nullable', 'string'],
            'people_mentioned_note' => ['nullable', 'string'],
            'copies' => ['nullable', 'array'],
            'related_resources' => ['nullable', 'array'],
            'abstract' => ['nullable', 'array'],
            'explicit' => ['nullable', 'string', 'max:255'],
            'incipit' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'string', 'max:255'],
            'notes_private' => ['nullable', 'string'],
            'notes_public' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:255'],
            'approval' => ['required', 'integer', 'in:1,0'],
        ];
    }

    /**
     * Define human-readable attribute names.
     */
    public function attributes(): array
    {
        return collect($this->rules())
            ->keys()
            ->mapWithKeys(fn($field) => [$field => __("hiko.{$field}")])
            ->toArray();
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        $this->merge([
            'date_uncertain' => $this->convertToBooleanWithDefault('date_uncertain', false),
            'date_approximate' => $this->convertToBooleanWithDefault('date_approximate', false),
            'date_inferred' => $this->convertToBooleanWithDefault('date_inferred', false),
            'date_is_range' => $this->convertToBooleanWithDefault('date_is_range', false),
            'author_uncertain' => $this->convertToBooleanWithDefault('author_uncertain', false),
            'author_inferred' => $this->convertToBooleanWithDefault('author_inferred', false),
            'recipient_uncertain' => $this->convertToBooleanWithDefault('recipient_uncertain', false),
            'recipient_inferred' => $this->convertToBooleanWithDefault('recipient_inferred', false),
            'destination_uncertain' => $this->convertToBooleanWithDefault('destination_uncertain', false),
            'destination_inferred' => $this->convertToBooleanWithDefault('destination_inferred', false),
            'origin_uncertain' => $this->convertToBooleanWithDefault('origin_uncertain', false),
            'origin_inferred' => $this->convertToBooleanWithDefault('origin_inferred', false),
            'languages' => $this->prepareStringField('languages', ';'),
            'related_resources' => $this->prepareJsonField('related_resources'),
            'copies' => $this->prepareJsonField('copies'),
            'authors' => $this->prepareJsonField('authors'),
            'recipients' => $this->prepareJsonField('recipients'),
            'destinations' => $this->prepareJsonField('destinations'),
            'origins' => $this->prepareJsonField('origins'),
            'abstract' => $this->prepareAbstract(),
        ]);
    }

    /**
     * Convert a field to a boolean value with a default.
     */
    private function convertToBooleanWithDefault(string $field, bool $default): bool
    {
        $value = $this->input($field);
        return is_null($value) ? $default : filter_var($value, FILTER_VALIDATE_BOOLEAN);
    }

    /**
     * Prepare a string field by joining an array into a delimited string.
     */
    private function prepareStringField(string $field, string $delimiter): ?string
    {
        $value = $this->input($field);
        return empty($value) ? null : implode($delimiter, (array)$value);
    }

    /**
     * Prepare a field that may be JSON or an array.
     */
    private function prepareJsonField(string $field): ?array
    {
        $value = $this->input($field);

        if (is_array($value)) {
            return $value;
        }

        return empty($value) ? null : json_decode($value, true);
    }

    /**
     * Prepare the abstract field with translations.
     */
    private function prepareAbstract(): array
    {
        return [
            'cs' => $this->input('abstract_cs'),
            'en' => $this->input('abstract_en'),
        ];
    }
}
