<div>
    <x-filter-form>
        <label>
            <span class="block text-sm">
                {{ __('hiko.name') }}
            </span>
            <x-input wire:model.defer="filters.name" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.country') }}
            </span>
            <x-input wire:model.defer="filters.country" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
        <label>
            <span class="block text-sm">
                {{ __('hiko.note') }}
            </span>
            <x-input wire:model.defer="filters.note" class="block w-full px-2 text-sm lg:w-64" type="text" />
        </label>
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
