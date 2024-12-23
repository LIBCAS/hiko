<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterRequest extends FormRequest
{
    public function rules()
    {
        return [
            // General date-related fields
            'date_year' => ['nullable', 'integer', 'numeric'],
            'date_month' => ['nullable', 'integer', 'numeric'],
            'date_day' => ['nullable', 'integer', 'numeric'],
            'date_marked' => ['nullable', 'string', 'max:255'],
            'date_uncertain' => ['nullable', 'boolean'],
            'date_approximate' => ['nullable', 'boolean'],
            'date_inferred' => ['nullable', 'boolean'],
            'date_is_range' => ['nullable', 'boolean'],
            'range_year' => ['nullable', 'integer', 'numeric'],
            'range_month' => ['nullable', 'integer', 'numeric'],
            'range_day' => ['nullable', 'integer', 'numeric'],
            'date_note' => ['nullable', 'string'],

            // Author-related fields
            'author_uncertain' => ['nullable', 'boolean'],
            'author_inferred' => ['nullable', 'boolean'],
            'author_note' => ['nullable', 'string'],

            // Recipient-related fields
            'recipient_uncertain' => ['nullable', 'boolean'],
            'recipient_inferred' => ['nullable', 'boolean'],
            'recipient_note' => ['nullable', 'string'],

            // Destination-related fields
            'destination_uncertain' => ['nullable', 'boolean'],
            'destination_inferred' => ['nullable', 'boolean'],
            'destination_note' => ['nullable', 'string'],

            // Origin-related fields
            'origin_uncertain' => ['nullable', 'boolean'],
            'origin_inferred' => ['nullable', 'boolean'],
            'origin_note' => ['nullable', 'string'],

            // Metadata
            'people_mentioned_note' => ['nullable', 'string'],
            'copies' => ['nullable', 'array'],
            'related_resources' => ['nullable', 'array'],
            'abstract' => ['nullable', 'array'], // Translatable abstract
            'explicit' => ['nullable', 'string', 'max:1024'],
            'incipit' => ['nullable', 'string', 'max:1024'],
            'copyright' => ['nullable', 'string'],
            'languages' => ['nullable', 'string', 'max:1024'],
            'notes_private' => ['nullable', 'string'],
            'notes_public' => ['nullable', 'string'],
            'status' => ['required', 'string', 'max:255'],

            // Relationships (arrays with nested objects)
            'authors' => ['nullable', 'array'],
            'authors.*.value' => ['required', 'integer'], // Author ID
            'authors.*.marked' => ['nullable', 'string'], // Optional "marked" data

            'recipients' => ['nullable', 'array'],
            'recipients.*.value' => ['required', 'integer'], // Recipient ID
            'recipients.*.marked' => ['nullable', 'string'], // Optional "marked" data
            'recipients.*.salutation' => ['nullable', 'string'], // Optional salutation

            'origins' => ['nullable', 'array'],
            'origins.*.value' => ['required', 'integer'], // Origin ID
            'origins.*.marked' => ['nullable', 'string'], // Optional "marked" data

            'destinations' => ['nullable', 'array'],
            'destinations.*.value' => ['required', 'integer'], // Destination ID
            'destinations.*.marked' => ['nullable', 'string'], // Optional "marked" data
        ];
    }

    public function attributes()
    {
        // Automatically generate localized attributes for validation error messages
        $fields = [
            'date_year', 'date_month', 'date_day', 'date_marked', 'date_uncertain',
            'date_approximate', 'date_inferred', 'date_is_range', 'range_year',
            'range_month', 'range_day', 'date_note', 'author_uncertain',
            'author_inferred', 'author_note', 'recipient_uncertain',
            'recipient_inferred', 'recipient_note', 'destination_uncertain',
            'destination_inferred', 'destination_note', 'origin_uncertain',
            'origin_inferred', 'origin_note', 'people_mentioned_note', 'copies',
            'related_resources', 'abstract', 'explicit', 'incipit', 'copyright',
            'languages', 'notes_private', 'notes_public', 'status', 'authors',
            'recipients', 'destinations', 'origins',
        ];

        $result = [];
        foreach ($fields as $field) {
            $result[$field] = __("hiko.{$field}");
        }

        return $result;
    }

    protected function prepareForValidation()
    {
        // Prepare languages and abstract fields for validation
        $this->merge([
            'languages' => empty($this->languages) ? null : implode(';', $this->languages),
            'abstract' => [
                'cs' => $this->abstract_cs ?? null,
                'en' => $this->abstract_en ?? null,
            ],
            'copies' => collect($this->copies ?? [])
                ->reject(function ($copy) {
                    return empty(array_filter(array_values($copy)));
                })
                ->toArray(),
        ]);

        // Convert boolean-like values to integers
        foreach ($this->rules() as $key => $fieldRules) {
            if (in_array('boolean', $fieldRules)) {
                $this->merge([
                    $key => $this->boolean($key),
                ]);
            }
        }
    }

    public function messages()
    {
        return [
            'authors.*.value.required' => __('hiko.authors_value_required'),
            'recipients.*.value.required' => __('hiko.recipients_value_required'),
            'origins.*.value.required' => __('hiko.origins_value_required'),
            'destinations.*.value.required' => __('hiko.destinations_value_required'),
        ];
    }
}
