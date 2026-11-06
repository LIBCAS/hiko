<?php

namespace App\Services;

/** The edit-form order and control types shared by the filter form and badges */
class LetterFilterDefinitions
{
    public static function sections(): array
    {
        $sections = [
            'metadata' => ['label' => 'metadata', 'fields' => ['id' => ['id', 'id']]],
            'date' => ['label' => 'date', 'fields' => [
                'after' => ['date', 'from'], 'before' => ['date', 'to'],
                'date_match' => ['date_match', 'filter_date_matching'],
                'date_year' => ['part', 'year'], 'date_month' => ['part', 'month'], 'date_day' => ['part', 'day'],
                'date_marked' => ['text', 'date_marked'],
                'date_uncertain' => ['boolean', 'date_uncertain'],
                'date_approximate' => ['boolean', 'date_approximate'],
                'date_inferred' => ['boolean', 'date_inferred'], 'date_is_range' => ['boolean', 'date_is_range'],
                'range_year' => ['part', 'filter_year_2'], 'range_month' => ['part', 'filter_month_2'],
                'range_day' => ['part', 'filter_day_2'], 'date_note' => ['text', 'date_note'],
            ]],
        ];

        foreach (['author', 'recipient', 'origin', 'destination'] as $role) {
            $fields = [
                "{$role}_count" => ['count', 'filter_count'],
                $role => ['entity', $role], "{$role}_marked" => ['text', 'name_marked'],
            ];

            if ($role === 'recipient') {
                $fields['recipient_salutation'] = ['text', 'salutation'];
            }

            $fields["{$role}_inferred"] = ['boolean', "{$role}_inferred"];
            $fields["{$role}_uncertain"] = ['boolean', "{$role}_uncertain"];
            $fields["{$role}_note"] = ['text', "{$role}_note"];
            $sections[$role] = ['label' => $role, 'fields' => $fields];
        }

        $sections += [
            'content' => ['label' => 'content_description', 'fields' => [
                'languages_count' => ['count', 'filter_languages_count'], 'languages' => ['languages', 'language'],
                'keyword_count' => ['count', 'filter_keywords_count'], 'keyword' => ['entity', 'keywords'],
                'abstract_cs' => ['text', 'filter_abstract_cs'], 'abstract_en' => ['text', 'filter_abstract_en'],
                'abstract' => ['text', 'filter_abstract_both'],
                'incipit' => ['text', 'incipit'], 'explicit' => ['text', 'explicit'],
                'mentioned_count' => ['count', 'filter_mentioned_count'], 'mentioned' => ['entity', 'mentioned'],
                'people_mentioned_note' => ['text', 'people_mentioned_note'],
                'notes_private' => ['text', 'notes_private'], 'notes_public' => ['text', 'notes_public'],
            ]],
            'resources' => ['label' => 'related_resources', 'fields' => [
                'related_resources_count' => ['count', 'filter_count'],
                'resource_title' => ['text', 'name_2'], 'resource_link' => ['text', 'filter_url'],
            ]],
            'manifestations' => ['label' => 'manifestation_location', 'fields' => [
                'manifestations_count' => ['count', 'filter_count'],
                'preservation' => ['checks', 'preservation'], 'type' => ['checks', 'doc_type'],
                'copy' => ['checks', 'doc_mode'], 'manifestation_notes' => ['text', 'manifestation_notes'],
                'l_number' => ['text', 'l_number'],
                'repository' => ['entity', 'repository'], 'archive' => ['entity', 'archive'],
                'collection' => ['entity', 'collection'], 'signature' => ['text', 'signature'],
                'location_note' => ['text', 'location_note'],
            ]],
            'copyright' => ['label' => 'copyright', 'fields' => ['copyright' => ['text', 'copyright']]],
            'status' => ['label' => 'status', 'fields' => [
                'status' => ['checks', 'status'], 'approval' => ['checks', 'approval'],
                'media' => ['boolean', 'media'], 'editor' => ['editor', 'editors'],
            ]],
            'full_text' => ['label' => 'full_text', 'fields' => ['content_stripped' => ['text', 'full_text']]],
        ];

        return $sections;
    }

    public static function fields(): array
    {
        return array_merge(...array_column(self::sections(), 'fields'));
    }

    public static function keys(): array
    {
        $keys = ['match'];

        foreach (self::fields() as $key => [$type]) {
            $keys[] = $key;

            if (in_array($type, ['entity', 'languages'], true)) {
                $keys[] = "{$key}_match";
            }
        }

        return $keys;
    }

    public static function choices(string $field): array
    {
        if ($field === 'status') {
            return ['draft' => __('hiko.draft'), 'publish' => __('hiko.publish')];
        }

        if ($field === 'approval') {
            return ['1' => __('hiko.approved'), '0' => __('hiko.not_approved')];
        }

        $result = [];

        foreach (config("letter_metadata.{$field}", []) as $value => $label) {
            $translation = match ($field) {
                'preservation' => 'preservation_' . str_replace(' ', '_', $value),
                default => str_replace(' ', '_', $value),
            };
            $result[$value] = __("hiko.{$translation}");
        }

        return $result;
    }
}
