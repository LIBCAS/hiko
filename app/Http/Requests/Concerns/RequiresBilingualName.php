<?php

namespace App\Http\Requests\Concerns;

trait RequiresBilingualName
{
    protected function bilingualNameRules(): array
    {
        return [
            'cs' => ['required', 'string', 'max:255'],
            'en' => ['required', 'string', 'max:255'],
        ];
    }

    /** Validate the resulting translations, without locale fallback or scalar coercion. */
    protected function prepareBilingualName(string $modelClass, bool $acceptName = false): void
    {
        $payload = [];

        if ($acceptName && $this->isApiV2Request() && $this->exists('name')) {
            $name = $this->input('name');
            if (is_string($name)) {
                $decoded = json_decode($name, true);
                if (is_array($decoded)) {
                    $name = $decoded;
                    $payload['name'] = $decoded;
                }
            }
            if (is_array($name)) {
                foreach (['cs', 'en'] as $locale) {
                    if (!$this->exists($locale) && array_key_exists($locale, $name)) {
                        $payload[$locale] = $name[$locale];
                    }
                }
            }
        }

        $current = [];
        if ($this->isApiV2UpdateRequest() && $this->route('id') !== null) {
            $record = $modelClass::query()->findOrFail($this->route('id'));
            $current = $record->getTranslations('name');
        }

        foreach (['cs', 'en'] as $locale) {
            $value = $this->exists($locale)
                ? $this->input($locale)
                : (array_key_exists($locale, $payload) ? $payload[$locale] : ($current[$locale] ?? null));
            $payload[$locale] = is_string($value) ? trim($value) : $value;
        }

        $this->merge($payload);
    }
}
