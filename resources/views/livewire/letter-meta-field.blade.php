<div>
    <fieldset class="space-y-3" wire:loading.attr="disabled">
        @foreach ($items as $item)
            <div wire:key="{{ $loop->index }}" class="p-3 space-y-6 bg-gray-200 shadow">
                <div class="required">
                    <x-label :for="$fieldKey . '-' . $loop->index" :value="$item['label'] ?? 'No Label'" />
                    <div 
                        x-data="ajaxChoices({
                            url: '{{ route($route) }}',
                            element: document.getElementById('{{ $fieldKey . '-' . $loop->index }}'),
                            change: (data) => { $wire.changeItemValue({{ $loop->index }}, data) }
                        })" 
                        x-init="initSelect()" 
                        wire:ignore
                        wire:key="select-{{ $loop->index }}">
                        
                        <x-select 
                            wire:model="items.{{ $loop->index }}.value" 
                            class="block w-full mt-1"
                            name="{{ $fieldKey }}[{{ $loop->index }}][value]" 
                            :id="$fieldKey . '-' . $loop->index">
                            @if (!empty($item['value']))
                                <option value="{{ $item['value'] }}">{{ $item['label'] ?? 'No Label' }}</option>
                            @endif
                        </x-select>
                        
                        @if (strpos(route($route), 'identity') !== false)
                            <livewire:create-new-item-modal 
                                :route="route('identities.create')" 
                                :text="__('hiko.modal_new_identity')" />
                        @endif
                    </div>
                </div>
                <div>
                    @foreach ($fields as $field)
                        <div>
                            <x-label 
                                for="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}" 
                                value="{{ $field['label'] }}" />
                            <x-input 
                                wire:model="items.{{ $loop->parent->index }}.{{ $field['key'] }}"
                                name="{{ $fieldKey }}[{{ $loop->parent->index }}][{{ $field['key'] }}]"
                                id="{{ $fieldKey }}-{{ $field['key'] . '-' . $loop->parent->index }}"
                                class="block w-full mt-1" 
                                type="text" />
                        </div>
                    @endforeach
                </div>
                <x-button-trash wire:click="removeItem({{ $loop->index }})" />
            </div>
        @endforeach
        <button 
            wire:click="addItem" 
            type="button" 
            class="mb-3 text-sm font-bold text-primary hover:underline">
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
                    
                    // Prevent multiple initializations
                    if (select.dataset.choicesInitialized) return;
                    select.dataset.choicesInitialized = true;

                    // Load options initially or on search
                    const loadOptions = (query = '') => {
                        fetch(`${url}?search=${query}`)
                            .then(response => response.json())
                            .then(data => {
                                select.innerHTML = ''; // Clear current options
                                
                                // Populate new options
                                data.forEach(option => {
                                    const optionElement = document.createElement('option');
                                    optionElement.value = option.value;
                                    optionElement.textContent = option.label;
                                    select.appendChild(optionElement);
                                });
                            });
                    };

                    // Initialize options on load
                    loadOptions();

                    // Debounce function for search filtering
                    const debounce = (func, delay) => {
                        let timeout;
                        return function(...args) {
                            clearTimeout(timeout);
                            timeout = setTimeout(() => func.apply(this, args), delay);
                        };
                    };

                    // Event listener for input to filter dynamically
                    select.addEventListener('input', debounce((event) => {
                        const query = event.target.value;
                        loadOptions(query);
                    }, 300));

                    // Add change listener
                    select.addEventListener('change', (event) => {
                        const selectedOption = event.target.options[event.target.selectedIndex];
                        change({
                            value: selectedOption.value,
                            label: selectedOption.text
                        });
                    });
                }
            }
        }
    </script>
@endpush
