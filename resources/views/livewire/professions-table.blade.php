<div>
    <x-filter-form>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mt-4">
            <label>
                <span class="block text-sm">CS</span>
                <x-input wire:model.live.debounce.1000ms="filters.cs" class="block w-full px-2 text-sm" type="text" />
            </label>

            <label>
                <span class="block text-sm">EN</span>
                <x-input wire:model.live.debounce.1000ms="filters.en" class="block w-full px-2 text-sm" type="text" />
            </label>

            <label>
                <span class="block text-sm">{{ __('hiko.source') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.source" class="block w-full px-2 text-sm">
                    <option value="all">*</option>
                    <option value="local">{{ __('hiko.local') }}</option>
                    <option value="global">{{ __('hiko.global') }}</option>
                </x-select>
            </label>

            <label>
                <span class="block text-sm">{{ __('hiko.order_by') }}</span>
                <x-select wire:model.live.debounce.1000ms="filters.order" class="block w-full px-2 text-sm">
                    <option value="cs">CS</option>
                    <option value="en">EN</option>
                </x-select>
            </label>
        </div>
    </x-filter-form>

    @if(!empty($tableData['rows']))
        <div class="overflow-x-auto -mx-4 sm:mx-0">
            <x-table :tableData="$tableData" class="table-auto w-full mt-3" />
        </div>

        <div class="w-full pl-1 mt-3">
            {{ $pagination->links() }}
        </div>
    @else
        <div class="mt-4">
            <p class="text-gray-700">{{ __('hiko.compare_no_results') }}</p>
        </div>
    @endif
</div>
