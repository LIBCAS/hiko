<div>
    <fieldset id="a-copies" class="space-y-6" wire:loading.attr="disabled">
        <legend class="text-lg font-semibold">
            {{ __('hiko.manifestation_location') }}
        </legend>
        @foreach ($copies as $index => $item)
            <div class="p-3 space-y-6 bg-gray-200 shadow" wire:key="copy-item-{{ $index }}">
                <div>
                    <x-label for="preservation_{{ $index }}" :value="__('hiko.preservation')" />
                    <x-select wire:model.defer="copies.{{ $index }}.preservation"
                        name="copies[{{ $index }}][preservation]" id="preservation_{{ $index }}"
                        class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['preservation'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __('hiko.preservation_' . str_replace(' ', '_', $cv)) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="type_{{ $index }}" :value="__('hiko.doc_type')" />
                    <x-select wire:model.defer="copies.{{ $index }}.type" id="type_{{ $index }}"
                        name="copies[{{ $index }}][type]" class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['type'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __('hiko.' . str_replace(' ', '_', $cv)) }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="copy_{{ $index }}" :value="__('hiko.doc_mode')" />
                    <x-select wire:model.defer="copies.{{ $index }}.copy" id="doc_mode_{{ $index }}"
                        name="copies[{{ $index }}][copy]" class="block w-full mt-1">
                        <option value="">
                            ---
                        </option>
                        @foreach ($copyValues['copy'] as $cv)
                            <option value="{{ $cv }}">
                                {{ __("hiko.{$cv}") }}
                            </option>
                        @endforeach
                    </x-select>
                </div>
                <div>
                    <x-label for="manifestation_notes_{{ $index }}" :value="__('hiko.manifestation_notes')" />
                    <x-textarea wire:model.defer="copies.{{ $index }}.manifestation_notes"
                        name="copies[{{ $index }}][manifestation_notes]"
                        id="manifestation_notes_{{ $index }}" class="block w-full mt-1">
                    </x-textarea>
                </div>
                <div>
                    <x-label for="l_number_{{ $index }}" :value="__('hiko.l_number')" />
                    <x-input wire:model.defer="copies.{{ $index }}.l_number"
                        name="copies[{{ $index }}][l_number]" id="l_number_{{ $index }}"
                        class="block w-full mt-1" type="text" />
                </div>

                <div>
                    <x-label for="repository_{{ $index }}" :value="__('hiko.repository')" />
                    <div
                        x-data="enhancedSelect({
                            url: '{{ route('locations.repository.search') }}',
                            initialValue: '{{ $item['repository'] ?? '' }}',
                            initialLabel: '{{ $item['repository'] ?? '' }}',
                            index: {{ $index }},
                            fieldKey: 'repository'
                        })"
                        class="relative"
                        wire:ignore
                    >
                        <div class="relative w-full">
                            <input
                                type="hidden"
                                name="copies[{{ $index }}][repository]"
                                x-model="selectedValue"
                            >
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
                                 <button
                                    x-show="selectedLabel !== '' || searchQuery !== ''"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center p-2"
                                    x-on:click="clearSelection"
                                    style="top: 50%; transform: translateY(-50%);"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
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
                                <div x-show="loading" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.loading') }}
                                </div>
                                <div x-show="!loading && filteredOptions.length === 0" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.no_results') }}
                                </div>
                                <template x-for="(option, i) in filteredOptions" :key="option.value">
                                    <div
                                        x-on:click="selectOption(option)"
                                        x-on:mouseenter="highlightedIndex = i"
                                        :class="{ 'bg-primary text-white': highlightedIndex === i, 'text-gray-900': highlightedIndex !== i }"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary hover:text-white"
                                    >
                                        <span x-text="option.label" class="block truncate"></span>
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
                    </div>
                </div>

                <div>
                    <x-label for="archive_{{ $index }}" :value="__('hiko.archive')" />
                    <div
                        x-data="enhancedSelect({
                            url: '{{ route('locations.archive.search') }}',
                            initialValue: '{{ $item['archive'] ?? '' }}',
                            initialLabel: '{{ $item['archive'] ?? '' }}',
                            index: {{ $index }},
                            fieldKey: 'archive'
                        })"
                        class="relative"
                        wire:ignore
                    >
                        <div class="relative w-full">
                             <input
                                type="hidden"
                                name="copies[{{ $index }}][archive]"
                                x-model="selectedValue"
                            >
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
                                 <button
                                    x-show="selectedLabel !== '' || searchQuery !== ''"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center p-2"
                                    x-on:click="clearSelection"
                                    style="top: 50%; transform: translateY(-50%);"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
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
                                <div x-show="loading" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.loading') }}
                                </div>
                                <div x-show="!loading && filteredOptions.length === 0" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.no_results') }}
                                </div>
                                <template x-for="(option, i) in filteredOptions" :key="option.value">
                                    <div
                                        x-on:click="selectOption(option)"
                                        x-on:mouseenter="highlightedIndex = i"
                                        :class="{ 'bg-primary text-white': highlightedIndex === i, 'text-gray-900': highlightedIndex !== i }"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary hover:text-white"
                                    >
                                        <span x-text="option.label" class="block truncate"></span>
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
                    </div>
                </div>

                <div>
                    <x-label for="collection_{{ $index }}" :value="__('hiko.collection')" />
                    <div
                        x-data="enhancedSelect({
                            url: '{{ route('locations.collection.search') }}',
                            initialValue: '{{ $item['collection'] ?? '' }}',
                            initialLabel: '{{ $item['collection'] ?? '' }}',
                            index: {{ $index }},
                            fieldKey: 'collection'
                        })"
                        class="relative"
                        wire:ignore
                    >
                        <div class="relative w-full">
                             <input
                                type="hidden"
                                name="copies[{{ $index }}][collection]"
                                x-model="selectedValue"
                            >
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
                                 <button
                                    x-show="selectedLabel !== '' || searchQuery !== ''"
                                    type="button"
                                    class="absolute inset-y-0 right-0 flex items-center p-2"
                                    x-on:click="clearSelection"
                                    style="top: 50%; transform: translateY(-50%);"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                        <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                    </svg>
                                </button>
                            </div>
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
                                <div x-show="loading" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.loading') }}
                                </div>
                                <div x-show="!loading && filteredOptions.length === 0" class="p-2 text-center text-sm text-gray-500">
                                    {{ __('hiko.no_results') }}
                                </div>
                                <template x-for="(option, i) in filteredOptions" :key="option.value">
                                    <div
                                        x-on:click="selectOption(option)"
                                        x-on:mouseenter="highlightedIndex = i"
                                        :class="{ 'bg-primary text-white': highlightedIndex === i, 'text-gray-900': highlightedIndex !== i }"
                                        class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary hover:text-white"
                                    >
                                        <span x-text="option.label" class="block truncate"></span>
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
                    </div>
                </div>

                <div>
                    <x-label for="signature_{{ $index }}" :value="__('hiko.signature')" />
                    <x-input wire:model.defer="copies.{{ $index }}.signature"
                        name="copies[{{ $index }}][signature]" id="signature_{{ $index }}"
                        class="block w-full mt-1" type="text" />
                </div>
                <div>
                    <x-label for="location_note_{{ $index }}" :value="__('hiko.location_note')" />
                    <x-textarea wire:model.defer="copies.{{ $index }}.location_note"
                        name="copies[{{ $index }}][location_note]" id="location_note_{{ $index }}"
                        class="block w-full mt-1">
                    </x-textarea>
                </div>
                <x-button-trash wire:click="removeItem({{ $index }})" />
            </div>
        @endforeach
        <button wire:click="addItem" type="button" class="mb-3 text-sm font-bold text-primary hover:underline">
            {{ __('hiko.add_new_item') }}
        </button>
        <livewire:create-new-item-modal :route="route('locations.create')" :text="__('hiko.modal_new_location')" />
    </fieldset>
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

            // Watch selectedValue changes to update Livewire
            this.$watch('selectedValue', value => {
                if (value) {
                    // Construct data object based on fieldKey
                    let data = { value: this.selectedValue, label: this.selectedLabel };

                    if (fieldKey === 'repository') {
                        $wire.changeRepositoryValue(index, data);
                    } else if (fieldKey === 'archive') {
                        $wire.changeArchiveValue(index, data);
                    } else if (fieldKey === 'collection') {
                        $wire.changeCollectionValue(index, data);
                    }
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

            // Clear selected value in Livewire component
              if (fieldKey === 'repository') {
                 $wire.changeRepositoryValue(index, { value: '', label: '' });
             } else if (fieldKey === 'archive') {
                 $wire.changeArchiveValue(index, { value: '', label: '' });
             } else if (fieldKey === 'collection') {
                 $wire.changeCollectionValue(index, { value: '', label: '' });
             }

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
