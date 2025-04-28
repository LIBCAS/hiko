<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />

    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm col-span-1">
            <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.places.similar') }}', id: '{{ $place->id }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
                class="space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- Name Field -->
                <div class="required">
                    <x-label for="name" :value="__('hiko.name')" />
                    <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $place->name)"
                        x-on:change="search = $el.value" required />
                    @error('name')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Country Select -->
                <div class="required">
                    <x-label for="country" :value="__('hiko.country')" />
                    <x-select x-data="choices({ element: $el })" x-init="initSelect()" id="country" class="block w-full mt-1"
                        name="country">
                        @foreach ($countries as $country)
                            <option value="{{ $country->name }}"
                                {{ old('country', $place->country) == $country->name ? 'selected' : '' }}>
                                {{ $country->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @error('country')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Division and Note Fields -->
                <div>
                    <x-label for="division" :value="__('hiko.division')" />
                    <x-input id="division" class="block w-full mt-1" type="text" name="division"
                        :value="old('division', $place->division)" />
                    @error('division')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-label for="note" :value="__('hiko.note')" />
                    <x-textarea name="note" id="note" rows="3"
                        class="block w-full mt-1" style="min-height: 90px;">{{ old('note', $place->note) }}</x-textarea>
                    @error('note')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Geolocation Search Section -->
                <div x-data="{ open: false, cityName: '' }" x-ref="cityNameContainer" class="p-3 bg-gray-200 border rounded-md shadow">
                    <button type="button" @click="open = !open"
                        class="inline-flex items-center font-semibold text-primary hover:underline">
                        <x-icons.location-marker class="h-5 mr-2" />
                        <span x-text="cityName ? cityName : '{{ __('hiko.search_geolocation') }}'"></span>
                    </button>
                    <span x-show="open" x-transition.duration.500ms>
                        <livewire:geonames-search latitude="{{ $place->latitude }}"
                            longitude="{{ $place->longitude }}" geoname_id="{{ $place->geoname_id }}" />
                    </span>
                </div>

                <!-- Latitude and Longitude Fields -->
                <div>
                    <x-label for="latitude" :value="__('hiko.latitude')" />
                    <x-input id="latitude" class="block w-full mt-1" type="text" name="latitude" :value="old('latitude', $place->latitude)" />
                    @error('latitude')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-label for="longitude" :value="__('hiko.longitude')" />
                    <x-input id="longitude" class="block w-full mt-1" type="text" name="longitude" :value="old('longitude', $place->longitude)" />
                    @error('longitude')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Geoname ID Field -->
                <div>
                    <x-label for="geoname_id" :value="__('Geoname ID')" />
                    <x-input id="geoname_id" class="block w-full mt-1" type="text" name="geoname_id" :value="old('geoname_id', $place->geoname_id)" />
                    @error('geoname_id')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Action Buttons -->
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            @if ($place->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }" action="{{ route('places.destroy', $place->id) }}" method="post"
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

        @if ($place->alternative_names)
            <div class="bg-white p-6 shadow rounded-md col-span-2">
                <h2 class="text-l font-semibold">{{ __('hiko.alternative_place_names') }}</h2>
                <ul class="list-disc px-3 py-3">
                    @foreach ($place->alternative_names as $altName)
                        <li>{{ $altName }}</li>
                    @endforeach
                </ul>
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

            Livewire.on('updateMainForm', data => {
                console.log('updateMainForm event received:', data);

                const nameInput = document.getElementById('name');
                nameInput.value = data[0].name;
                nameInput.dispatchEvent(new Event('input')); // Trigger input event

                const latitudeInput = document.getElementById('latitude');
                latitudeInput.value = data[0].latitude;
                latitudeInput.dispatchEvent(new Event('input')); // Trigger input event

                const longitudeInput = document.getElementById('longitude');
                longitudeInput.value = data[0].longitude;
                longitudeInput.dispatchEvent(new Event('input')); // Trigger input event

                const geonameIdInput = document.getElementById('geoname_id');
                geonameIdInput.value = data[0].id;
                geonameIdInput.dispatchEvent(new Event('input')); // Trigger input event

                const cityNameContainer = document.querySelector('[x-ref="cityNameContainer"]');
                if (cityNameContainer) {
                    cityNameContainer.__x.$data.cityName = data[0].name
                }
            });
        </script>
    @endpush
</x-app-layout>
