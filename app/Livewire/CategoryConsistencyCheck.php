<?php

namespace App\Livewire;

use App\Http\Requests\GlobalKeywordCategoryRequest;
use App\Http\Requests\GlobalProfessionCategoryRequest;
use App\Http\Requests\KeywordCategoryRequest;
use App\Http\Requests\ProfessionCategoryRequest;
use App\Models\GlobalKeywordCategory;
use App\Models\GlobalProfessionCategory;
use App\Models\KeywordCategory;
use App\Models\ProfessionCategory;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Validator;
use Livewire\Attributes\Locked;
use Livewire\Component;

class CategoryConsistencyCheck extends Component
{
    #[Locked]
    public string $kind;

    public string $scope = 'all';

    #[Locked]
    public bool $hasScanned = false;

    #[Locked]
    public array $issues = [];

    private const SOURCES = [
        'profession' => [
            'local' => [ProfessionCategory::class, ProfessionCategoryRequest::class],
            'global' => [GlobalProfessionCategory::class, GlobalProfessionCategoryRequest::class],
        ],
        'keyword' => [
            'local' => [KeywordCategory::class, KeywordCategoryRequest::class],
            'global' => [GlobalKeywordCategory::class, GlobalKeywordCategoryRequest::class],
        ],
    ];

    public function mount(string $kind): void
    {
        Gate::authorize('view-metadata');
        abort_unless(array_key_exists($kind, self::SOURCES), 404);
        $this->kind = $kind;
    }

    public function scan(): void
    {
        Gate::authorize('view-metadata');
        $this->validate(['scope' => ['required', 'in:all,local,global']]);
        $this->issues = [];
        $this->hasScanned = false;

        foreach (self::SOURCES[$this->kind] as $scope => [$modelClass, $requestClass]) {
            if (($this->scope !== 'all' && $this->scope !== $scope)
                || ($scope === 'local' && !tenancy()->initialized)) {
                continue;
            }

            $rules = (new $requestClass())->rules();
            $modelClass::query()->chunkById(200, function ($categories) use ($scope, $rules): void {
                foreach ($categories as $category) {
                    $translations = $category->getTranslations('name');
                    $input = [];
                    foreach (['cs', 'en'] as $locale) {
                        $value = $translations[$locale] ?? null;
                        $input[$locale] = is_string($value) ? trim($value) : $value;
                    }

                    $validator = Validator::make($input, $rules);
                    if ($validator->fails()) {
                        $label = $input[app()->getLocale()] ?? null;
                        $label = is_string($label) && $label !== '' ? $label : '#'.$category->id;
                        foreach ($validator->errors()->all() as $error) {
                            $this->issues[] = [
                                'type' => $scope,
                                'id' => $category->id,
                                'name' => $label,
                                'error' => $error,
                            ];
                        }
                    }
                }
            });
        }

        $this->hasScanned = true;
    }

    public function render()
    {
        return view('livewire.category-consistency-check', [
            'routePrefix' => $this->kind === 'profession' ? 'professions' : 'keywords',
        ]);
    }
}
