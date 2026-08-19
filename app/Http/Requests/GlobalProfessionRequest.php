<?php

namespace App\Http\Requests;

use App\Http\Requests\Concerns\InteractsWithApiV2;
use App\Models\GlobalProfession;
use Illuminate\Foundation\Http\FormRequest;

class GlobalProfessionRequest extends FormRequest
{
    use InteractsWithApiV2;

    public function authorize(): bool
    {
        return $this->user()?->hasAbility('manage-users') ?? false;
    }

    public function rules(): array
    {
        if ($this->isApiV2Request()) {
            $nameRules = $this->isApiV2UpdateRequest()
                ? ['sometimes', 'nullable']
                : ['nullable'];
            $csRules = $this->isApiV2UpdateRequest()
                ? ['sometimes', 'nullable', 'string', 'max:255']
                : ['nullable', 'string', 'max:255', 'required_without_all:en,name'];
            $enRules = $this->isApiV2UpdateRequest()
                ? ['sometimes', 'nullable', 'string', 'max:255']
                : ['nullable', 'string', 'max:255', 'required_without_all:cs,name'];
            $categoryRules = $this->isApiV2UpdateRequest()
                ? ['sometimes', 'required', 'exists:global_profession_categories,id']
                : ['required', 'exists:global_profession_categories,id'];

            return [
                'name' => $nameRules,
                'cs' => $csRules,
                'en' => $enRules,
                'profession_category_id' => $categoryRules,
                'client_meta' => ['nullable', 'array'],
            ];
        }

        return [
            'cs' => ['required', 'string', 'max:255'],
            'en' => ['nullable', 'string', 'max:255'],
            'profession_category_id' => ['required', 'exists:global_profession_categories,id'],
        ];
    }

    public function prepareForValidation(): void
    {
        $payload = [];

        if ($this->exists('cs')) {
            $payload['cs'] = $this->filled('cs') ? trim((string) $this->input('cs')) : null;
        }

        if ($this->exists('en')) {
            $payload['en'] = $this->filled('en') ? trim((string) $this->input('en')) : null;
        }

        if ($this->exists('profession_category_id') || $this->exists('category_id') || $this->exists('category')) {
            $payload['profession_category_id'] = $this->input('profession_category_id', $this->input('category_id', $this->input('category')));
        }

        $this->merge($payload);
    }

    public function withValidator($validator): void
    {
        $this->validateAllowedApiV2Fields($validator, [
            'name',
            'cs',
            'en',
            'profession_category_id',
            'category_id',
            'category',
            'client_meta',
        ]);

        $validator->after(function ($validator): void {
            if (!$this->isApiV2UpdateRequest() || $this->exists('profession_category_id')) {
                return;
            }

            $professionId = $this->route('id');
            if ($professionId !== null && GlobalProfession::query()->whereKey($professionId)->whereNull('profession_category_id')->exists()) {
                $validator->errors()->add('category_id', __('validation.required', [
                    'attribute' => 'category id',
                ]));
            }
        });
    }
}
