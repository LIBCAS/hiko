<div>
    <x-filter-form>
        <div class="flex flex-wrap gap-4">
            <label>
                <span class="block text-sm">
                    {{ __('hiko.name') }}
                </span>
                <x-input wire:model.live.debounce.1000ms="filters.name" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.country') }}
                </span>
                <x-input wire:model.live.debounce.1000ms="filters.country" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.note') }}
                </span>
                <x-input wire:model.live.debounce.1000ms="filters.note" class="block w-full px-2 text-sm" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.source') }}
                </span>
                <x-select wire:model.live.debounce.1000ms="filters.source" class="block w-full px-2 text-sm">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="local">{{ __('hiko.local') }}</option>
                    <option value="global">{{ __('hiko.global') }}</option>
                </x-select>
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.has_geoname_id') }}
                </span>
                <x-select wire:model.live.debounce.1000ms="filters.has_geoname" class="block w-full px-2 text-sm">
                    <option value="all">{{ __('hiko.all') }}</option>
                    <option value="yes">{{ __('hiko.yes') }}</option>
                    <option value="no">{{ __('hiko.no') }}</option>
                </x-select>
            </label>
        </div>
    </x-filter-form>
    @if(!empty($tableData['rows']))
        <x-table :tableData="$tableData" class="table-auto w-full mt-3" />
        <div class="w-full pl-1 mt-3">
            {{ $pagination->links() }}
        </div>
    @else
        <div class="mt-4">
            <p class="text-gray-700">{{ __('hiko.compare_no_results') }}</p>
        </div>
    @endif
</div>
