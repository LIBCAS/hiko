<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.locations.similar') }}', id: '{{ $location->id }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div class="required">
            <x-label for="name" :value="__('hiko.name')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $location->name)"
                x-on:change="search = $el.value" required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-alert-similar-names />
        <div class="required">
            <x-label for="type" :value="__('hiko.type')" />
            <x-select id="type" class="block w-full mt-1" name="type" required>
                @foreach ($types as $type)
                    <option value="{{ $type }}"
                        {{ old('type', $location->type) === $type ? 'selected' : '' }}>
                        {{ __("hiko.{$type}") }}
                    </option>
                @endforeach
            </x-select>
            @error('type')
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
    @if ($location->id)
        @can('delete-metadata')
            <form x-data="{ form: $el }" action="{{ route('locations.destroy', $location->id) }}" method="post"
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
        </script>
    @endpush
</x-app-layout>
