<x-app-layout :title="$title">
    @if ($category)
        <h2 class="text-l font-semibold">{{ __('hiko.attached_category') }}: <a
                href="{{ route('keywords.category.edit', $category['id']) }}"
                class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">{{ $category['label'] }}</a>
        </h2>
    @elseif(!$category && $keyword->id)
        <h2 class="text-l font-semibold">{{ __('hiko.no_attached_category') }}</h2>
    @endif
    <x-success-alert />
    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm col-span-1">
            <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'Keyword']) }}', id: '{{ $keyword->id }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
                class="max-w-sm space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset
                <div>
                    <x-label for="cs" value="CS" />
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', $keyword->translations['name']['cs'] ?? null)"
                        x-on:change="search = $el.value" />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="en" value="EN" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('cs', $keyword->translations['name']['en'] ?? null)"
                        x-on:change="search = $el.value" />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <x-alert-similar-names />
                <div class="required">
                    <x-label for="category" :value="__('hiko.category')" />
                    <x-select name="category" id="category" class="block w-full mt-1" x-data="ajaxChoices({ url: '{{ route('ajax.keywords.category') }}', element: $el })"
                        x-init="initSelect()" required>
                        @if ($category)
                            <option value="{{ $category['id'] }}">{{ $category['label'] }}</option>
                        @endif
                    </x-select>
                    @error('category')
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

            @if ($keyword->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }" action="{{ route('keywords.destroy', $keyword->id) }}" method="post"
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
        @if ($keyword->id)
            <div class="bg-white p-6 shadow rounded-md col-span-2">
                @if ($keyword->letters->count())
                    <h2 class="text-l font-semibold">{{ __('hiko.letters') }}: {{ $keyword->letters->count() }}</h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($keyword->letters as $letter)
                            <li class="text-sm">
                                {{ $letter->id }} - <a href="{{ route('letters.edit', $letter->id) }}"
                                    class="border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $letter->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_letters') }}</h2>
                @endif
            </div>
        @endif
    </div>
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
