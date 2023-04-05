<div>
    <fieldset class="space-y-3" wire:loading.attr="disabled">
        @foreach ($items as $item)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div class="required">
                    <x-label :for="$fieldKey . '-' . $loop->index" :value="$label" />
                    <div x-data="ajaxChoices({ url: '{{ route($route) }}', element: document.getElementById('{{ $fieldKey . '-' . $loop->index }}'), change: (data) => { $wire.changeItemValue({{ $loop->index }}, data) } })" x-init="initSelect();
                    window.livewire.on('itemChanged', () => { initSelect() })" wire:key="{{ 'select-' . $loop->index }}">
                        <x-select wire:model.defer="items.{{ $loop->index }}.value" class="block w-full mt-1"
                            name="{{ $fieldKey }}[{{ $loop->index }}][value] }}]" :id="$fieldKey . '-' . $loop->index">
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
                            <x-input wire:model.defer="items.{{ $loop->parent->index }}.{{ $field['key'] }}"
                                name="{{ $fieldKey }}[{{ $loop->parent->index }}][{{ $field['key'] }}]"
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
    </fieldset>
</div>
