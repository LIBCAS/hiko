<div>
    <div class="p-3 space-y-3 bg-gray-200 rounded-md shadow" wire:loading.attr="disabled">
        <p>
            {{ $fieldLabel }}
        </p>
        @error($fieldKey)
            <div class="text-red-600">{{ $message }}</div>
        @enderror
        @foreach ($items as $item)
            <div wire:key="{{ $loop->index }}" class="flex">
                <x-select name="{{ $fieldKey }}[]" class="block w-full mt-1" aria-label="{{ $fieldLabel }}"
                    x-data="ajaxChoices({url: '{{ route($route) }}', element: $el, change: (data) => { $wire.changeItemValue({{ $loop->index }}, data) } })"
                    x-init="initSelect()">
                    @if (!empty($item['value']))
                        <option value="{{ $item['value'] }}" selected>{{ $item['label'] }} @isset($categories[$item['value']]) ({{ $categories[$item['value']] }}) @endisset</option>
                    @endif
                </x-select>
                <button wire:click="removeItem({{ $loop->index }})" type="button" class="ml-6 text-red-600"
                    aria-label="{{ __('hiko.remove_item') }}" title="{{ __('hiko.remove_item') }}">
                    <x-icons.remove class="h-5" />
                </button>
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </div>
</div>
