<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class NationalityRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->can('manage-users') ?? false;
    }

    protected function prepareForValidation(): void
    {
        $model = $this->route('nationality');
        if ($this->isMethod('PUT') && $model instanceof \App\Models\Nationality) {
            foreach (['cs', 'en'] as $locale) {
                if (!$this->exists($locale)) $this->merge([$locale => $model->getTranslation('name', $locale, false)]);
            }
        }
    }

    public function rules(): array
    {
        return [
            'cs' => ['required', 'string', 'max:255', 'regex:/[^\s\p{Z}]/u'],
            'en' => ['required', 'string', 'max:255', 'regex:/[^\s\p{Z}]/u']
        ];
    }
}
