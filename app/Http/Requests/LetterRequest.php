<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Enums\LocationType;
use App\Models\Location;
use App\Helpers\DateHelper;
use App\Helpers\FormRequestHelper;

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
            'date_year'           => ['nullable', 'integer', 'min:1', 'max:9999'],
            'date_month'          => ['nullable', 'integer', 'between:1,12', 'required_with:date_day'],
            'date_day'            => ['nullable', 'integer', 'between:1,31'],
            'date_marked'         => ['nullable', 'string', 'max:255'],

            // Boolean flags for date
            'date_uncertain'      => ['required', 'boolean'],
            'date_approximate'    => ['required', 'boolean'],
            'date_inferred'       => ['required', 'boolean'],
            'date_is_range'       => ['required', 'boolean'],

            // Range date fields
            'range_year' => ['nullable','integer','min:1','max:9999','exclude_unless:date_is_range,1,true,on'],
            'range_month' => ['nullable','integer','between:1,12','required_with:range_day','exclude_unless:date_is_range,1,true,on'],
            'range_day' => ['nullable','integer','between:1,31','exclude_unless:date_is_range,1,true,on'],

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

            // Abstract is stored as JSON by Spatie\Translatable
            // but we treat it here as a simple array
            'abstract'   => ['nullable', 'array'],
            // 'abstract_cs' / 'abstract_en' are combined later, see prepareForValidation.

            // Simple text fields
            'explicit'   => ['nullable', 'string', 'max:255'],
            'incipit'    => ['nullable', 'string', 'max:255'],
            'copyright'  => ['nullable', 'string'],

            // 'languages' is stored as a semicolon-delimited string
            'languages'  => ['nullable', 'string', 'max:255'],

            'notes_private' => ['nullable', 'string'],
            'notes_public'  => ['nullable', 'string'],

            'status'         => ['required', 'string', 'max:255'],
            'approval'       => ['required', 'integer', 'in:1,0'],

            // Pivot fields we do NOT store in letters table, but still validate as arrays
            'authors'      => ['nullable', 'array'],
            'authors.*.value' => ['nullable', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'recipients'   => ['nullable', 'array'],
            'recipients.*.value' => ['nullable', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'mentioned' => ['nullable', 'array'],
            'mentioned.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__identities,id'],
            'destinations' => ['nullable', 'array'],
            'destinations.*.value' => ['nullable', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__places,id'],
            'origins'      => ['nullable', 'array'],
            'origins.*.value' => ['nullable', 'integer', 'exists:' . tenancy()->tenant->table_prefix . '__places,id'],

            // IMPORTANT: keywords => array of integer IDs
            'keywords'     => ['nullable', 'array'],
            'keywords.*'   => ['regex:/^(local|global)-\d+$/'],

            'local_keywords' => ['sometimes', 'array'],
            'local_keywords.*' => ['integer', 'exists:' . tenancy()->tenant->table_prefix . '__keywords,id'],
            'global_keywords' => ['sometimes', 'array'],
            'global_keywords.*' => ['integer', 'exists:global_keywords,id'],
        ];
    }

    /**
     * Provide human-friendly names for each attribute, if needed for error messages.
     */
    public function attributes(): array
    {
        return FormRequestHelper::mapAttributeLabelsFromRules($this->rules());
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
            'mentioned'         => $this->prepareJsonField('mentioned'),
            'destinations'      => $this->prepareJsonField('destinations'),
            'origins'           => $this->prepareJsonField('origins'),

            // Merge abstract_cs / abstract_en into single 'abstract' array
            'abstract' => [
                'cs' => $this->input('abstract_cs'),
                'en' => $this->input('abstract_en'),
            ],
        ]);

        // Normalize date fields: trim strings, convert '0' / 0 / '' to null
        $datePatch = [];
        foreach (['date_year', 'date_month', 'date_day', 'range_year', 'range_month', 'range_day'] as $f) {
            $v = $this->input($f);

            if (is_string($v)) {
                $v = trim($v);
            }

            if ($v === '' || $v === '0' || $v === 0) {
                $v = null;
            }

            if ($v !== $this->input($f)) {
                $datePatch[$f] = $v;
            }
        }

        if ($datePatch) {
            $this->merge($datePatch);
        }

        // If user checked the box but provided no range parts at all, treat it as NOT a range
        $isRange = $this->boolDefault('date_is_range');
        $hasAnyRangePart = $this->filled('range_year') || $this->filled('range_month') || $this->filled('range_day');
        if ($isRange && ! $hasAnyRangePart) {
            $this->merge(['date_is_range' => false]);
        }

        // If date_is_range is false, clear range date fields
        if (!$isRange) {
            $this->merge([
                'range_year'  => null,
                'range_month' => null,
                'range_day'   => null,
            ]);
        }
    }

    /**
     * Custom validation after the standard rules.
     */
    public function withValidator($validator)
    {
        $validator->after(function ($v) {
            $y = $this->input('date_year');
            $m = $this->input('date_month');
            $d = $this->input('date_day');

            // main date existence
            if ($y && $m && $d && !checkdate((int)$m, (int)$d, (int)$y)) {
                $v->errors()->add('date_day', __('validation.date', ['attribute' => FormRequestHelper::attributeLabel('date_day')]));
            }

            // only check range date if date_is_range = true
            if ($this->boolDefault('date_is_range')) {
                $ry = $this->input('range_year');
                $rm = $this->input('range_month');
                $rd = $this->input('range_day');

                // range date existence
                if ($ry && $rm && $rd && !checkdate((int)$rm, (int)$rd, (int)$ry)) {
                    $v->errors()->add('range_day', __('validation.date', ['attribute' => FormRequestHelper::attributeLabel('range_day')]));
                }

                // ensure range end date >= range start date
                $start = DateHelper::deriveStartBoundDate($y, $m, $d);
                $end = DateHelper::deriveEndBoundDate($ry, $rm, $rd);

                if ($start && $end && $end->lt($start)) {
                    $v->errors()->add(
                        'range_day',
                        __('validation.after_or_equal', [
                            'attribute' => FormRequestHelper::attributeLabel('range_day'),
                            'date'      => $start->toDateString(),
                        ])
                    );
                }
            }
        });
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
            $items = $val;
        } elseif (!empty($val)) {
            $items = json_decode($val, true);
        } else {
            return null;
        }

        // Normalize authors, recipients, mentioned
        if (in_array($field, ['authors', 'recipients', 'mentioned']) && is_array($items)) {
            foreach ($items as &$item) {
                if (isset($item['value'])) {
                    // convert "local-7" or "global-13" → "7" / "13"
                    $item['value'] = preg_replace('/\D/', '', $item['value']);
                } else {
                    // convert "local-7" or "global-13" → "7" / "13"
                    $item = preg_replace('/\D/', '', $item);
                }
            }
        }

        return $items;
    }
}
