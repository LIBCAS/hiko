<div x-data="{ showModal: false }">
    {{-- Trigger button - will be called from parent via Alpine event --}}
    <div
        @open-religion-modal.window="showModal = true"
        @close-modal.window="showModal = false; $wire.call('resetForm')"
        x-on:keydown.escape.window="if (showModal) { showModal = false; $wire.call('resetForm') }"></div>

    {{-- Modal Overlay --}}
    <div x-show="showModal" class="fixed inset-0 z-50 overflow-auto bg-gray-500 bg-opacity-75" x-cloak>
        <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
            {{-- Modal Panel --}}
            <div
                @click.outside="showModal = false; $wire.call('resetForm')"
                class="inline-block w-full max-w-3xl m-8 overflow-hidden text-left align-bottom transition-all transform bg-white rounded-lg shadow-xl align-middle">
                <form wire:submit.prevent="create">
                    {{-- Modal Header --}}
                    <div class="px-6 py-4 border-b border-gray-200">
                        <div class="flex items-center justify-between">
                            <h3 class="text-lg font-semibold text-gray-900">
                                {{ __('hiko.create_religion') }}
                            </h3>
                            <button
                                type="button"
                                @click="showModal = false; $wire.call('resetForm')"
                                class="text-gray-400 hover:text-gray-500 focus:outline-none">
                                <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12" />
                                </svg>
                            </button>
                        </div>
                    </div>

                    {{-- Modal Body --}}
                    <div class="px-6 py-4 space-y-4">
                        @error('general')
                        <div class="p-3 text-sm text-red-600 bg-red-50 rounded-md">
                            {{ $message }}
                        </div>
                        @enderror

                        {{-- Parent Religion Selector --}}
                        <div>
                            <label class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('hiko.parent_religion') }}
                                <span class="text-gray-500 font-normal">({{ __('hiko.optional') }})</span>
                            </label>
                            <div
                                x-data="parentReligionSelect()"
                                x-init="init()"
                                class="relative"
                                wire:ignore>
                                {{-- Hidden input to store the actual value --}}
                                <input
                                    type="hidden"
                                    wire:model="parentId"
                                    x-model="selectedValue">

                                {{-- Search Input --}}
                                <div class="relative">
                                    <input
                                        type="text"
                                        x-ref="searchInput"
                                        x-model="searchQuery"
                                        @click="openDropdown"
                                        @keydown.escape="isOpen = false"
                                        @keydown.arrow-down.prevent="highlightNext"
                                        @keydown.arrow-up.prevent="highlightPrev"
                                        @keydown.enter.prevent="selectHighlighted"
                                        class="block w-full rounded-md border-gray-300 py-2 pl-3 pr-10 text-sm focus:border-primary focus:ring-1 focus:ring-primary"
                                        :class="{'border-primary': isOpen}"
                                        placeholder="{{ __('hiko.search_or_leave_empty_for_root') }}"
                                        autocomplete="off">

                                    {{-- Clear button --}}
                                    <button
                                        x-show="selectedLabel !== '' || searchQuery !== ''"
                                        type="button"
                                        @click="clearSelection"
                                        class="absolute inset-y-0 right-0 flex items-center p-2"
                                        style="top: 50%; transform: translateY(-50%);">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-gray-400 hover:text-gray-700" viewBox="0 0 20 20" fill="currentColor">
                                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd" />
                                        </svg>
                                    </button>
                                </div>

                                {{-- Dropdown --}}
                                <div
                                    x-show="isOpen"
                                    x-transition:enter="transition ease-out duration-100"
                                    x-transition:enter-start="transform opacity-0 scale-95"
                                    x-transition:enter-end="transform opacity-100 scale-100"
                                    x-transition:leave="transition ease-in duration-75"
                                    x-transition:leave-start="transform opacity-100 scale-100"
                                    x-transition:leave-end="transform opacity-0 scale-95"
                                    @click.away="isOpen = false"
                                    class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-y-auto py-1 text-base ring-1 ring-black ring-opacity-5 focus:outline-none sm:text-sm">
                                    {{-- Loading indicator --}}
                                    <div x-show="loading" class="p-2 text-center text-sm text-gray-500">
                                        {{ __('hiko.loading') }}
                                    </div>

                                    {{-- No results message --}}
                                    <div x-show="!loading && filteredOptions.length === 0" class="p-2 text-center text-sm text-gray-500">
                                        {{ __('hiko.no_results') }}
                                    </div>

                                    {{-- Options --}}
                                    <template x-for="(option, i) in filteredOptions" :key="option.value">
                                        <div
                                            @click="selectOption(option)"
                                            @mouseenter="highlightedIndex = i"
                                            :class="{ 'bg-primary text-white': highlightedIndex === i, 'text-gray-900': highlightedIndex !== i }"
                                            class="cursor-pointer select-none relative py-2 pl-3 pr-9 hover:bg-primary hover:text-white">
                                            <span x-text="option.label" class="block truncate"></span>

                                            {{-- Selected check mark --}}
                                            <span
                                                x-show="option.value === selectedValue"
                                                :class="{ 'text-white': highlightedIndex === i, 'text-primary': highlightedIndex !== i }"
                                                class="absolute inset-y-0 right-0 flex items-center justify-center p-2"
                                                style="top: 50%; transform: translateY(-50%);">
                                                <svg class="h-5 w-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true">
                                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                                </svg>
                                            </span>
                                        </div>
                                    </template>
                                </div>
                            </div>
                            <p class="mt-1 text-xs text-gray-500">
                                {{ __('hiko.leave_empty_to_create_root_religion') }}
                            </p>
                        </div>

                        {{-- Czech Name --}}
                        <div class="required">
                            <label for="nameCzech" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('hiko.name_in_czech') }}
                            </label>
                            <input
                                type="text"
                                id="nameCzech"
                                wire:model="nameCzech"
                                class="block w-full rounded-md border-gray-300 py-2 px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary @error('nameCzech') border-red-500 @enderror"
                                placeholder="{{ __('hiko.enter_czech_name') }}">
                            @error('nameCzech')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>

                        {{-- English Name --}}
                        <div class="required">
                            <label for="nameEnglish" class="block text-sm font-medium text-gray-700 mb-1">
                                {{ __('hiko.name_in_english') }}
                            </label>
                            <input
                                type="text"
                                id="nameEnglish"
                                wire:model="nameEnglish"
                                class="block w-full rounded-md border-gray-300 py-2 px-3 text-sm focus:border-primary focus:ring-1 focus:ring-primary @error('nameEnglish') border-red-500 @enderror"
                                placeholder="{{ __('hiko.enter_english_name') }}">
                            @error('nameEnglish')
                            <div class="mt-1 text-sm text-red-600">{{ $message }}</div>
                            @enderror
                        </div>
                    </div>

                    {{-- Modal Footer --}}
                    <div class="px-6 py-4 border-t border-gray-200 bg-gray-50 flex justify-end space-x-3">
                        <button
                            type="button"
                            @click="showModal = false; $wire.call('resetForm')"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary">
                            {{ __('hiko.cancel') }}
                        </button>
                        <button
                            type="submit"
                            class="px-4 py-2 text-sm font-medium text-white bg-primary border border-transparent rounded-md hover:bg-black focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-primary disabled:opacity-50"
                            wire:loading.attr="disabled">
                            <span wire:loading.remove>{{ __('hiko.create') }}</span>
                            <span wire:loading>{{ __('hiko.creating') }}...</span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

