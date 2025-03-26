<x-app-layout :title="$title">
    <!-- Success Alert -->
    <x-success-alert />

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">

        <!-- Left Column: Keyword Form -->
        <div class="max-w-sm">
            <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'GlobalKeyword']) }}', id: '{{ $keyword->id ?? null }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="POST"
                class="space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="CS" />
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', $keyword->getTranslation('name', 'cs') ?? '')"
                        x-on:change="search = $el.value" required />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="EN" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('en', $keyword->getTranslation('name', 'en') ?? '')"
                        x-on:change="search = $el.value" />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Similar Names Alert -->
                <x-alert-similar-names />

                <!-- Category Dropdown -->
                <div class="required">
                    <x-label for="category_id" :value="__('hiko.category')" />
                    <x-select name="category_id" id="category" class="block w-full mt-1" x-data="ajaxChoices({ url: '{{ route('ajax.global.keywords.category') }}', element: $el })"
                        x-init="initSelect()" required>
                        <option value="">{{ __('hiko.select_category') }}</option>
                        @foreach ($availableCategories as $availableCategory)
                            <option value="{{ $availableCategory->id }}"
                                {{ old('category_id', $keyword->keyword_category_id ?? '') == $availableCategory->id ? 'selected' : '' }}>
                                {{ $availableCategory->getTranslation('name', app()->getLocale()) }}
                            </option>
                        @endforeach
                    </x-select>
                    @error('category_id')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Livewire Modal for Creating New Category -->
                <livewire:create-new-item-modal :route="route('global.keywords.category.create')" :text="__('hiko.modal_new_keyword_category')" />

                <!-- Action Buttons -->
                <div class="space-y-2">
                    <x-button-simple class="w-full" name="action" value="edit">
                        {{ $label }}
                    </x-button-simple>
                    <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                        {{ $label }} {{ __('hiko.and_create_new') }}
                    </x-button-inverted>
                </div>
            </form>

            <!-- Delete Form with Alpine.js Confirmation -->
            @if ($keyword->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }"
                        action="{{ route('global.keywords.destroy', ['globalKeyword' => $keyword->id]) }}" method="POST"
                        class="w-full mt-8">
                        @csrf
                        @method('DELETE')
                        <x-button-danger class="w-full"
                            x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                            {{ __('hiko.remove') }}
                        </x-button-danger>
                    </form>
                @endcan
            @endif
        </div>

        <!-- Right Column: Attached Category Details -->
        @if ($keyword->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md col-span-2">
                @if ($keyword->keyword_category)
                    <h2 class="text-lg font-semibold">
                        {{ __('hiko.attached_category') }}:
                        <a href="{{ route('global.keywords.category.edit', $keyword->keyword_category->id) }}"
                            class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">
                            {{ $keyword->keyword_category->getTranslation('name', app()->getLocale()) }}
                        </a>
                    </h2>
                @else
                    <h2 class="text-lg font-semibold">{{ __('hiko.no_attached_category') }}</h2>
                @endif
            </div>
        @endif
    </div>

    <!-- Scripts Section -->

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
            searchInput.addEventListener('input', debounce(function(e) {
                // Make AJAX request here
            }, 500));

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
        </script>
    @endpush
</x-app-layout>
