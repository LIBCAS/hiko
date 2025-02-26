<div>
    <fieldset class="space-y-3" wire:loading.attr="disabled">
        @foreach ($items as $item)
            <div wire:key="item-{{ $item['id'] }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div class="required">
                    <x-label :for="$fieldKey . '-' . $loop->index" :value="$label" />
                    <div
                        x-data="ajaxChoices({
                            url: '{{ route($route) }}',
                            element: document.getElementById('{{ $fieldKey . '-' . $loop->index }}'),
                            change: (data) => { $dispatch('item-value-changed', { index: {{ $loop->index }}, data: data }); }
                        })"
                        x-init="initSelect()"
                        wire:ignore
                    >
                        <x-select
                            wire:model.debounce.300ms="items.{{ $loop->index }}.value"
                            class="block w-full mt-1"
                            name="{{ $fieldKey }}[{{ $loop->index }}][value]"
                            :id="$fieldKey . '-' . $loop->index"
                        >
                            @if (!empty($item['value']))
                                <option value="{{ is_array($item['value']) ? json_encode($item['value']) : $item['value'] }}">
                                    {{ is_array($item['label']) ? implode(', ', $item['label']) : $item['label'] }}
                                </option>
                            @endif
                        </x-select>
                    </div>
                </div>
                <div>
                    @foreach ($fields as $field)
                        <div>
                            <x-label
                                for="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}"
                                value="{{ $field['label'] }}"
                            />
                            <x-input
                                wire:model="items.{{ $loop->parent->index }}.{{ $field['key'] }}"
                                name="{{ $fieldKey }}[{{ $loop->parent->index }}][{{ $field['key'] }}]"
                                id="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}"
                                class="block w-full mt-1"
                                type="text"
                            />
                        </div>
                    @endforeach
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button
            wire:click="addItem"
            type="button"
            class="mb-3 text-sm font-bold text-primary hover:underline"
            wire:loading.attr="disabled"
            wire:target="addItem"
        >
            {{ __('hiko.add_new_item') }}
        </button>
    </fieldset>
</div>

@push('scripts')
<script>
    function ajaxChoices({ url, element, change }) {
        return {
            initSelect() {
                const select = this.$el;
                if (select.dataset.choicesInitialized) return;
                select.dataset.choicesInitialized = true;

                const loadOptions = (query = '') => {
                    fetch(`${url}?search=${query}`)
                        .then(response => response.json())
                        .then(data => {
                            select.innerHTML = '';
                            data.forEach(option => {
                                const optionElement = document.createElement('option');
                                optionElement.value = option.value;
                                optionElement.textContent = option.label;
                                select.appendChild(optionElement);
                            });
                        });
                };

                loadOptions();

                const debounce = (func, delay) => {
                    let timeout;
                    return function(...args) {
                        clearTimeout(timeout);
                        timeout = setTimeout(() => func.apply(this, args), delay);
                    };
                };

                select.addEventListener('input', debounce((event) => {
                    loadOptions(event.target.value);
                }, 300));

                select.addEventListener('change', (event) => {
                    const selectedOption = event.target.options[event.target.selectedIndex];
                    change({
                        value: selectedOption.value,
                        label: selectedOption.text
                    });
                });
            }
        };
    }

    document.addEventListener('livewire:load', () => {
        window.addEventListener('reinitialize-ajax-choices', () => {
            const elements = document.querySelectorAll('[x-data="ajaxChoices"]');
            elements.forEach(el => {
                Alpine.initTree(el);
            });
        });
    });
</script>
@endpush
