<?php 

namespace App\Http\Livewire;

use App\Models\Profession;
use App\Models\GlobalProfession;
use Livewire\Component;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Spatie\Translatable\HasTranslations;

class ProfessionsTable extends Component
{
    use WithPagination, HasTranslations;

    public $filters = [
        'order' => 'cs',
    ];

    public function resetFilters()
    {
        $this->reset('filters');
        $this->search();
    }

    public function search()
    {
        $this->resetPage('professionsPage');
    }

    public function mount()
    {
        if (session()->has('identitiesTableFilters')) {
            $this->filters = session()->get('identitiesTableFilters');
        }
    }
    
    public function render()
    {
        $professions = $this->findProfessions();
    
        return view('livewire.professions-table', [
            
            'tableData' => $this->formatTableData($professions->items()),
            'pagination' => $professions,
        ]);
    }

    protected function findProfessions()
    {
        $perPage = 10;
    
        $globalProfessions = GlobalProfession::query()
            ->when(!empty($this->filters['cs']), function ($query) {
                $query->whereRaw("LOWER(TRIM(json_unquote(json_extract(`name`, '$.cs')))) LIKE ?", ['%' . strtolower(trim($this->filters['cs'])) . '%']);
            })
            ->when(!empty($this->filters['en']), function ($query) {
                $query->whereRaw("LOWER(TRIM(json_unquote(json_extract(`name`, '$.en')))) LIKE ?", ['%' . strtolower(trim($this->filters['en'])) . '%']);
            })
            ->get();
    
        $tenantProfessions = Profession::query()
            ->when(!empty($this->filters['cs']), function ($query) {
                $query->whereRaw("LOWER(TRIM(json_unquote(json_extract(`name`, '$.cs')))) LIKE ?", ['%' . strtolower(trim($this->filters['cs'])) . '%']);
            })
            ->when(!empty($this->filters['en']), function ($query) {
                $query->whereRaw("LOWER(TRIM(json_unquote(json_extract(`name`, '$.en')))) LIKE ?", ['%' . strtolower(trim($this->filters['en'])) . '%']);
            })
            ->get();
    
        $mergedProfessions = $globalProfessions->merge($tenantProfessions);
    
        $currentPage = LengthAwarePaginator::resolveCurrentPage('professionsPage');
        $pagedData = $mergedProfessions->slice(($currentPage - 1) * $perPage, $perPage)->values();
    
        return new LengthAwarePaginator(
            $pagedData,
            $mergedProfessions->count(),
            $perPage,
            $currentPage,
            ['path' => url()->current()]
        );
    }    
    
    protected function formatTableData($data)
    {
        $data = collect($data);
    
        $header = auth()->user()->cannot('manage-metadata')
            ? ['CS', 'EN', __('hiko.category')]
            : ['', 'CS', 'EN', __('hiko.category')];
    
        return [
            'header' => $header,
            'rows' => $data->map(function ($pf) {
                $row = auth()->user()->cannot('manage-metadata')
                    ? []
                    : [
                        [
                            'label' => __('hiko.edit'),
                            'link' => route('professions.edit', is_array($pf) ? $pf['id'] : $pf->id),
                        ],
                    ];
    
                if (is_array($pf)) {

                    $name = is_string($pf['name']) ? json_decode($pf['name'], true) : $pf['name'];
                    $csName = isset($name['cs']) ? $name['cs'] : 'N/A';
                    $enName = isset($name['en']) ? $name['en'] : 'N/A';

                } else {

                    $csName = $pf->getTranslation('name', 'cs', false) ?? 'N/A';
                    $enName = $pf->getTranslation('name', 'en', false) ?? 'N/A';

                }
    
                $category = is_array($pf) && isset($pf['profession_category'])
                    ? $pf['profession_category']['name']
                    : ($pf->profession_category ? $pf->profession_category->getTranslation('name', config('hiko.metadata_default_locale')) : 'N/A');
    
                return array_merge($row, [
                    [
                        'label' => $csName,
                    ],
                    [
                        'label' => $enName,
                    ],
                    [
                        'label' => $category,
                    ],
                ]);
            })->toArray(),
        ];
    }    
}