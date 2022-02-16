<div>
    <form wire:submit.prevent="search" wire:keydown.enter="search" class="w-full p-3 my-8 bg-gray-200 shadow-sm">
        <div class="flex flex-col flex-wrap gap-4 lg:items-end lg:flex-row">
            <label>
                <span class="block text-sm">
                    CS
                </span>
                <x-input wire:model.defer="filters.cs" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    EN
                </span>
                <x-input wire:model.defer="filters.en" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.order_by') }}
                </span>
                <x-select wire:model.defer="filters.order" class="w-full lg:w-64">
                    <option value="cs">
                        CS
                    </option>
                    <option value="en">
                        EN
                    </option>
                </x-select>
            </label>
            <x-button-simple type="button" wire:click="search" class="py-3">
                {{ __('hiko.search') }}
            </x-button-simple>
        </div>
    </form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
