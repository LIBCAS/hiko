<div x-data class="flex flex-col">
    <select x-ref="select" name="{{ $name }}"
        class="block m-1 text-sm leading-4 border-gray-300 rounded-md shadow-sm focus:border-blue-300 focus:ring focus:ring-blue-200 focus:ring-opacity-50"
        wire:input="doBooleanFilter('{{ $index }}', $event.target.value)" x-on:input="$refs.select.value=''">
        <option value=""></option>
        <option value="0">{{ __('Ne') }}</option>
        <option value="1">{{ __('Ano') }}</option>
    </select>

    <div class="flex flex-wrap space-x-1 max-w-48">
        @isset($this->activeBooleanFilters[$index])
            @if ($this->activeBooleanFilters[$index] == 1)
                <button wire:click="removeBooleanFilter('{{ $index }}')"
                    class="flex items-center pl-1 m-1 space-x-1 text-xs tracking-wide text-white uppercase bg-gray-300 rounded-full hover:bg-red-600 focus:outline-none">
                    <span>{{ __('Ano') }}</span>
                    <x-icons.x-circle />
                </button>
            @elseif(strlen($this->activeBooleanFilters[$index]) > 0)
                <button wire:click="removeBooleanFilter('{{ $index }}')"
                    class="flex items-center pl-1 m-1 space-x-1 text-xs tracking-wide text-white uppercase bg-gray-300 rounded-full hover:bg-red-600 focus:outline-none">
                    <span>{{ __('Ne') }}</span>
                    <x-icons.x-circle />
                </button>
            @endif
        @endisset
    </div>
</div>
