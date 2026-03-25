<?php

namespace App\Http\Requests\Concerns;

use Illuminate\Contracts\Validation\Validator;

trait InteractsWithApiV2
{
    protected function isApiV2Request(): bool
    {
        return $this->is('api/v2/*');
    }

    protected function isApiV2WriteRequest(): bool
    {
        return $this->isApiV2Request() && in_array($this->method(), ['POST', 'PUT', 'PATCH'], true);
    }

    protected function isApiV2UpdateRequest(): bool
    {
        return $this->isApiV2Request() && in_array($this->method(), ['PUT', 'PATCH'], true);
    }

    protected function optionalOnApiV2Update(array $rules): array
    {
        if (!$this->isApiV2UpdateRequest()) {
            return $rules;
        }

        $filtered = array_values(array_filter(
            $rules,
            fn ($rule) => !in_array($rule, ['required', 'required_without:cs', 'required_without:en', 'required_without_all:cs,name', 'required_without_all:en,name'], true)
        ));

        array_unshift($filtered, 'sometimes');

        return $filtered;
    }

    protected function validateAllowedApiV2Fields(Validator $validator, array $allowedFields): void
    {
        if (!$this->isApiV2WriteRequest()) {
            return;
        }

        $unknownFields = array_diff(array_keys($this->all()), $allowedFields);

        foreach ($unknownFields as $field) {
            $validator->errors()->add(
                $field,
                "The {$field} field is not supported by this endpoint. Use client_meta for custom app data."
            );
        }
    }
}
