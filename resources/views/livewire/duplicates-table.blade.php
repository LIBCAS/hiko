<div>
    <x-filter-form>
        <div>
            
            <span class="block text-sm">
                {{ __('hiko.order_direction') }}
            </span>
            <div class="flex flex-col">
                <x-radio name="compare" wire:model="filters.compare" label="Compare Full Texts" value="full_texts" wire:change="search" />
                <x-radio name="compare" wire:model="filters.compare" label="Filter by Meta Data" value="meta_data" wire:change="search" />
            </div>
        </div>

        <label>
            <span class="block text-sm">Similarity {{ number_format($filters['threshold'], 2) }}</span>
            <x-input type="range" min="0.5" max="1" step="0.1" wire:model="filters.threshold" class="text-primary accent-[#6d28d9]" wire:change="search" />
        </label>

        <label>
            <span class="block text-sm">Source database</span>
            <x-select wire:model="currentDatabase" class="w-full px-2 text-sm lg:w-40" disabled>
                @foreach($options as $value => $name)
                    <option value="{{ $value }}" {{ $currentDatabase == $value ? 'selected' : '' }}>{{ $name }}</option>
                @endforeach
            </x-select>
        </label>
        <label>
            <span class="block text-sm">Target database</span>
            <x-select wire:model="filters.database" class="w-full px-2 text-sm lg:w-40" wire:change="search">
                <option value="">---</option>
                @foreach($options as $value => $name)
                    @if($currentDatabase !== $value)
                        <option value="{{ $value ?? '' }}">{{ $name }}</option>
                    @endif
                @endforeach
            </x-select>
        </label>

        <label>
            <span class="block text-sm">Number of results</span>
            <x-input wire:model="filters.perPage" class="block w-full px-2 text-sm lg:w-32" type="text" wire:change="search" />
        </label>
    </x-filter-form>

    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {!! $pagination !!}
    </div>
</div>

@push('scripts')
    <script>
    Livewire.on('filtersChanged', filters => {
        updateExportUrl(filters);
    })
    </script>
@endpush
