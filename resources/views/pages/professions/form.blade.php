<x-app-layout :title="$title">
    <x-success-alert />
    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
            <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'Profession']) }}', id: '{{ $profession ? $profession->id : null }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
                class="space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset
                <div>
                    <x-label for="cs" value="CS" />
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', isset($profession) ? $profession->translations['name']['cs'] ?? null : null)"
                        x-on:change="search = $el.value" />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <div>
                    <x-label for="en" value="EN" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('en', isset($profession) ? $profession->translations['name']['en'] ?? null : null)"
                        x-on:change="search = $el.value" />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>
                <x-alert-similar-names />

                <!-- Category Dropdown -->
                <div class="required">
                    <x-label for="category" :value="__('hiko.category')" />
                    <x-select name="category" id="category" class="block w-full mt-1" x-data="ajaxChoices({ url: '{{ route('ajax.professions.category') }}', element: $el })"
                        x-init="initSelect()">
                        <option value="">{{ __('hiko.select_category') }}</option>
                        @foreach ($availableCategories as $availableCategory)
                            @if (
                                (tenancy()->initialized && $availableCategory instanceof \App\Models\ProfessionCategory) ||
                                    (!tenancy()->initialized && $availableCategory instanceof \App\Models\GlobalProfessionCategory))
                                <option value="{{ $availableCategory->id }}"
                                    {{ old('category', isset($profession) ? $profession->profession_category_id ?? null : null) == $availableCategory->id ? 'selected' : '' }}>
                                    {{ $availableCategory->getTranslation('name', app()->getLocale()) }}
                                </option>
                            @endif
                        @endforeach
                    </x-select>
                    @error('category')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <livewire:create-new-item-modal :route="route('professions.category.create')" :text="__('hiko.modal_new_profession_category')" />
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            @if (isset($profession) && $profession->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }" action="{{ route('professions.destroy', $profession->id) }}"
                        method="post" class="w-full mt-8">
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

        <!-- Right Side Panels -->
        @if (isset($profession) && $profession->id)
            <!-- Identities Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if (isset($profession->identities) && $profession->identities->count() > 0)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_persons_count') }}:
                        {{ $profession->identities->count() }}</h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($profession->identities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}"
                                    class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $identity->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_persons') }}</h2>
                @endif
            </div>

            <!-- Category Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if (isset($profession->profession_category) && $profession->profession_category)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_category') }}:
                        <a href="{{ route('professions.category.edit', $profession->profession_category->id) }}"
                            class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">
                            {{ $profession->profession_category->getTranslation('name', app()->getLocale()) }}
                        </a>
                    </h2>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_category') }}</h2>
                @endif
            </div>

            <!-- Global Profession Link (if exists) -->
            @if (isset($profession->global_profession_id) && $profession->global_profession_id)
                <div class="max-w-sm bg-white p-6 shadow rounded-md">
                    <h2 class="text-l font-semibold">{{ __('hiko.global_profession') }}:</h2>
                    @php
                        $globalProfession = DB::table('global_professions')
                            ->where('id', $profession->global_profession_id)
                            ->first();

                        if ($globalProfession) {
                            $globalNameData = json_decode($globalProfession->name, true);
                            $globalName = $globalNameData['cs'] ?? ($globalNameData['en'] ?? __('hiko.unknown'));
                        }
                    @endphp

                    @if (isset($globalProfession))
                        <div class="mt-2">
                            <a href="{{ route('global.professions.edit', $profession->global_profession_id) }}"
                                class="text-sm border-b text-blue-600 border-blue-300 hover:border-blue-600">
                                {{ $globalName }}
                            </a>
                        </div>
                    @else
                        <div class="mt-2 text-sm text-red-600">
                            {{ __('hiko.invalid_global_link', ['id' => $profession->global_profession_id]) }}
                        </div>
                    @endif
                </div>
            @endif
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

            document.addEventListener('DOMContentLoaded', function() {
                var iframes = document.querySelectorAll('iframe');
                iframes.forEach(function(iframe) {
                    iframe.addEventListener('load', function() {
                        var iframeContent = iframe.contentDocument || iframe.contentWindow.document;
                        var header = iframeContent.querySelector('header');
                        var footer = iframeContent.querySelector('footer');
                        if (header) header.style.display = 'none';
                        if (footer) footer.style.display = 'none';
                    });
                });
            });
        </script>
    @endpush
</x-app-layout>
