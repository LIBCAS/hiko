<?php

namespace App\Livewire;

use App\Http\Requests\GlobalKeywordRequest;
use App\Http\Requests\KeywordRequest;
use App\Models\GlobalKeyword;
use App\Models\Keyword;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class KeywordsConsistencyCheck extends Component
{
    public string $scope = 'all';
    public bool $isScanning = false;
    public array $issues = [];

    public function scan(): void
    {
        $this->isScanning = true;
        $this->issues = [];

        if (($this->scope === 'local' || $this->scope === 'all') && tenancy()->initialized) {
            $rules = (new KeywordRequest())->rules();

            Keyword::query()->chunk(200, function ($keywords) use ($rules): void {
                foreach ($keywords as $keyword) {
                    $input = [
                        'cs' => $this->nullableTrim($keyword->getTranslation('name', 'cs')),
                        'en' => $this->nullableTrim($keyword->getTranslation('name', 'en')),
                        'keyword_category_id' => $keyword->keyword_category_id,
                    ];

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'local',
                                'id' => $keyword->id,
                                'name' => $keyword->getTranslation('name', app()->getLocale()),
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        if ($this->scope === 'global' || $this->scope === 'all') {
            $rules = (new GlobalKeywordRequest())->rules();

            GlobalKeyword::query()->chunk(200, function ($keywords) use ($rules): void {
                foreach ($keywords as $keyword) {
                    $input = [
                        'cs' => $this->nullableTrim($keyword->getTranslation('name', 'cs')),
                        'en' => $this->nullableTrim($keyword->getTranslation('name', 'en')),
                        'keyword_category_id' => $keyword->keyword_category_id,
                    ];

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'global',
                                'id' => $keyword->id,
                                'name' => $keyword->getTranslation('name', app()->getLocale()),
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        $this->isScanning = false;
    }

    public function render()
    {
        return view('livewire.keywords-consistency-check');
    }

    private function nullableTrim(?string $value): ?string
    {
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}
