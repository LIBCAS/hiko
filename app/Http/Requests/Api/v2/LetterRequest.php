<?php

namespace App\Http\Requests\Api\v2;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Enums\LocationType;
use App\Models\Letter;
use App\Models\Location;
use App\Helpers\DateHelper;
use App\Helpers\FormRequestHelper;
use App\Traits\GeneratesLetterAttributes;
use Illuminate\Support\Facades\DB;
use OpenApi\Attributes as OA;

/**
 * LetterRequest handles validation rules for creating/updating letters.
 * This includes JSON fields like 'copies' and pivot arrays like 'authors' and 'keywords'.
 */
#[OA\Schema(
    schema: "LetterUpsertRequest",
    required: [
        "date_uncertain",
        "date_approximate",
        "date_inferred",
        "date_is_range",
        "author_uncertain",
        "author_inferred",
        "recipient_uncertain",
        "recipient_inferred",
        "destination_uncertain",
        "destination_inferred",
        "origin_uncertain",
        "origin_inferred"
    ],
    properties: [
        new OA\Property(property: "date_year", type: "integer", nullable: true, example: 1933),
        new OA\Property(property: "date_month", type: "integer", nullable: true, example: 9),
        new OA\Property(property: "date_day", type: "integer", nullable: true, example: 13),
        new OA\Property(property: "date_marked", type: "string", nullable: true, example: "13.09.1933"),
        new OA\Property(property: "date_uncertain", type: "boolean", example: false),
        new OA\Property(property: "date_approximate", type: "boolean", example: true),
        new OA\Property(property: "date_inferred", type: "boolean", example: false),
        new OA\Property(property: "date_is_range", type: "boolean", example: true),
        new OA\Property(property: "range_year", type: "integer", nullable: true, example: 1939),
        new OA\Property(property: "range_month", type: "integer", nullable: true, example: 3),
        new OA\Property(property: "range_day", type: "integer", nullable: true, example: 21),
        new OA\Property(property: "date_note", type: "string", nullable: true),

        new OA\Property(property: "author_uncertain", type: "boolean"),
        new OA\Property(property: "author_inferred", type: "boolean"),
        new OA\Property(property: "author_note", type: "string", nullable: true),
        new OA\Property(property: "recipient_uncertain", type: "boolean"),
        new OA\Property(property: "recipient_inferred", type: "boolean"),
        new OA\Property(property: "recipient_note", type: "string", nullable: true),
        new OA\Property(property: "destination_uncertain", type: "boolean"),
        new OA\Property(property: "destination_inferred", type: "boolean"),
        new OA\Property(property: "destination_note", type: "string", nullable: true),
        new OA\Property(property: "origin_uncertain", type: "boolean"),
        new OA\Property(property: "origin_inferred", type: "boolean"),
        new OA\Property(property: "origin_note", type: "string", nullable: true),
        new OA\Property(property: "people_mentioned_note", type: "string", nullable: true),
        new OA\Property(
            property: "client_meta",
            type: "object",
            nullable: true,
            additionalProperties: new OA\AdditionalProperties(type: "string"),
            example: ["external_id" => "client-letter-2457", "sync_source" => "partner-app"]
        ),

        new OA\Property(
            property: "authors",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 2483),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-2483", description: "Read-only response field. Write requests use id + scope."),
                    new OA\Property(property: "marked", type: "string", nullable: true, example: "Author api-v2-live-20260302111619-ac68433c"),
                ]
            )
        ),
        new OA\Property(
            property: "recipients",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 18),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "global"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-18", description: "Read-only response field. Write requests use id + scope."),
                    new OA\Property(property: "marked", type: "string", nullable: true, example: "Global recipient api-v2-live-20260302111619-ac68433c"),
                    new OA\Property(property: "salutation", type: "string", nullable: true, example: "Dear global recipient"),
                ]
            )
        ),
        new OA\Property(
            property: "mentioned",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 2484),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-2484", description: "Read-only response field. Write requests use id + scope."),
                ]
            )
        ),

        new OA\Property(
            property: "origins",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-3", description: "Read-only response field. Write requests use id + scope."),
                    new OA\Property(property: "marked", type: "string", nullable: true),
                ]
            )
        ),
        new OA\Property(
            property: "destinations",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer"),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "global"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "global-9", description: "Read-only response field. Write requests use id + scope."),
                    new OA\Property(property: "marked", type: "string", nullable: true),
                ]
            )
        ),

        new OA\Property(
            property: "keywords",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "id", type: "integer", example: 74),
                    new OA\Property(property: "scope", type: "string", enum: ["local", "global"], example: "local"),
                    new OA\Property(property: "reference", type: "string", readOnly: true, example: "local-74", description: "Read-only response field. Write requests use id + scope."),
                ]
            )
        ),

        new OA\Property(
            property: "abstract",
            type: "object",
            nullable: true,
            properties: [
                new OA\Property(property: "cs", type: "string", nullable: true),
                new OA\Property(property: "en", type: "string", nullable: true),
            ]
        ),
        new OA\Property(property: "languages", type: "string", nullable: true, example: "Arabic;Azerbaijani"),
        new OA\Property(property: "incipit", type: "string", nullable: true, example: "Test incipit"),
        new OA\Property(property: "explicit", type: "string", nullable: true, example: "Test explicit"),
        new OA\Property(property: "notes_private", type: "string", nullable: true, example: "Private note"),
        new OA\Property(property: "notes_public", type: "string", nullable: true, example: "Public note"),
        new OA\Property(property: "copyright", type: "string", nullable: true, example: "Copyright test value"),
        new OA\Property(property: "content", type: "string", nullable: true, example: "<p>Test content api-v2-live-20260302111619-ac68433c</p>"),
        new OA\Property(property: "status", type: "string", enum: ["publish", "draft"], example: "draft"),
        new OA\Property(property: "approval", type: "integer", enum: [0, 1], example: 0),

        new OA\Property(
            property: "related_resources",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "title", type: "string"),
                    new OA\Property(property: "link", type: "string", format: "uri", nullable: true),
                ]
            )
        ),
        new OA\Property(
            property: "copies",
            type: "array",
            items: new OA\Items(
                type: "object",
                properties: [
                    new OA\Property(property: "repository", type: "string", nullable: true, example: "local-25"),
                    new OA\Property(property: "archive", type: "string", nullable: true, example: "global-9"),
                    new OA\Property(property: "collection", type: "string", nullable: true, example: "local-26"),
                    new OA\Property(property: "copy", type: "string", nullable: true, example: "handwritten"),
                    new OA\Property(property: "l_number", type: "string", nullable: true, example: "L-api-v2-live-20260302111619-ac68433c"),
                    new OA\Property(property: "location_note", type: "string", nullable: true, example: "Location note"),
                    new OA\Property(property: "manifestation_notes", type: "string", nullable: true, example: "Manifestation note"),
                    new OA\Property(property: "ms_manifestation", type: "string", nullable: true),
                    new OA\Property(property: "preservation", type: "string", nullable: true, example: "original"),
                    new OA\Property(property: "signature", type: "string", nullable: true, example: "SIG-api-v2-live-20260302111619-ac68433c"),
                    new OA\Property(property: "type", type: "string", nullable: true, example: "letter"),
                ]
            )
        ),
    ]
)]
class LetterRequest extends FormRequest
{
    use InteractsWithApiV2;
    use GeneratesLetterAttributes;

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
        $requiredBoolean = $this->isMethod('POST') ? ['required', 'boolean'] : ['sometimes', 'boolean'];

