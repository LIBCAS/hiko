<?php

namespace App\Livewire;

use App\Models\Letter;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Livewire\Component;
use Livewire\WithPagination;
use Stancl\Tenancy\Middleware\InitializeTenancyByRequestData;

class LettersTable extends Component
{
    use WithPagination;

    protected $middleware = [
        InitializeTenancyByRequestData::class
    ];

    public array $filters = [];
    public array $sorting = [
        'order' => 'updated_at',
        'direction' => 'desc',
    ];

    protected $listeners = ['filtersChanged', 'sortingChanged', 'removeFilter', 'resetLettersTablePage'];

    public function mount()
    {
        $this->filters = session()->get('lettersTableFilters', []);
        $this->sorting = session()->get('lettersTableSorting', [
            'order' => 'updated_at',
            'direction' => 'desc',
        ]);
    }

    public function filtersChanged(array $filters)
    {
        $this->filters = $filters;
        session()->put('lettersTableFilters', $this->filters);
        $this->resetPage();
    }

    public function sortingChanged(array $sorting)
    {
        $this->sorting = $sorting;
        session()->put('lettersTableSorting', $this->sorting);
        $this->resetPage();
    }

    public function removeFilter(string $filterKey)
    {
        unset($this->filters[$filterKey]);
        session()->put('lettersTableFilters', $this->filters);
        $this->resetPage();
    }

    public function resetLettersTablePage()
    {
        $this->resetPage();
    }

    public function resetFilters()
    {
        $this->filters = [];
        session()->forget('lettersTableFilters');
        $this->resetPage();
    }

    public function render()
    {
        $letters = $this->findLetters();
        return view('livewire.letters-table', [
            'tableData' => $this->formatTableData($letters),
            'pagination' => $letters,
        ]);
    }

    protected function findLetters(): LengthAwarePaginator
    {
        $tenantPrefix = tenancy()->initialized ? tenancy()->tenant->table_prefix . '__' : '';
    
        $lettersTable = "{$tenantPrefix}letters";
        $mediaTable = "{$tenantPrefix}media";
        $identityLetterTable = "{$tenantPrefix}identity_letter";
        $identitiesTable = "{$tenantPrefix}identities";
        $letterPlaceTable = "{$tenantPrefix}letter_place";
        $placesTable = "{$tenantPrefix}places";
        $keywordLetterTable = "{$tenantPrefix}keyword_letter";
        $keywordsTable = "{$tenantPrefix}keywords";
    
        $query = Letter::from("{$lettersTable} as letters")
            ->select([
                'letters.id', 'letters.uuid', 'letters.history', 'letters.date_computed',
                'letters.updated_at', 'letters.status', 'letters.approval',
                \DB::raw("(SELECT COUNT(*) FROM {$mediaTable} as media WHERE media.model_id = letters.id AND media.model_type = '" . addslashes(Letter::class) . "') AS media_count")
            ])
            ->with([
                'identities:id,name',
                'places:id,name',
                'keywords:id,name',
                'media:id,model_id,model_type',
                'users:id,name',
            ]);
    
        // **Fix ambiguous 'id' in filters**
        if (!empty($this->filters['id'])) {
            $query->where('letters.id', 'LIKE', "%{$this->filters['id']}%");
        }
    
        $query->filter($this->filters);
    
        $order = $this->sorting['order'] ?? 'updated_at';
        $direction = $this->sorting['direction'] ?? 'desc';
    
        switch ($order) {
            case 'media':  // Sorting by media count
                $query->orderBy('media_count', $direction);
                break;
    
            case 'id':
            case 'date_computed':
            case 'updated_at':
                $query->orderBy("letters.{$order}", $direction);
                break;
    
            default:
                $query->orderBy('updated_at', 'desc');
                break;
        }
    
        return $query->paginate(25);
    }

    protected function formatTableData($data): array
    {
        return [
            'header' => [
                '', 'ID', __('hiko.date'), __('hiko.signature'),
                __('hiko.author'), __('hiko.recipient'),
                __('hiko.origin'), __('hiko.destination'),
                __('hiko.keywords'), __('hiko.media'),
                __('hiko.status'), __('hiko.approval')
            ],
            'rows' => $data->map(fn($letter) => $this->mapLetterToRow($letter))->toArray(),
        ];
    }

    protected function mapLetterToRow(Letter $letter): array
    {
        $identities = $letter->identities->groupBy('pivot.role')->toArray();
        $places = $letter->places->groupBy('pivot.role')->toArray();
        $showPublicUrl = $letter->status === 'publish' && !empty(config('hiko.public_url'));

        return [
            ['component' => ['args' => ['id' => $letter->id, 'history' => $letter->history], 'name' => 'tables.letter-actions']],
            ['label' => $letter->id],
            ['label' => $letter->pretty_date],
            ['label' => collect($letter->copies)->pluck('signature')->toArray()],
            ['label' => collect($identities['author'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($identities['recipient'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($places['origin'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($places['destination'] ?? [])->pluck('name')->toArray()],
            ['label' => collect($letter->keywords)->map(fn($kw) => $kw->getTranslation('name', config('hiko.metadata_default_locale')))->toArray()],
            ['label' => $letter->media->count()],
            ['label' => __("hiko.{$letter->status}"), 'link' => $showPublicUrl ? config('hiko.public_url') . '?letter=' . $letter->uuid : '', 'external' => $showPublicUrl],
            ['label' => $letter->approval === Letter::APPROVED ? '<span class="bg-green-100 text-green-800">'. __('hiko.approved') .'</span>' : '<span class="bg-red-100 text-red-800">'. __('hiko.not_approved') .'</span>'],
        ];
    }
}
