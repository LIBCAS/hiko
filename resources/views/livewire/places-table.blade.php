<div>
    <form wire:submit.prevent="search" wire:keydown.enter="search" class="w-full p-3 my-8 bg-gray-200 shadow-sm">
        <div class="flex flex-col flex-wrap gap-4 lg:items-end lg:flex-row">
            <label>
                <span class="block text-sm">
                    {{ __('hiko.name') }}
                </span>
                <x-input wire:model.defer="filters.name" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.country') }}
                </span>
                <x-input wire:model.defer="filters.country" class="block w-full lg:w-64" type="text" />
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