@push('scripts')
<script>
    function parentReligionSelect() {
        return {
            searchQuery: '',
            selectedValue: @entangle('parentId'),
            selectedLabel: @entangle('parentLabel'),
            options: [],
            defaultOptions: [],
            filteredOptions: [],
            isOpen: false,
            loading: false,
            highlightedIndex: 0,
            debounceTimeout: null,

            init() {
                // Set initial display if parent was pre-selected
                if (this.selectedLabel) {
                    this.searchQuery = this.selectedLabel;
                }

                // Watch searchQuery changes for debounced search
                this.$watch('searchQuery', value => {
                    this.debouncedSearch(value);
                });

                // Watch for external changes to selectedLabel (from parent selection)
                this.$watch('selectedLabel', value => {
                    if (value && value !== this.searchQuery) {
                        this.searchQuery = value;
                    }
                });
            },

            // Helper function to normalize text for diacritic-insensitive comparison
            normalizeText(text) {
                return text.toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
            },

            // Open dropdown and fetch results
            openDropdown() {
                if (!this.isOpen) {
                    this.isOpen = true;

                    if (this.searchQuery.trim() === '') {
                        this.fetchOptions('');
                    } else {
                        this.debouncedSearch(this.searchQuery);
                    }
                }
            },

            debouncedSearch(query) {
                clearTimeout(this.debounceTimeout);

                this.debounceTimeout = setTimeout(() => {
                    this.fetchOptions(query);
                }, 300);
            },

            fetchOptions(query = '') {
                const queryIsEmpty = query.trim() === '';

                this.loading = true;

                if (queryIsEmpty && this.defaultOptions.length > 0) {
                    this.options = this.defaultOptions;
                    this.filteredOptions = this.defaultOptions;
                    this.isOpen = true;
                    this.loading = false;
                    this.highlightedIndex = 0;
                    return;
                }

                const url = '{{ route("ajax.religions") }}';
                const searchParam = !queryIsEmpty ? query : ' ';

                fetch(`${url}?search=${encodeURIComponent(searchParam)}`)
                    .then(response => response.json())
                    .then(data => {
                        this.options = data;
                        this.isOpen = true;
                        this.filteredOptions = data;
                        this.loading = false;
                        this.highlightedIndex = 0;

                        if (queryIsEmpty) {
                            this.defaultOptions = data;
                        }
                    })
                    .catch(error => {
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
                this.selectedValue = null;
                this.selectedLabel = '';
                this.searchQuery = '';
                this.$refs.searchInput.focus();

                this.openDropdown();
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
                this.$nextTick(() => {
                    const dropdown = this.$el.querySelector('[x-show="isOpen"]');
                    if (dropdown) {
                        const options = dropdown.querySelectorAll('[\\@click="selectOption(option)"]');
                        if (options[this.highlightedIndex]) {
                            options[this.highlightedIndex].scrollIntoView({
                                block: 'nearest'
                            });
                        }
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