        return [
            // Basic date fields
            'date_year'           => ['nullable', 'integer', 'min:1', 'max:9999'],
            'date_month'          => ['nullable', 'integer', 'between:1,12', 'required_with:date_day'],
            'date_day'            => ['nullable', 'integer', 'between:1,31'],
            'date_marked'         => ['nullable', 'string', 'max:255'],

            // Boolean flags for date
            'date_uncertain'      => $requiredBoolean,
            'date_approximate'    => $requiredBoolean,
            'date_inferred'       => $requiredBoolean,
            'date_is_range'       => $requiredBoolean,

            // Range date fields
            'range_year' => ['nullable','integer','min:1','max:9999','exclude_unless:date_is_range,1,true,on'],
            'range_month' => ['nullable','integer','between:1,12','required_with:range_day','exclude_unless:date_is_range,1,true,on'],
            'range_day' => ['nullable','integer','between:1,31','exclude_unless:date_is_range,1,true,on'],

            'date_note'           => ['nullable', 'string'],

            // Author flags & note
            'author_uncertain'    => $requiredBoolean,
            'author_inferred'     => $requiredBoolean,
            'author_note'         => ['nullable', 'string'],

            // Recipient flags & note
            'recipient_uncertain' => $requiredBoolean,
            'recipient_inferred'  => $requiredBoolean,
            'recipient_note'      => ['nullable', 'string'],

            // Destination flags & note
            'destination_uncertain' => $requiredBoolean,
            'destination_inferred'  => $requiredBoolean,
            'destination_note'      => ['nullable', 'string'],

            // Origin flags & note
            'origin_uncertain'    => $requiredBoolean,
            'origin_inferred'     => $requiredBoolean,
            'origin_note'         => ['nullable', 'string'],

            // People mentioned note
            'people_mentioned_note' => ['nullable', 'string'],
            'client_meta' => ['nullable', 'array'],

            // JSON array fields
            'copies' => ['nullable', 'array'],
            // Subfields for copies
            'copies.*.repository' => ['nullable', $this->getLocationValidationRule('repository')],
            'copies.*.archive'    => ['nullable', $this->getLocationValidationRule('archive')],
            'copies.*.collection' => ['nullable', $this->getLocationValidationRule('collection')],
            'copies.*.copy'                => ['nullable', 'string', 'max:255'],
            'copies.*.l_number'            => ['nullable', 'string', 'max:255'],
            'copies.*.location_note'       => ['nullable', 'string'],
            'copies.*.manifestation_notes' => ['nullable', 'string'],
            'copies.*.ms_manifestation'    => ['nullable', 'string', 'max:10'],
            'copies.*.preservation'        => ['nullable', 'string', 'max:50'],
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
            'copyright'  => ['nullable', 'string'],

            // 'languages' is stored as a semicolon-delimited string
            'languages'  => ['nullable', 'string', 'max:255'],

            'notes_private' => ['nullable', 'string'],
            'notes_public'  => ['nullable', 'string'],

            'status' => ['sometimes', 'string', Rule::in([Letter::PUBLISHED, Letter::DRAFT])],
            'approval' => ['sometimes', 'integer', Rule::in([Letter::APPROVED, Letter::NOT_APPROVED])],

            // Pivot fields we do NOT store in letters table, but still validate as arrays
            'authors'      => ['nullable', 'array'],
            'authors.*.id' => ['required', $this->getIdentityValidationRule()],
            'recipients'   => ['nullable', 'array'],
            'recipients.*.id' => ['required', $this->getIdentityValidationRule()],
            'mentioned' => ['nullable', 'array'],
            'mentioned.*.id' => ['required', $this->getIdentityValidationRule()],

            // Places
            'origins' => ['sometimes', 'array'],
            'origins.*.id' => ['required', $this->getPlaceValidationRule()],
            'destinations' => ['sometimes', 'array'],
            'destinations.*.id' => ['required', $this->getPlaceValidationRule()],

            // Keywords
            'keywords'     => ['nullable', 'array'],
            'keywords.*.id'   => ['required', $this->getKeywordValidationRule()],

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
        return $this->generateLetterAttributes($this->all(), $this->rules());
    }

