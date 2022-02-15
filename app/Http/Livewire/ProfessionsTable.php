<?php

namespace App\Http\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class ProfessionsTable extends Component
{
    use WithPagination;

    public $filters = [
        'order' => 'cs',
    ];

    public $model;

    public function search()
    {
        $this->resetPage();
    }

    public function render()
    {
        $professions = $this->findProfessions();

        return view('livewire.professions-table', [
            'tableData' => $this->formatTableData($professions),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions()
    {
        $query = app('App\Models\\' . $this->model)::select('id', 'name', DB::raw("LOWER(JSON_EXTRACT(name, '$.cs')) AS cs"), DB::raw("LOWER(JSON_EXTRACT(name, '$.en')) AS en"));

        if (isset($this->filters['cs']) && !empty($this->filters['cs'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.cs')) like ?", ["%{$this->filters['cs']}%"]);
        }

        if (isset($this->filters['en']) && !empty($this->filters['en'])) {
            $query->whereRaw("LOWER(JSON_EXTRACT(name, '$.en')) like ?", ["%{$this->filters['en']}%"]);
        }

        $query->orderBy($this->filters['order']);

        return $query->paginate(10, ['*'], "{$this->model}Page");
    }

    protected function formatTableData($data)
    {
        return [
            'header' => ['', 'CS', 'EN'],
            'rows' => $data->map(function ($profession) {
                return [
                    [
                        'label' => __('hiko.edit'),
                        'link' => route('users.edit', $profession->id),
                    ],
                    [
                        'label' => $profession->getTranslation('name', 'cs'),
                    ],
                    [
                        'label' => $profession->getTranslation('name', 'en'),
                    ],
                ];
            })->toArray(),
        ];
    }
}
