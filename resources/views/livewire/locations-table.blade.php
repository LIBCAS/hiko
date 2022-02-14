<div>
    <form wire:submit.prevent="search" class="w-full p-3 my-8 bg-gray-200 shadow-sm">
        <div class="flex flex-col gap-4 lg:flex-row">
            <label>
                <span class="block text-sm">
                    {{ __('hiko.name') }}
                </span>
                <x-input wire:model.defer="filters.name" class="block w-full lg:w-64" type="text" />
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.type') }}
                </span>
                <x-select wire:model.defer="filters.type" class="w-full lg:w-64">
                    <option value="">---</option>
                    @foreach ($types as $type)
                        <option value="{{ $type }}">
                            {{ __("hiko.{$type}") }}
                        </option>
                    @endforeach
                </x-select>
            </label>
            <label>
                <span class="block text-sm">
                    {{ __('hiko.order_by') }}
                </span>
                <x-select wire:model.defer="filters.order" class="w-full lg:w-64">
                    <option value="type">
                        {{ __('hiko.type') }}
                    </option>
                    <option value="name">
                        {{ __('hiko.name') }}
                    </option>
                </x-select>
            </label>
            <x-button-simple type="button" wire:click="search" class="mt-4">
                {{ __('hiko.search') }}
            </x-button-simple>
        </div>
    </form>
    <x-table :tableData="$tableData" />
    <div class="w-full pl-1 mt-3">
        {{ $pagination->links() }}
    </div>
</div>
