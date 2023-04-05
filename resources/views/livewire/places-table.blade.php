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
    </x-filter-form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
