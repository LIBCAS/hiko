<?php

/**
 * Helper class for FormRequest-related utilities.
 */

namespace App\Helpers;

class FormRequestHelper
{
    /**
     * Get the human-readable translation label for a given attribute key.
     *
     * @param  string  $key
     * @return string
     */
    public static function attributeLabel(string $key): string
    {
        return __("hiko.{$key}");
    }

    /**
     * Get a mapping of attribute keys to their human-readable translation labels.
     *
     * @param  array  $rules
     * @return array
     */
    public static function mapAttributeLabelsFromRules(array $rules): array
    {
        return collect($rules)
            ->keys()
            ->mapWithKeys(fn ($field) => [$field => __("hiko.{$field}")])
            ->toArray();
    }
}
