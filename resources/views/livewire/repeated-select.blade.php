<div>
    <div class="p-3 space-y-3 bg-gray-200 rounded-md shadow" wire:loading.attr="disabled">
        <p>
            {{ $fieldLabel }}
        </p>
        @error($fieldKey)
            <div class="text-red-600">{{ $message }}</div>
        @enderror
        @foreach ($items as $index => $item)
            <div wire:key="item-{{ $index }}" class="flex">
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
                    class="ml-6 text-red-600"
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
                    // Initialize Select2 or any other JS library if needed
                    // For simplicity, using basic AJAX fetch

                    // Fetch initial options if not already loaded
                    if (!select.hasAttribute('data-initialized')) {
                        fetch(url + '?search=')
                            .then(response => response.json())
                            .then(data => {
                                data.forEach(option => {
                                    const optionElement = document.createElement('option');
                                    optionElement.value = option.value;
                                    optionElement.innerHTML = option.label;
                                    select.appendChild(optionElement);
                                });
                                select.setAttribute('data-initialized', 'true');
                            });
                    }

                    // Listen for changes
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
