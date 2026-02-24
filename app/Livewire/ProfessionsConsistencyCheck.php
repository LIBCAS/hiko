<?php

namespace App\Livewire;

use App\Http\Requests\GlobalProfessionRequest;
use App\Http\Requests\ProfessionRequest;
use App\Models\GlobalProfession;
use App\Models\Profession;
use Illuminate\Support\Facades\Validator;
use Livewire\Component;

class ProfessionsConsistencyCheck extends Component
{
    public string $scope = 'all';
    public bool $isScanning = false;
    public array $issues = [];

    public function scan(): void
    {
        $this->isScanning = true;
        $this->issues = [];

        if (($this->scope === 'local' || $this->scope === 'all') && tenancy()->initialized) {
            $rules = (new ProfessionRequest())->rules();

            Profession::query()->chunk(200, function ($professions) use ($rules): void {
                foreach ($professions as $profession) {
                    $input = [
                        'cs' => $this->nullableTrim($profession->getTranslation('name', 'cs')),
                        'en' => $this->nullableTrim($profession->getTranslation('name', 'en')),
                        'profession_category_id' => $profession->profession_category_id,
                    ];

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'local',
                                'id' => $profession->id,
                                'name' => $profession->getTranslation('name', app()->getLocale()),
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        if ($this->scope === 'global' || $this->scope === 'all') {
            $rules = (new GlobalProfessionRequest())->rules();

            GlobalProfession::query()->chunk(200, function ($professions) use ($rules): void {
                foreach ($professions as $profession) {
                    $input = [
                        'cs' => $this->nullableTrim($profession->getTranslation('name', 'cs')),
                        'en' => $this->nullableTrim($profession->getTranslation('name', 'en')),
                        'profession_category_id' => $profession->profession_category_id,
                    ];

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => 'global',
                                'id' => $profession->id,
                                'name' => $profession->getTranslation('name', app()->getLocale()),
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
        return view('livewire.professions-consistency-check');
    }

    private function nullableTrim(?string $value): ?string
    {
        $trimmed = trim((string)$value);
        return $trimmed === '' ? null : $trimmed;
    }
}
