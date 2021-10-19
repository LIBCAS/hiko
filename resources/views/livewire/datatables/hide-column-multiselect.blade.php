<div x-data="{ show: false }" class="flex flex-col items-center">
    <div class="relative flex flex-col items-center">
        <button x-on:click="show = !show" class="px-3 py-2 text-xs font-medium leading-4 tracking-wider text-blue-500 uppercase bg-white border border-blue-400 rounded-md hover:bg-blue-200 focus:outline-none">
            <div class="flex items-center h-5">
                {{ __('Skrýt / zobrazit sloupce') }}
            </div>
        </button>
        <div x-show="show" x-on:click.away="show = false" class="absolute right-0 z-50 mt-16 -mr-4 overflow-y-auto bg-white rounded shadow-2xl top-100 w-96 max-h-select" x-cloak>
            <div class="flex flex-col w-full">
                @foreach($this->columns as $index => $column)
                <div>
                    <div class="@unless($column['hidden']) hidden @endif cursor-pointer w-full border-gray-800 border-b bg-gray-700 text-gray-500 hover:bg-blue-600 hover:text-white" wire:click="toggle({{$index}})">
                        <div class="relative flex items-center w-full p-2 group">
                            <div class="flex items-center w-full ">
                                <div class="mx-2 leading-6">{{ $column['label'] }}</div>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <x-icons.check-circle class="w-3 h-3 text-gray-700 stroke-current" />
                            </div>
                        </div>
                    </div>
                    <div class="@if($column['hidden']) hidden @endif cursor-pointer w-full border-gray-800 border-b bg-gray-700 text-white hover:bg-red-600" wire:click="toggle({{$index}})">
                        <div class="relative flex items-center w-full p-2 group">
                            <div class="flex items-center w-full ">
                                <div class="mx-2 leading-6">{{ $column['label'] }}</div>
                            </div>
                            <div class="absolute inset-y-0 right-0 flex items-center pr-2">
                                <x-icons.x-circle class="w-3 h-3 text-gray-700 stroke-current" />
                            </div>
                        </div>
                    </div>
                </div>
                @endforeach
            </div>
        </div>
    </div>
</div>

<style>
    .top-100 {
        top: 100%
    }

    .bottom-100 {
        bottom: 100%
    }

    .max-h-select {
        max-height: 300px;
    }

</style>
