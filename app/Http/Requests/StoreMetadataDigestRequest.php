<?php

namespace App\Http\Requests;

use Carbon\CarbonImmutable;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreMetadataDigestRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->role === 'admin' && !$this->user()->isDeactivated();
    }

    public function rules(): array
    {
        return [
            'period_start' => ['required', 'date'],
            'period_end' => ['required', 'date', 'after:period_start'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->isNotEmpty()) {
                    return;
                }

                $timezone = config('metadata_digest.timezone');
                $start = CarbonImmutable::parse($this->input('period_start'), $timezone);
                $end = CarbonImmutable::parse($this->input('period_end'), $timezone);

                if ($start->diffInDays($end) > config('metadata_digest.manual_max_days', 366)) {
                    $validator->errors()->add(
                        'period_end',
                        __('hiko.metadata_digest_period_too_long', [
                            'days' => config('metadata_digest.manual_max_days', 366),
                        ])
                    );
                }
            },
        ];
    }
}