    /**
     * Prepare/transform input before validation: convert booleans, decode JSON, etc.
     */
    protected function prepareForValidation(): void
    {
        $payload = [];

        foreach ([
            'date_uncertain',
            'date_approximate',
            'date_inferred',
            'date_is_range',
            'author_uncertain',
            'author_inferred',
            'recipient_uncertain',
            'recipient_inferred',
            'destination_uncertain',
            'destination_inferred',
            'origin_uncertain',
            'origin_inferred',
        ] as $field) {
            if ($this->isMethod('POST') || $this->exists($field)) {
                $payload[$field] = $this->boolDefault($field);
            }
        }

        if ($this->isMethod('POST') || $this->exists('status')) {
            $payload['status'] = $this->input('status', Letter::DRAFT);
        }

        if ($this->isMethod('POST') || $this->exists('approval')) {
            $payload['approval'] = $this->input('approval', Letter::NOT_APPROVED);
        }

        if ($this->exists('languages')) {
            $payload['languages'] = $this->prepareStringField('languages', ';');
        }

        if ($this->exists('copies')) {
            $payload['copies'] = $this->normalizeCopies($this->prepareJsonField('copies'));
        }

        if ($this->exists('related_resources')) {
            $payload['related_resources'] = $this->prepareJsonField('related_resources');
        }

        if ($this->exists('authors')) {
            $payload['authors'] = $this->normalizeIdentityItems($this->prepareJsonField('authors'));
        }

        if ($this->exists('recipients')) {
            $payload['recipients'] = $this->normalizeIdentityItems($this->prepareJsonField('recipients'));
        }

        if ($this->exists('mentioned')) {
            $payload['mentioned'] = $this->normalizeIdentityItems($this->prepareJsonField('mentioned'));
        }

        if ($this->exists('origins') || $this->exists('local_origins') || $this->exists('global_origins')) {
            $payload['origins'] = $this->normalizeScopedItems(
                $this->prepareJsonField('origins'),
                $this->prepareJsonField('local_origins'),
                $this->prepareJsonField('global_origins')
            );
        }

        if ($this->exists('destinations') || $this->exists('local_destinations') || $this->exists('global_destinations')) {
            $payload['destinations'] = $this->normalizeScopedItems(
                $this->prepareJsonField('destinations'),
                $this->prepareJsonField('local_destinations'),
                $this->prepareJsonField('global_destinations')
            );
        }

        if ($this->exists('keywords') || $this->exists('local_keywords') || $this->exists('global_keywords')) {
            $payload['keywords'] = $this->normalizeKeywordItems(
                $this->prepareJsonField('keywords'),
                $this->prepareJsonField('local_keywords'),
                $this->prepareJsonField('global_keywords')
            );
        }

        if ($this->exists('abstract')) {
            $payload['abstract'] = $this->normalizeAbstract($this->input('abstract'));
        }

        $this->merge($payload);

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
        if ($this->exists('date_is_range') || $this->exists('range_year') || $this->exists('range_month') || $this->exists('range_day') || $this->isMethod('POST')) {
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

        // XSS Sanitization for content fields
        if ($this->has('content')) {
            $this->merge([
                'content' => $this->sanitizeHtml($this->input('content')),
            ]);
        }
    }

    /**
     * Custom validation after the standard rules.
     */
    public function withValidator($validator)
    {
        $this->validateAllowedApiV2Fields($validator, [
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
            'authors',
            'recipients',
            'mentioned',
            'origins',
            'destinations',
            'keywords',
            'local_origins',
            'global_origins',
            'local_destinations',
            'global_destinations',
            'local_keywords',
            'global_keywords',
            'abstract',
            'languages',
            'incipit',
            'explicit',
            'notes_private',
            'notes_public',
            'copyright',
            'content',
            'status',
            'approval',
            'related_resources',
            'copies',
            'client_meta',
        ]);

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
     * Allow only basic formatting tags.
     */
    private function sanitizeHtml(?string $html): ?string
    {
        if (!$html) return null;
        // Allowed tags: p, br, b, strong, i, em, u, ul, ol, li, span, div, a
        return strip_tags($html, '<p><br><b><strong><i><em><u><ul><ol><li><span><div><a>');
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

    private function normalizeCopies(?array $copies): ?array
    {
        if (!is_array($copies)) {
            return null;
        }

        return array_values(array_map(function ($copy) {
            if (!is_array($copy)) {
                return [];
            }

            foreach (['repository', 'archive', 'collection'] as $field) {
                $copy[$field] = $this->normalizeScopedReference($copy[$field] ?? null, null);
            }

            return $copy;
        }, $copies));
    }

    private function normalizeIdentityItems(?array $items): ?array
    {
        return $this->normalizeScopedItems($items);
    }

    private function normalizeKeywordItems(?array $items, ?array $legacyLocal = null, ?array $legacyGlobal = null): ?array
    {
        $normalized = $this->normalizeScopedItems($items);

        if ($normalized !== null) {
            return $normalized;
        }

        $normalized = [];

        foreach ($legacyLocal ?? [] as $id) {
            $reference = $this->normalizeScopedReference($id, 'local');
            if ($reference !== null) {
                $normalized[] = ['id' => $reference];
            }
        }

        foreach ($legacyGlobal ?? [] as $id) {
            $reference = $this->normalizeScopedReference($id, 'global');
            if ($reference !== null) {
                $normalized[] = ['id' => $reference];
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeScopedItems(?array $items, ?array $legacyLocal = null, ?array $legacyGlobal = null): ?array
    {
        if (is_array($items)) {
            $normalized = [];

            foreach ($items as $item) {
                if (is_scalar($item) || $item === null) {
                    $reference = $this->normalizeScopedReference($item, null);
                    if ($reference !== null) {
                        $normalized[] = ['id' => $reference];
                    }
                    continue;
                }

                if (!is_array($item)) {
                    continue;
                }

                $reference = $this->normalizeScopedReference(
                    $item['id'] ?? ($item['value'] ?? null),
                    $item['scope'] ?? null
                );

                if ($reference === null) {
                    continue;
                }

                $row = ['id' => $reference];

                foreach (['marked', 'salutation'] as $field) {
                    if (array_key_exists($field, $item)) {
                        $row[$field] = $item[$field];
                    }
                }

                $normalized[] = $row;
            }

            return $normalized === [] ? null : $normalized;
        }

        $normalized = [];

        foreach ($legacyLocal ?? [] as $item) {
            if (is_array($item)) {
                $reference = $this->normalizeScopedReference($item['id'] ?? null, 'local');
                if ($reference === null) {
                    continue;
                }

                $normalized[] = [
                    'id' => $reference,
                    'marked' => $item['marked'] ?? null,
                    'salutation' => $item['salutation'] ?? null,
                ];
            } else {
                $reference = $this->normalizeScopedReference($item, 'local');
                if ($reference !== null) {
                    $normalized[] = ['id' => $reference];
                }
            }
        }

        foreach ($legacyGlobal ?? [] as $item) {
            if (is_array($item)) {
                $reference = $this->normalizeScopedReference($item['id'] ?? null, 'global');
                if ($reference === null) {
                    continue;
                }

                $normalized[] = [
                    'id' => $reference,
                    'marked' => $item['marked'] ?? null,
                    'salutation' => $item['salutation'] ?? null,
                ];
            } else {
                $reference = $this->normalizeScopedReference($item, 'global');
                if ($reference !== null) {
                    $normalized[] = ['id' => $reference];
                }
            }
        }

        return $normalized === [] ? null : $normalized;
    }

    private function normalizeScopedReference(mixed $value, ?string $scope): mixed
    {
        if (is_array($value)) {
            $scope = $value['scope'] ?? $scope;
            $value = $value['id'] ?? ($value['value'] ?? null);
        }

        if ($value === null || $value === '') {
            return null;
        }

        if (is_int($value) || (is_string($value) && ctype_digit($value))) {
            return ($scope === 'global' ? 'global' : 'local') . '-' . (int) $value;
        }

        if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
            return $matches[1] . '-' . (int) $matches[2];
        }

        if (is_string($value)) {
            $value = trim($value);
            return $value === '' ? null : $value;
        }

        return null;
    }

    protected function getLocationValidationRule(string $type)
    {
        return function ($attribute, $value, $fail) use ($type) {
            // Handle Array Input (from Livewire enhancedSelect)
            if (is_array($value)) {
                $value = $value['value'] ?? ($value['label'] ?? null);
            }

            if (empty($value)) {
                return; // Nullable
            }

            // Check if it's an ID (local-X or global-X)
            if (preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
                $scope = $matches[1];
                $id = $matches[2];

                if ($scope === 'local') {
                    $exists = DB::table(tenancy()->tenant->table_prefix . '__locations')
                        ->where('id', $id)
                        ->where('type', $type)
                        ->exists();

                    if (!$exists) {
                        $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                    }
                } elseif ($scope === 'global') {
                    $exists = DB::table('global_locations')
                        ->where('id', $id)
                        ->where('type', $type)
                        ->exists();

                    if (!$exists) {
                        $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                    }
                }
            }
            // If it's a plain string (New Name), we generally allow it
            // because the Controller will create it.
            // OR we can enforce max length.
            else {
                if (strlen($value) > 255) {
                    $fail(__('hiko.validation_max_string_length', [
                        'attribute' => $attribute,
                        'max' => 255,
                    ]));
                }
            }
        };
    }

    protected function getIdentityValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if (is_array($value)) {
                $value = $value['value'] ?? ($value['label'] ?? null);
            }

            if ($value === null || $value === '') {
                return;
            }

            $localTable = tenancy()->tenant->table_prefix . '__identities';

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                $exists = DB::table($localTable)->where('id', (int) $value)->exists();
                if (!$exists) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
                $scope = $matches[1];
                $id = (int) $matches[2];

                if ($scope === 'local') {
                    $exists = DB::table($localTable)->where('id', $id)->exists();
                } else {
                    $exists = DB::table('global_identities')->where('id', $id)->exists();
                }

                if (!$exists) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            $fail(__('hiko.validation_id_not_found', ['id' => $value]));
        };
    }

    protected function getPlaceValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $localTable = tenancy()->tenant->table_prefix . '__places';

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                if (!DB::table($localTable)->where('id', (int) $value)->exists()) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
                $scope = $matches[1];
                $id = (int) $matches[2];
                $table = $scope === 'global' ? 'global_places' : $localTable;

                if (!DB::table($table)->where('id', $id)->exists()) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            $fail(__('hiko.validation_id_not_found', ['id' => $value]));
        };
    }

    protected function getKeywordValidationRule()
    {
        return function ($attribute, $value, $fail) {
            if ($value === null || $value === '') {
                return;
            }

            $localTable = tenancy()->tenant->table_prefix . '__keywords';

            if (is_int($value) || (is_string($value) && ctype_digit($value))) {
                if (!DB::table($localTable)->where('id', (int) $value)->exists()) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            if (is_string($value) && preg_match('/^(local|global)-(\d+)$/', $value, $matches)) {
                $scope = $matches[1];
                $id = (int) $matches[2];
                $table = $scope === 'global' ? 'global_keywords' : $localTable;

                if (!DB::table($table)->where('id', $id)->exists()) {
                    $fail(__('hiko.validation_id_not_found', ['id' => $value]));
                }

                return;
            }

            $fail(__('hiko.validation_id_not_found', ['id' => $value]));
        };
    }
}
