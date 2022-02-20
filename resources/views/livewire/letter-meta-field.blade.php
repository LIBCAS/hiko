<div>
    <fieldset class="space-y-3" wire:loading.attr="disabled">
        @foreach ($items as $item)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div class="required">
                    <x-label :for="$fieldKey . '-' . $loop->index" :value="$label" />
                    <div wire:ignore.self wire:key="{{ 'select-' . $loop->index }}">
                        <x-select wire:model.lazy="items.{{ $loop->index }}.value"
                            x-data="ajaxChoices({url: '{{ route($route) }}', element: $el, change: (data) => { $wire.changeItemValue({{ $loop->index }}, data) } })"
                            class="block w-full mt-1" :id="$fieldKey .'-'.$loop->index" x-init="initSelect()">
                            @if (!empty($item['value']))
                                <option value="{{ $item['value'] }}">{{ $item['label'] }}</option>
                            @endif
                        </x-select>
                    </div>
                </div>
                <div>
                    @foreach ($fields as $field)
                        <div>
                            <x-label for="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}"
                                value="{{ $field['label'] }}" />
                            <x-input wire:model.lazy="items.{{ $loop->parent->index }}.{{ $field['key'] }}"
                                id="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}"
                                class="block w-full mt-1" type="text" />
                        </div>
                    @endforeach
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
        <input type="hidden" name="{{ $fieldKey }}" value="{!! htmlspecialchars(json_encode($items), ENT_QUOTES, 'UTF-8') !!}">
    </fieldset>
</div>
