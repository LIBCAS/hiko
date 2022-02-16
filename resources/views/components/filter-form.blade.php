<form wire:submit.prevent="search" wire:keydown.enter="search"
    class="flex flex-col flex-wrap w-full gap-4 p-3 my-8 bg-gray-200 shadow-sm lg:items-end lg:flex-row">
    {{ $slot }}
    <x-button-simple type="button" wire:click="search" class="py-3">
        {{ __('hiko.search') }}
    </x-button-simple>
</form>
