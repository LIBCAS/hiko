<form wire:submit="search" wire:keydown.enter="search"
    class="flex flex-col flex-wrap w-full gap-4 p-3 my-8 rounded-lg bg-gray-200 shadow-sm items-center lg:flex-row">
    {{ $slot }}
    <x-button-simple type="button" wire:click="search" class="flex justify-center py-3 lg:justify-start">
        {{ __('hiko.search') }}
        <div wire:loading="search">
            <svg class="w-4 h-4 ml-3 text-white animate-spin" xmlns="http://www.w3.org/2000/svg" fill="none"
                viewBox="0 0 24 24">
                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                <path class="opacity-75" fill="currentColor"
                    d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z">
                </path>
            </svg>
        </div>
    </x-button-simple>
    <button type="button" wire:click="resetFilters" class="flex justify-center p-2 mt-4 lg:justify-start text-red-700 focus:border-red-700" title="{{__('hiko.clear_filters')}}">
        <x-icons.x-circle class="w-6 h-6" />
    </button>    <button wire:click="mergeAll"
    wire:loading.attr="disabled"
    wire:target="mergeAll"
    class="bg-blue-500 text-white px-4 py-2 rounded">
    Merge All Professions
</button>

<!-- Loading Animation -->
<div wire:loading wire:target="mergeAll" class="text-blue-500 font-bold">
    Merging in progress...
</div>

<!-- Success Message -->
@if (session()->has('success'))
    <div class="bg-green-100 text-green-700 p-2 rounded mt-2">
        {{ session('success') }}
    </div>
@endif

<!-- Error Message -->
@if (session()->has('error'))
    <div class="bg-red-100 text-red-700 p-2 rounded mt-2">
        {{ session('error') }}
    </div>
@endif


</form>
