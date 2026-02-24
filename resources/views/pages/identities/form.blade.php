<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />
    @if (!empty($identity->id))
        <x-page-lock
            scope="tenant"
            resource-type="identity_edit"
            :resource-id="$identity->id"
            :redirect-url="route('identities')"
            :read-only-on-deny="true" />
    @endif

    <form x-data="identityForm({
        type: '{{ $identity->type ? $identity->type : 'person' }}',
        similarNamesUrl: '{{ route('ajax.identities.similar') }}',
        id: '{{ $identity->id }}',
        surname: '{{ $identity->surname }}',
        name: '{{ $identity->name }}',
        forename: '{{ $identity->forename }}'
    })" x-init="$watch('fullName', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-lg space-y-6">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <livewire:identity-form-switcher
            :types="$types"
            :identityType="$selectedType"
            :identity="$identity"
            :selectedProfessions="$selectedProfessions"
            :selectedCategories="$selectedCategories"
            :selectedReligions="$selectedReligions" />

        <div x-data="globalIdentityLinkSelect({
            searchUrl: '{{ route('ajax.global.identities') }}',
            initialValue: '{{ old('global_identity_id', $selectedGlobalIdentity['value'] ?? '') }}',
            initialLabel: @js($selectedGlobalIdentity['label'] ?? ''),
        })" class="space-y-2">
            <x-label for="global_identity_search" :value="__('hiko.global_identity')" />
            <input type="hidden" name="global_identity_id" x-model="selectedValue">

            <div class="relative">
                <x-input id="global_identity_search" type="text" class="block w-full mt-1" x-model="searchQuery"
                    x-on:focus="openDropdown()" x-on:input="openDropdown()"
                    x-on:keydown.arrow-down.prevent="highlightNext()"
                    x-on:keydown.arrow-up.prevent="highlightPrev()"
                    x-on:keydown.enter.prevent="selectHighlighted()" x-on:keydown.escape="isOpen = false"
                    autocomplete="off" :placeholder="__('hiko.search') . '...'" />

                <button x-show="selectedValue || searchQuery" type="button"
                    class="absolute inset-y-0 right-0 flex items-center p-2 text-gray-400 hover:text-gray-700"
                    x-on:click="clearSelection()">×</button>

                <div x-show="isOpen" @click.away="isOpen = false"
                    class="absolute z-50 mt-1 w-full rounded-md bg-white shadow-lg max-h-60 overflow-y-auto py-1 text-sm ring-1 ring-black ring-opacity-5">
                    <div x-show="loading" class="p-2 text-center text-gray-500">{{ __('hiko.loading') }}</div>
                    <div x-show="!loading && options.length === 0" class="p-2 text-center text-gray-500">{{ __('hiko.no_results') }}</div>
                    <template x-for="(option, idx) in options" :key="option.value">
                        <div x-on:click="selectOption(option)" x-on:mouseenter="highlightedIndex = idx"
                            class="cursor-pointer px-3 py-2"
                            :class="{ 'bg-primary text-white': highlightedIndex === idx }">
                            <span x-text="option.label"></span>
                        </div>
                    </template>
                </div>
            </div>

            @error('global_identity_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <livewire:related-identity-resources :resources="$identity->related_identity_resources" />

        <div>
            <x-label for="note" :value="__('hiko.note')" />
            <x-textarea name="note" id="note" rows="3"
                class="block w-full mt-1" style="min-height: 90px;">{{ old('note', $identity->note) }}</x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <x-button-simple class="w-full" name="action" value="edit">
            {{ $label }}
        </x-button-simple>

        <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
            {{ $label }} {{ __('hiko.and_create_new') }}
        </x-button-inverted>
    </form>

    @if ($canMerge)
        <x-merge-form :oldId="$identity->id" model="identity" route="{{ route('ajax.identities') }}" />
    @endif

    @can('delete-metadata')
        @if ($canRemove)
            <form x-data="{ form: $el }" action="{{ route('identities.destroy', $identity->id) }}" method="post"
                class="max-w-lg space-y-6">
                @csrf
                @method('DELETE')
                <x-button-danger type="button" class="w-full"
                    x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                    {{ __('hiko.remove') }}
                </x-button-danger>
            </form>
        @endif
    @endcan

    @push('scripts')
        <script>
            // Form changes tracker
            document.addEventListener('DOMContentLoaded', function() {
                // Define variables
                var formChanged = false;
                var confirmLeaveMessage = '{{ __('hiko.confirm_leave') }}';

                // Function to mark form as changed
                function markFormAsChanged() {
                    formChanged = true;
                }

                // Function to mark form as saved/unchanged
                function markFormAsUnchanged() {
                    formChanged = false;
                }

                // Get all forms on the page
                const forms = document.querySelectorAll('form');
                forms.forEach(form => {
                    // Standard inputs, textareas, selects
                    const formElements = form.querySelectorAll('input, textarea, select');
                    formElements.forEach(element => {
                        // Different event listeners based on element type
                        if (element.type === 'text' || element.type === 'password' || element.type ===
                            'email' ||
                            element.type === 'number' || element.type === 'search' || element.type ===
                            'tel' ||
                            element.type === 'url' || element.tagName === 'TEXTAREA') {
                            element.addEventListener('input', markFormAsChanged);
                            element.addEventListener('change', markFormAsChanged);
                        } else if (element.type === 'checkbox' || element.type === 'radio') {
                            element.addEventListener('change', markFormAsChanged);
                        } else if (element.tagName === 'SELECT') {
                            element.addEventListener('change', markFormAsChanged);

                            // For select elements that might be using Choices.js
                            if (element.classList.contains('choices__input') ||
                                element.parentElement?.classList.contains('choices')) {
                                // Try to find related Choices container and listen for mutations
                                const choicesContainer = element.closest('.choices');
                                if (choicesContainer) {
                                    const observer = new MutationObserver(markFormAsChanged);
                                    observer.observe(choicesContainer, {
                                        childList: true,
                                        subtree: true
                                    });
                                }
                            }
                        }
                    });

                    // Add listener for form submission
                    form.addEventListener('submit', markFormAsUnchanged);
                });

                // Handle buttons that should bypass the warning
                // Save buttons
                const saveButtons = document.querySelectorAll(
                    'button[type="submit"], input[type="submit"], [name="action"]');
                saveButtons.forEach(button => {
                    button.addEventListener('click', markFormAsUnchanged);
                });

                // Delete/cancel buttons
                const specialButtons = document.querySelectorAll(
                    '.btn-danger, [x-on\\:click*="confirm"], [data-no-confirm]');
                specialButtons.forEach(button => {
                    button.addEventListener('click', markFormAsUnchanged);
                });

                // Alpine.js driven elements
                document.addEventListener('change', function(e) {
                    // Target is within a form and has x-model attribute
                    if (e.target.closest('form') && (e.target.hasAttribute('x-model') ||
                            e.target.hasAttribute(':value') ||
                            e.target.hasAttribute('x-bind:value'))) {
                        markFormAsChanged();
                    }
                });

                // Listen for Alpine.js initialization
                document.addEventListener('alpine:initialized', function() {
                    // Find all Alpine-powered elements within forms
                    const alpineElements = document.querySelectorAll(
                        'form [x-data], form [x-model], form [x-bind], form [x-on\\:input]');
                    alpineElements.forEach(el => {
                        // For elements with x-model
                        if (el.hasAttribute('x-model')) {
                            el.addEventListener('input', markFormAsChanged);
                            el.addEventListener('change', markFormAsChanged);
                        }

                        // For elements with x-data (might contain reactive elements)
                        if (el.hasAttribute('x-data')) {
                            const observer = new MutationObserver(function(mutations) {
                                mutations.forEach(function(mutation) {
                                    if (mutation.type === 'attributes' || mutation
                                        .type === 'childList') {
                                        markFormAsChanged();
                                    }
                                });
                            });

                            observer.observe(el, {
                                attributes: true,
                                childList: true,
                                subtree: true
                            });
                        }
                    });
                });

                // Set up the beforeunload event
                window.onbeforeunload = function(e) {
                    if (formChanged) {
                        return confirmLeaveMessage;
                    }
                };

                // Expose functions globally for programmatic control
                window.formTracker = {
                    markChanged: markFormAsChanged,
                    markUnchanged: markFormAsUnchanged,
                    isChanged: () => formChanged
                };

                // For iframes in modals (keeping this functionality)
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe) {
                    iframe.addEventListener('load', function() {
                        try {
                            var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                            var header = iframeContent.querySelector('header');
                            var footer = iframeContent.querySelector('footer');
                            if (header) header.style.display = 'none';
                            if (footer) footer.style.display = 'none';
                        } catch (error) {
                            console.warn('Unable to access iframe content:', error);
                        }
                    });
                });
            });

            // Keep the debounce function
            function debounce(func, wait, immediate) {
                var timeout;
                return function() {
                    var context = this,
                        args = arguments;
                    var later = function() {
                        timeout = null;
                        if (!immediate) func.apply(context, args);
                    };
                    var callNow = immediate && !timeout;
                    clearTimeout(timeout);
                    timeout = setTimeout(later, wait);
                    if (callNow) func.apply(context, args);
                };
            }

            // Use debounce with the AJAX request
            var searchInput = document.getElementById('mentioned');
            if (searchInput) {
                searchInput.addEventListener('input', debounce(function(e) {
                    // Make AJAX request here
                }, 500));
            }

            // Hide header and footer in modals
            function hideHeaderFooterInIframe() {
                var modals = document.querySelectorAll('.modal-window');
                modals.forEach(function(modal) {
                    var iframe = modal.querySelector('iframe');
                    if (iframe) {
                        var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                        var header = iframeContent.querySelector('header');
                        var footer = iframeContent.querySelector('footer');
                        if (header) {
                            header.style.display = 'none';
                        }
                        if (footer) {
                            footer.style.display = 'none';
                        }
                    }
                });
            }

            // Function to handle iframe content load
            function handleIframeLoad() {
                hideHeaderFooterInIframe();
            }

            // Listen for iframe load event and handle it
            document.addEventListener('DOMContentLoaded', function() {
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe) {
                    iframe.addEventListener('load', handleIframeLoad);
                });
            });

            window.globalIdentityLinkSelect = function(params) {
                return {
                    searchUrl: params.searchUrl,
                    searchQuery: params.initialLabel || '',
                    selectedValue: params.initialValue || '',
                    selectedLabel: params.initialLabel || '',
                    options: [],
                    isOpen: false,
                    loading: false,
                    highlightedIndex: 0,
                    debounceTimeout: null,

                    currentIdentityType() {
                        const typeInput = document.getElementById('type');
                        return typeInput ? typeInput.value : 'person';
                    },

                    openDropdown() {
                        this.isOpen = true;
                        this.debouncedFetch(this.searchQuery);
                    },

                    debouncedFetch(query) {
                        clearTimeout(this.debounceTimeout);
                        this.debounceTimeout = setTimeout(() => this.fetchOptions(query), 200);
                    },

                    fetchOptions(query = '') {
                        this.loading = true;
                        const url = new URL(this.searchUrl, window.location.origin);
                        url.searchParams.set('search', query || '');
                        url.searchParams.set('type', this.currentIdentityType());

                        fetch(url.toString())
                            .then((response) => response.json())
                            .then((data) => {
                                this.options = Array.isArray(data) ? data : [];
                                this.highlightedIndex = 0;
                                this.loading = false;
                            })
                            .catch(() => {
                                this.options = [];
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
                        this.openDropdown();
                    },

                    highlightNext() {
                        if (this.highlightedIndex < this.options.length - 1) {
                            this.highlightedIndex++;
                        }
                    },

                    highlightPrev() {
                        if (this.highlightedIndex > 0) {
                            this.highlightedIndex--;
                        }
                    },

                    selectHighlighted() {
                        if (this.options.length > 0 && this.highlightedIndex >= 0) {
                            this.selectOption(this.options[this.highlightedIndex]);
                        }
                    },

                };
            };
        </script>
    @endpush
</x-app-layout>
