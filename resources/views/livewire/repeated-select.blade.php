<div>
    <div class="p-4 space-y-4 bg-white rounded-lg shadow-md border border-gray-200" wire:loading.class="opacity-75">
        <h3 class="text-lg font-semibold">{{ $fieldLabel }}</h3>
        
        @error($fieldKey)
            <div class="text-red-600 text-sm rounded-md bg-red-50 p-2">{{ $message }}</div>
        @enderror

        @if (!empty($items))
        <div>
            @foreach ($items as $index => $item)
                <div wire:key="item-{{ $index }}" class="relative">
                    <div 
                        x-data="enhancedSelect({
                            url: '{{ route($route) }}',
                            initialValue: '{{ $item['value'] ?? '' }}',
                            initialLabel: '{{ $item['label'] ?? '' }}',
                            index: {{ $index }},
                            fieldKey: '{{ $fieldKey }}'
                        })"
                        class="relative"
                        wire:ignore
                    >
                        <div class="flex items-center space-x-2">
                            <div class="relative w-full">
                                <!-- Hidden input to store the actual value -->
                                <input 
                                    type="hidden" 
                                    name="{{ $fieldKey }}[]" 
                                    x-model="selectedValue"
                                >
                                
                                <!-- Text input for searching -->
                                <div class="relative">
                                    <input
                                        type="text"
                                        x-ref="searchInput"
                                        x-model="searchQuery"
                                        x-on:focus="openDropdown"
                                        x-on:input="openDropdown"
                                        x-on:blur="closeDropdown"
                                        x-on:keydown.arrow-down.prevent="highlightNext"
                                        x-on:keydown.arrow-up.prevent="highlightPrev"
                                        x-on:keydown.enter.prevent="selectHighlighted"
                                        x-on:keydown.escape="isOpen = false"
                                        class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"
                                        :class="{'border-primary': isOpen}"
                                        placeholder="Search..."
                                        autocomplete="off"
                                    >
                                    
                                    <!-- Clear button (only shown when there's input) -->
                                    <button 
                                        x-show="selectedLabel !== '' || searchQuery !== ''" 
                                        type="button" 
                                        class="absolute inset-y-0 right-0 flex items-center p-2"
                                        x-on:click="clearSelection"
                                        style="top: 50%; transform: translateY(-50%);"
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>
                                
                                <!-- Dropdown menu -->
                                <div 
                                    x-show="isOpen" 
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-y-auto py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm"
                                    @click.away="isOpen = false"
                                >
                                    <!-- Loading indicator -->
                                    <div x-show="loading" class="p-2 text-center text-sm text-gray-500">
                                        {{ __('hiko.loading') }}
                                    </div>
                                    
                                    <!-- No results message -->
                                    <div x-show="!loading && filteredOptions.length === 0" class="p-2 text-center text-sm text-gray-500">
                                        {{ __('hiko.no_results') }}
                                    </div>
                                    
                                    <!-- Options -->
                                    <template x-for="(option, i) in filteredOptions" :key="option.value">
                                        <div
                                            x-on:click="selectOption(option)"
                                            x-on:mouseenter="highlightedIndex = i"
                                            :class="{ 'bg-primary text-white': highlightedIndex === i, 'text-gray-900': highlightedIndex !== i }"
                                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary hover:text-white"
                                        >
                                            <span x-text="option.label" class="block truncate"></span>
                                            
                                            <!-- Selected check mark -->
                                            <span 
                                                x-show="option.value === selectedValue"
                                                :class="{ 'text-white': highlightedIndex === i, 'text-primary': highlightedIndex !== i }"
                                                class="absolute inset-y-0 right-0 flex items-center justify-center p-2"
                                                style="top: 50%; transform: translateY(-50%);"
                                            >
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            
                            <!-- Remove button -->
                            <button 
                                wire:click="removeItem({{ $index }})" 
                                type="button" 
                                class="flex-shrink-0 text-red-500 hover:text-red-700 transition-colors duration-150 flex items-center" 
                                aria-label="{{ __('hiko.remove_item') }}" 
                                title="{{ __('hiko.remove_item') }}">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 20 20" fill="currentColor">
                                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM7 9a1 1 0 000 2h6a1 1 0 100-2H7z" clip-rule="evenodd" />
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>
            @endforeach
        </div>
        @endif

        <button 
            wire:click="addItem" 
            type="button" 
            class="inline-flex items-center px-4 py-2 border border-transparent text-sm font-medium rounded-full shadow-sm text-white bg-primary hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
            <svg class="h-4 w-4 mr-1" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor">
                <path fill-rule="evenodd" d="M10 3a1 1 0 011 1v5h5a1 1 0 110 2h-5v5a1 1 0 11-2 0v-5H4a1 1 0 110-2h5V4a1 1 0 011-1z" clip-rule="evenodd" />
            </svg>
            {{ __('hiko.add_new_item') }}
        </button>
    </div>
</div>

@push('scripts')
<script>
function enhancedSelect({ url, initialValue = '', initialLabel = '', index, fieldKey }) {
    return {
        searchQuery: initialLabel,
        selectedValue: initialValue,
        selectedLabel: initialLabel,
        options: [],
        filteredOptions: [],
        isOpen: false,
        loading: false,
        highlightedIndex: 0,
        debounceTimeout: null,
        
        init() {
            // Initial load of options
            this.fetchOptions();
            
            // Set initial value if present
            if (this.selectedValue && this.selectedLabel) {
                this.searchQuery = this.selectedLabel;
            }
            
            // Update Livewire component when selection changes
            this.$watch('selectedValue', value => {
                if (value) {
                    $wire.changeItemValue(index, {
                        value: this.selectedValue,
                        label: this.selectedLabel
                    });
                }
            });
            
            // Watch searchQuery changes for debounced search
            this.$watch('searchQuery', value => {
                this.debouncedSearch(value);
                
                // Keep dropdown open when typing
                if (value !== '') {
                    this.isOpen = true;
                }
            });
        },
        
        // Helper function to normalize text for diacritic-insensitive comparison
        normalizeText(text) {
            return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
        },
        
        // Open dropdown and fetch results
        openDropdown() {
            this.isOpen = true;
            this.debouncedSearch(this.searchQuery);
        },
        
        debouncedSearch(query) {
            // Clear any existing timeout
            clearTimeout(this.debounceTimeout);
            
            // Set a new timeout
            this.debounceTimeout = setTimeout(() => {
                // Always fetch from server to get latest data
                this.fetchOptions(query);
            }, 200); // Reduced debounce time for more responsive feel
        },
        
        fetchOptions(query = '') {
            this.loading = true;
            
            fetch(`${url}?search=${encodeURIComponent(query)}`)
                .then(response => response.json())
                .then(data => {
                    this.options = data;
                    
                    // If there's a query, also filter client-side for normalized diacritic matching
                    if (query && query.trim() !== '') {
                        const normalizedQuery = this.normalizeText(query);
                        
                        // Keep server results but also do client-side filtering for diacritics
                        // This allows server to do its own filtering, but we enhance it
                        this.filteredOptions = data.filter(option => 
                            this.normalizeText(option.label).includes(normalizedQuery)
                        );
                    } else {
                        this.filteredOptions = data;
                    }
                    
                    this.loading = false;
                    
                    // Reset highlighted index
                    this.highlightedIndex = 0;
                })
                .catch(error => {
                    console.error('Error fetching options:', error);
                    this.loading = false;
                });
        },
        
        selectOption(option) {
            this.selectedValue = option.value;
            this.selectedLabel = option.label;
            this.searchQuery = option.label;
            this.isOpen = false;
        },
        
        clearSelection() {
            this.selectedValue = '';
            this.selectedLabel = '';
            this.searchQuery = '';
            this.$refs.searchInput.focus();
            
            // Update Livewire component
            $wire.changeItemValue(index, {
                value: '',
                label: ''
            });
            
            // Open dropdown to show all options
            this.openDropdown();
        },
        
        closeDropdown() {
            // Delay closing to allow option selection to complete
            setTimeout(() => {
                this.isOpen = false;
                
                // If there's a selected value, make sure the search input shows the label
                if (this.selectedValue && this.selectedLabel) {
                    this.searchQuery = this.selectedLabel;
                }
                // If there's no selection but user typed something, clear it if it doesn't match any option
                else if (this.searchQuery && this.searchQuery !== this.selectedLabel) {
                    const normalizedQuery = this.normalizeText(this.searchQuery);
                    const matchedOption = this.options.find(option => 
                        this.normalizeText(option.label) === normalizedQuery
                    );
                    
                    if (matchedOption) {
                        // Auto-select if there's an exact match (normalized)
                        this.selectOption(matchedOption);
                    } else {
                        // Clear if no match
                        this.searchQuery = '';
                    }
                }
            }, 150);
        },
        
        highlightNext() {
            if (this.highlightedIndex < this.filteredOptions.length - 1) {
                this.highlightedIndex++;
                this.scrollToHighlighted();
            }
        },
        
        highlightPrev() {
            if (this.highlightedIndex > 0) {
                this.highlightedIndex--;
                this.scrollToHighlighted();
            }
        },
        
        scrollToHighlighted() {
            // Ensure the highlighted option is visible in the scrollable dropdown
            this.$nextTick(() => {
                const highlightedEl = this.$el.querySelector(`[x-on\\:mouseenter="highlightedIndex = ${this.highlightedIndex}"]`);
                if (highlightedEl) {
                    highlightedEl.scrollIntoView({ block: 'nearest' });
                }
            });
        },
        
        selectHighlighted() {
            if (this.filteredOptions.length > 0 && this.highlightedIndex >= 0) {
                this.selectOption(this.filteredOptions[this.highlightedIndex]);
            }
        }
    };
}
</script>
@endpush
