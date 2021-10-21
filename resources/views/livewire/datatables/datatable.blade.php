<div>
    @if ($beforeTableSlot)
        <div class="mt-8">
            @include($beforeTableSlot)
        </div>
    @endif
    <div class="relative">
        <div class="flex items-center justify-between mb-1">
            <div class="flex items-center h-10">
                @if ($this->searchableColumns()->count())
                    <div class="flex rounded-lg shadow-sm w-96">
                        <div class="relative flex-grow focus-within:z-10">
                            <div class="absolute inset-y-0 left-0 flex items-center pl-3 pointer-events-none">
                                <svg class="w-5 h-5 text-gray-400" viewBox="0 0 20 20" stroke="currentColor" fill="none">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                        d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z" />
                                </svg>
                            </div>
                            <input wire:model.debounce.500ms="search"
                                class="block w-full py-3 pl-10 text-sm leading-4 border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50 focus:outline-none"
                                placeholder="{{ __('Vyhledávat') }} {{ $this->searchableColumns()->map->label->join(', ') }}"
                                type="text" />
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <button wire:click="$set('search', null)"
                                    class="text-gray-300 hover:text-red-600 focus:outline-none">
                                    <x-icons.x-circle class="w-5 h-5 stroke-current" />
                                </button>
                            </div>
                        </div>
                    </div>
                @endif
            </div>

            @if ($this->activeFilters)
                <span class="text-xl text-blue-400 uppercase">{{ __('Aktivní filtry') }}</span>
            @endif

            <div class="flex flex-wrap items-center space-x-1">
                <x-icons.cog wire:loading class="text-gray-400 h-9 w-9 animate-spin" />

                @if ($this->activeFilters)
                    <button wire:click="clearAllFilters"
                        class="flex items-center px-3 space-x-2 text-xs font-medium leading-4 tracking-wider text-red-500 uppercase bg-white border border-red-400 rounded-md hover:bg-red-200 focus:outline-none"><span>{{ __('Resetovat') }}</span>
                        <x-icons.x-circle class="m-2" />
                    </button>
                @endif

                @if ($exportable)
                    <div x-data="{ init() {
                    window.livewire.on('startDownload', link => window.open(link, '_blank'))
                } }" x-init="init">
                        <button wire:click="export"
                            class="flex items-center px-3 space-x-2 text-xs font-medium leading-4 tracking-wider text-green-500 uppercase bg-white border border-green-400 rounded-md hover:bg-green-200 focus:outline-none"><span>{{ __('Export') }}</span>
                            <x-icons.excel class="m-2" />
                        </button>
                    </div>
                @endif

                @if ($hideable === 'select')
                    @include('datatables::hide-column-multiselect')
                @endif

                @foreach ($columnGroups as $name => $group)
                    <button wire:click="toggleGroup('{{ $name }}')"
                        class="px-3 py-2 text-xs font-medium leading-4 tracking-wider text-green-500 uppercase bg-white border border-green-400 rounded-md hover:bg-green-200 focus:outline-none">
                        <span
                            class="flex items-center h-5">{{ isset($this->groupLabels[$name]) ? __($this->groupLabels[$name]) : __('Toggle :group', ['group' => $name]) }}</span>
                    </button>
                @endforeach
            </div>
        </div>

        @if ($hideable === 'buttons')
            <div class="grid grid-cols-8 gap-2 p-2">
                @foreach ($this->columns as $index => $column)
                    @if ($column['hideable'])
                        <button wire:click.prefetch="toggle('{{ $index }}')"
                            class="px-3 py-2 rounded text-white text-xs focus:outline-none
                    {{ $column['hidden'] ? 'bg-blue-100 hover:bg-blue-300 text-blue-600' : 'bg-blue-500 hover:bg-blue-800' }}">
                            {{ $column['label'] }}
                        </button>
                    @endif
                @endforeach
            </div>
        @endif

        <div wire:loading.class="opacity-50"
            class="rounded-lg @unless($complex || $this->hidePagination) rounded-b-none @endunless shadow-lg bg-white max-w-screen overflow-x-scroll border-4 @if ($this->activeFilters) border-blue-500 @else border-transparent @endif @if ($complex) rounded-b-none border-b-0 @endif">
            <div>
                <div class="table min-w-full align-middle">
                    @unless($this->hideHeader)
                        <div class="table-row divide-x divide-gray-200">
                            @foreach ($this->columns as $index => $column)
                                @if ($hideable === 'inline')
                                    @include('datatables::header-inline-hide', ['column' => $column, 'sort' => $sort])
                                @elseif($column['type'] === 'checkbox')
                                    @unless($column['hidden'])
                                        <div
                                            class="flex justify-center table-cell w-32 h-12 px-6 py-4 overflow-hidden text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase align-top border-b border-gray-200 bg-gray-50 focus:outline-none">
                                            <div class="px-3 py-1 rounded @if (count($selected)) bg-orange-400 @else bg-gray-200 @endif text-white text-center">
                                                {{ count($selected) }}
                                            </div>
                                        </div>
                                    @endunless
                                @else
                                    @include('datatables::header-no-hide', ['column' => $column, 'sort' => $sort])
                                @endif
                            @endforeach
                        </div>

                        <div class="table-row bg-blue-100 divide-x divide-blue-200">
                            @foreach ($this->columns as $index => $column)
                                @if ($column['hidden'])
                                    @if ($hideable === 'inline')
                                        <div class="table-cell w-5 overflow-hidden align-top bg-blue-100"></div>
                                    @endif
                                @elseif($column['type'] === 'checkbox')
                                    <div
                                        class="flex flex-col items-center h-full px-6 py-5 space-y-2 overflow-hidden text-xs font-medium leading-4 tracking-wider text-left text-gray-500 uppercase align-top bg-blue-100 border-b border-gray-200 focus:outline-none">
                                        <div>SELECT ALL</div>
                                        <div>
                                            <input type="checkbox" wire:click="toggleSelectAll" @if (count($selected) === $this->results->total()) checked @endif
                                                class="w-4 h-4 mt-1 text-blue-600 transition duration-150 ease-in-out form-checkbox" />
                                        </div>
                                    </div>
                                @elseif($column['type'] === 'label')
                                    <div class="table-cell overflow-hidden align-top">
                                        {{ $column['label'] ?? '' }}
                                    </div>
                                @else
                                    <div class="table-cell overflow-hidden align-top">
                                        @isset($column['filterable'])
                                            @if (is_iterable($column['filterable']))
                                                <div wire:key="{{ $index }}">
                                                    @include('datatables::filters.select', ['index' => $index, 'name' =>
                                                    $column['label'], 'options' => $column['filterable']])
                                                </div>
                                            @else
                                                <div wire:key="{{ $index }}">
                                                    @include('datatables::filters.' . ($column['filterView'] ??
                                                    $column['type']), ['index' => $index, 'name' => $column['label']])
                                                </div>
                                            @endif
                                        @endisset
                                    </div>
                                @endif
                            @endforeach
                        </div>
                        @endif
                        @forelse($this->results as $row)
                            <div class="table-row p-1 {{ $this->rowClasses($row, $loop) }}">
                                @foreach ($this->columns as $column)
                                    @if ($column['hidden'])
                                        @if ($hideable === 'inline')
                                            <div class="table-cell w-5 overflow-hidden align-top"></div>
                                        @endif
                                    @elseif($column['type'] === 'checkbox')
                                        @include('datatables::checkbox', ['value' => $row->checkbox_attribute])
                                    @elseif($column['type'] === 'label')
                                        @include('datatables::label')
                                    @else
                                        <div
                                            class="table-cell px-6 py-2 whitespace-no-wrap @if ($column['align'] === 'right') text-right @elseif($column['align'] === 'center') text-center @else text-left @endif {{ $this->cellClasses($row, $column) }}">
                                            @if (!is_numeric($row->{$column['name']}) && json_decode('[' . $row->{$column['name']} . ']'))
                                                <ul>
                                                    @foreach (json_decode('[' . $row->{$column['name']} . ']', true) as $item)
                                                        <li>
                                                            {{ is_numeric($item) ? $item : implode(' | ', array_values($item)) }}
                                                        </li>
                                                    @endforeach
                                                </ul>
                                            @else
                                                {!! $row->{$column['name']} !!}
                                            @endif
                                        </div>
                                    @endif
                                @endforeach
                            </div>
                        @empty
                            <p class="p-3 text-lg text-teal-600">
                                {{ __('Nebyl nalezen žádný záznam') }}
                            </p>
                        @endforelse
                    </div>
                </div>
            </div>

            @unless($this->hidePagination)
                <div
                    class="max-w-screen bg-white @unless($complex) rounded-b-lg @endunless border-4 border-t-0 border-b-0 @if ($this->activeFilters) border-blue-500 @else border-transparent @endif">
                    <div class="items-center justify-between p-2 sm:flex">
                        {{-- check if there is any data --}}
                        @if (count($this->results))
                            <div class="flex items-center my-2 sm:my-0">
                                <select name="perPage"
                                    class="block w-full py-2 pl-3 pr-10 mt-1 text-base leading-6 border-gray-300 form-select focus:outline-none focus:shadow-outline-blue focus:border-blue-300 sm:text-sm sm:leading-5"
                                    wire:model="perPage">
                                    @foreach (config('livewire-datatables.per_page_options', [10, 25, 50, 100]) as $per_page_option)
                                        <option value="{{ $per_page_option }}">{{ $per_page_option }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="my-4 sm:my-0">
                                <div class="lg:hidden">
                                    <span
                                        class="space-x-2">{{ $this->results->links('datatables::tailwind-simple-pagination') }}</span>
                                </div>

                                <div class="justify-center hidden lg:flex">
                                    <span>{{ $this->results->links('datatables::tailwind-pagination') }}</span>
                                </div>
                            </div>

                            <div class="flex justify-end text-gray-600">
                                {{ __('Zobrazeno') }} {{ $this->results->firstItem() }} -
                                {{ $this->results->lastItem() }}
                                {{ __('z celkového počtu') }}
                                {{ $this->results->total() }}
                            </div>
                        @endif
                    </div>
                </div>
                @endif
            </div>

            @if ($complex)
                <div class="bg-gray-50 px-4 py-4 rounded-b-lg rounded-t-none shadow-lg border-4 @if ($this->activeFilters) border-blue-500 @else border-transparent @endif @if ($complex) border-t-0 @endif">
                    <livewire:complex-query :columns="$this->complexColumns" :persistKey="$this->persistKey"
                        :savedQueries="method_exists($this, 'getSavedQueries') ? $this->getSavedQueries() : null" />
                </div>
            @endif

            @if ($afterTableSlot)
                <div class="mt-8">
                    @include($afterTableSlot)
                </div>
            @endif
            <span
                class="hidden text-sm leading-5 text-left text-center text-right text-gray-900 bg-gray-100 bg-yellow-100 bg-gray-50"></span>
        </div>
