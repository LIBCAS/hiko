<div>
    <div class="p-3 space-y-3 bg-gray-200 rounded-md shadow" wire:loading.attr="disabled">
        <p class="font-semibold">{{ $fieldLabel }}</p>
        
        @error($fieldKey)
            <div class="text-red-600">{{ $message }}</div>
        @enderror

        @foreach ($items as $index => $item)
            <div wire:key="item-{{ $index }}" class="flex items-center space-x-3">
                <x-select 
                    name="{{ $fieldKey }}[]" 
                    class="block w-full mt-1" 
                    aria-label="{{ $fieldLabel }}"
                    x-data="ajaxChoices({ 
                        url: '{{ route($route) }}', 
                        element: $el, 
                        change: (data) => { $wire.changeItemValue({{ $index }}, data) } 
                    })"
                    x-init="initSelect()">
                    
                    @if (!empty($item['value']))
                        <option value="{{ $item['value'] }}" selected>{{ $item['label'] }}</option>
                    @endif
                </x-select>

                <button 
                    wire:click="removeItem({{ $index }})" 
                    type="button" 
                    class="text-red-600" 
                    aria-label="{{ __('hiko.remove_item') }}" 
                    title="{{ __('hiko.remove_item') }}">
                    <x-icons.remove class="h-5" />
                </button>
            </div>
        @endforeach

        <button 
            wire:click="addItem" 
            type="button" 
            class="text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
    </div>
</div>

@push('scripts')
    <script>
        function ajaxChoices({ url, element, change }) {
            return {
                initSelect() {
                    const select = this.$el;

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

                    // Add change listener
                    select.addEventListener('change', (event) => {
                        const selectedOption = event.target.options[event.target.selectedIndex];
                        change({
                            value: selectedOption.value,
                            label: selectedOption.text
                        });
                    });

                    // Optional: Search filter for dynamic loading
                    select.addEventListener('input', (event) => {
                        const query = event.target.value;
                        loadOptions(query);
                    });
                }
            }
        }
    </script>
@endpush
