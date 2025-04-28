<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <!-- Form Section -->
        <div class="max-w-sm">
            <form x-data="similarItems({
                similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'ProfessionCategory']) }}',
                id: '{{ $professionCategory->id ?? null }}'
            })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="POST"
                class="space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="{{ __('CS') }}" />
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', $professionCategory->translations['name']['cs'] ?? null)"
                        x-on:change="search = $el.value" required />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="{{ __('EN') }}" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('en', $professionCategory->translations['name']['en'] ?? null)"
                        x-on:change="search = $el.value" />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Alert for Similar Names -->
                <x-alert-similar-names />

                <!-- Submit Buttons -->
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            <!-- Delete Button -->
            @if (isset($professionCategory) && $professionCategory->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }"
                        action="{{ route('professions.category.destroy', $professionCategory->id) }}" method="POST"
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

        <!-- Related Identities Section -->
        @if (isset($professionCategory) && $professionCategory->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @php
                    // Find identities related to the category
                    $relatedIdentities = collect();

                    // Get all identities that have a profession in this category
                    $professionsInCategory = \App\Models\Profession::where(
                        'profession_category_id',
                        $professionCategory->id,
                    )->get();

                    foreach ($professionsInCategory as $profession) {
                        $identities = $profession->identities;
                        foreach ($identities as $identity) {
                            if (!$relatedIdentities->contains('id', $identity->id)) {
                                $relatedIdentities->push($identity);
                            }
                        }
                    }

                    // Sort identities by name
                    $relatedIdentities = $relatedIdentities->sortBy('name');
                @endphp

                <h2 class="text-l font-semibold">
                    {{ __('hiko.attached_persons_count') }}: {{ $relatedIdentities->count() }}
                </h2>

                @if ($relatedIdentities->count() > 0)
                    <ul class="list-disc px-3 py-3">
                        @foreach ($relatedIdentities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}"
                                    class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $identity->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_attached_persons') }}</p>
                @endif
            </div>

            <!-- Related Professions Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @php
                    // Get all professions in this category
                    $categoryProfessions = \App\Models\Profession::where(
                        'profession_category_id',
                        $professionCategory->id,
                    )->get();

                    // Get count of professions with identities
                    $professionsWithIdentities = $categoryProfessions->filter(function ($prof) {
                        return $prof->identities->count() > 0;
                    });
                @endphp

                <h2 class="text-l font-semibold">
                    {{ __('hiko.professions') }}: {{ $categoryProfessions->count() }}
                </h2>

                @if ($categoryProfessions->count() > 0)
                    <ul class="list-disc px-3 py-3 space-y-1">
                        @foreach ($categoryProfessions->sortBy(function ($prof) {
        return $prof->getTranslation('name', 'cs', false) ?? ($prof->getTranslation('name', 'en', false) ?? '');
    }) as $prof)
                            @php
                                $csName = $prof->getTranslation('name', 'cs', false);
                                $enName = $prof->getTranslation('name', 'en', false);
                                $displayName = $csName ?? '';
                                if (!empty($enName) && $csName != $enName) {
                                    $displayName .= !empty($displayName) ? ' / ' . $enName : $enName;
                                }
                                if (empty($displayName)) {
                                    $displayName = __('hiko.no_name');
                                }

                                $hasIdentities = $prof->identities->count() > 0;

                                // Check for global profession link
                                $hasGlobalLink = !empty($prof->global_profession_id);
                                $globalProfessionName = null;

                                if ($hasGlobalLink) {
                                    $globalProfession = DB::table('global_professions')
                                        ->where('id', $prof->global_profession_id)
                                        ->first();

                                    if ($globalProfession) {
                                        $globalNameData = json_decode($globalProfession->name, true);
                                        $globalProfessionName =
                                            $globalNameData['cs'] ?? ($globalNameData['en'] ?? null);
                                    }
                                }
                            @endphp

                            <li>
                                <a href="{{ route('professions.edit', $prof->id) }}"
                                    class="text-sm border-b {{ $hasIdentities ? 'text-primary-dark border-primary-light hover:border-primary-dark' : 'text-gray-500 border-gray-300 hover:border-gray-700' }}">
                                    {{ $displayName }}

                                    @if ($hasGlobalLink && $globalProfessionName)
                                        <span class="text-xs text-blue-600 ml-1">(↔ {{ $globalProfessionName }})</span>
                                    @endif
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_professions_in_category') }}</p>
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
