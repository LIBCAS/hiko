<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LetterRequest extends FormRequest
{
    public function authorize()
    {
        return auth()->user()->hasAbility('manage-metadata');
    }

    public function rules()
    {
        return [
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
            'date_note' => ['nullable'],
            'author_uncertain' => ['nullable', 'boolean'],
            'author_inferred' => ['nullable', 'boolean'],
            'author_note' => ['nullable'],
            'recipient_uncertain' => ['nullable', 'boolean'],
            'recipient_inferred' => ['nullable', 'boolean'],
            'recipient_note' => ['nullable'],
            'destination_uncertain' => ['nullable', 'boolean'],
            'destination_inferred' => ['nullable', 'boolean'],
            'destination_note' => ['nullable'],
            'origin_uncertain' => ['nullable', 'boolean'],
            'origin_inferred' => ['nullable', 'boolean'],
            'origin_note' => ['nullable'],
            'people_mentioned_note' => ['nullable'],
            'copies' => ['nullable'],
            'related_resources' => ['nullable'],
            'abstract' => ['nullable'],
            'explicit' => ['nullable', 'string', 'max:255'],
            'incipit' => ['nullable', 'string', 'max:255'],
            'copyright' => ['nullable', 'string', 'max:255'],
            'languages' => ['nullable', 'string', 'max:255'],
            'notes_private' => ['nullable'],
            'notes_public' => ['nullable'],
            'status' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes()
    {
        $fields = [
            'date_year',
            'date_month',
            'date_day',
            'date_marked',
            'date_uncertain',
            'date_approximate',
            'date_inferred',
            'date_is_range',
            'range_year',
            'range_month',
            'range_day',
            'date_note',
            'author_uncertain',
            'author_inferred',
            'author_note',
            'recipient_uncertain',
            'recipient_inferred',
            'recipient_note',
            'destination_uncertain',
            'destination_inferred',
            'destination_note',
            'origin_uncertain',
            'origin_inferred',
            'origin_note',
            'people_mentioned_note',
            'copies',
            'related_resources',
            'abstract',
            'explicit',
            'incipit',
            'copyright',
            'languages',
            'notes_private',
            'notes_public',
            'status',
        ];

        $result = [];

        foreach ($fields as $field) {
            $result[$field] = "'" . __("hiko.{$field}") . "'";
        }

        return $result;
    }

    protected function prepareForValidation()
    {
        $this->merge([
            'languages' => empty($this->request->get('languages')) ? null : implode(';', $this->request->get('languages')),
            'copies' => empty($this->request->get('copies')) ? null : json_decode($this->request->get('copies'), true),
            'authors' => empty($this->request->get('authors')) ? null : json_decode($this->request->get('authors'), true),
            'recipients' => empty($this->request->get('recipients')) ? null : json_decode($this->request->get('recipients'), true),
            'destinations' => empty($this->request->get('destinations')) ? null : json_decode($this->request->get('destinations'), true),
            'origins' => empty($this->request->get('origins')) ? null : json_decode($this->request->get('origins'), true),
            'abstract' => [
                'cs' => $this->request->get('abstract_cs'),
                'en' => $this->request->get('abstract_en'),
            ],
        ]);

        foreach ($this->rules() as $key => $fieldRules) {
            if (in_array('boolean', $fieldRules)) {
                $this->merge([
                    $key => $this->request->get($key) ? 1 : 0,
                ]);
            }
        }
    }
}
