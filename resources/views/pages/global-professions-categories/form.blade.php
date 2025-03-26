<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <!-- Form Section -->
        <div class="max-w-sm">
            <form x-data="similarItems({
                similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'GlobalProfessionCategory']) }}',
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
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', $professionCategory->getTranslation('name', 'cs') ?? null)"
                        x-on:change="search = $el.value" required />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="{{ __('EN') }}" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('en', $professionCategory->getTranslation('name', 'en') ?? null)"
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
                        action="{{ route('global.professions.category.destroy', $professionCategory->id) }}" method="POST"
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
                    // Find all identities related to professions in this category across all tenants
                    $relatedIdentities = collect();

                    // First get all global professions in this category
                    $globalProfessionIds = isset($professionCategory->professions)
                        ? $professionCategory->professions->pluck('id')->toArray()
                        : [];

                    if (!empty($globalProfessionIds)) {
                        // Get all tenants with their domains
                        $tenants = DB::table('tenants')
                            ->leftJoin('domains', 'tenants.id', '=', 'domains.tenant_id')
                            ->select('tenants.*', 'domains.domain')
                            ->get();

                        foreach ($tenants as $tenant) {
                            $prefix = $tenant->table_prefix . '__';

                            // Check if the table exists before querying
                            $tableExists = DB::select("SHOW TABLES LIKE '{$prefix}identity_profession'");

                            if (!empty($tableExists)) {
                                // Check if global_profession_id column exists in this tenant's table
            $columnsQuery = DB::select(
                "SHOW COLUMNS FROM `{$prefix}identity_profession` LIKE 'global_profession_id'",
            );
            $hasGlobalProfessionId = !empty($columnsQuery);

            if ($hasGlobalProfessionId) {
                // Get identities from this tenant related to our global professions
                try {
                    $tenantIdentities = DB::table("{$prefix}identity_profession")
                        ->whereIn('global_profession_id', $globalProfessionIds)
                        ->join("{$prefix}identities", 'identity_id', '=', "{$prefix}identities.id")
                        ->select([
                            "{$prefix}identities.id",
                            "{$prefix}identities.name",
                            DB::raw("'{$tenant->name}' as tenant_name"),
                            DB::raw("'{$tenant->domain}' as tenant_domain"),
                        ])
                        ->get();

                    foreach ($tenantIdentities as $identity) {
                        $relatedIdentities->push($identity);
                    }
                } catch (\Exception $e) {
                    // Log error but continue with other tenants
                    Log::error("Error querying tenant {$tenant->name}: " . $e->getMessage());
                }
            }
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
                                <a href="{{ isset($identity->tenant_domain) ? 'https://' . $identity->tenant_domain . '/identities/' . $identity->id . '/edit' : '#' }}"
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
                <h2 class="text-l font-semibold">
                    {{ __('hiko.professions') }}:
                    {{ $professionCategory->professions ? $professionCategory->professions->count() : 0 }}
                </h2>

                @if (isset($professionCategory->professions) && $professionCategory->professions->count() > 0)
                    <ul class="list-disc px-3 py-3">
                        @foreach ($professionCategory->professions->sortBy(function ($profession) {
        return $profession->getTranslation('name', 'cs', false) ?? ($profession->getTranslation('name', 'en', false) ?? '');
    }) as $profession)
                            <li>
                                <a href="{{ route('global.professions.edit', $profession->id) }}"
                                    class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $profession->getTranslation('name', 'cs', false) ??
                                        ($profession->getTranslation('name', 'en', false) ?? __('hiko.no_name')) }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <p class="text-sm text-gray-500 mt-2">{{ __('hiko.no_attached_professions') }}</p>
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
