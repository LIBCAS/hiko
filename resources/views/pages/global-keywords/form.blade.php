<x-app-layout :title="$title">
    <!-- Success Alert -->
    <x-success-alert />

    <!-- Main Grid Layout -->
    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        
        <!-- Left Column: Keyword Form -->
        <div class="max-w-sm">
            <form 
                x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'GlobalKeyword']) }}', id: '{{ $keyword->id ?? null }}' })" 
                x-init="$watch('search', () => findSimilarNames($data))" 
                action="{{ $action }}" 
                method="POST" 
                class="space-y-3" 
                autocomplete="off"
            >
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="CS" />
                    <x-input 
                        id="cs" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="cs" 
                        :value="old('cs', $keyword->getTranslation('name', 'cs') ?? '')"
                        x-on:change="search = $el.value" 
                        required
                    />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="EN" />
                    <x-input 
                        id="en" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="en" 
                        :value="old('en', $keyword->getTranslation('name', 'en') ?? '')"
                        x-on:change="search = $el.value" 
                    />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Similar Names Alert -->
                <x-alert-similar-names />

                <!-- Category Dropdown -->
                <div class="required">
                    <x-label for="category_id" :value="__('hiko.category')" />
                    <x-select 
                        name="category_id" 
                        id="category" 
                        class="block w-full mt-1" 
                        x-data="ajaxChoices({ url: '{{ route('ajax.global.keywords.category') }}', element: $el })"
                        x-init="initSelect()" 
                        required
                    >
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
                <livewire:create-new-item-modal 
                    :route="route('global.keywords.category.create')" 
                    :text="__('hiko.modal_new_keyword_category')" 
                />

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
                    <form 
                        x-data="{ form: $el }" 
                        action="{{ route('global.keywords.destroy', ['globalKeyword' => $keyword->id]) }}" 
                        method="POST"
                        class="max-w-sm mt-8"
                    >
                        @csrf
                        @method('DELETE')
                        <x-button-danger 
                            class="w-full"
                            x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()"
                        >
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
            // Prevent Leaving Confirmation
            var preventLeaving = true;
            window.onbeforeunload = function(e) {
                if (preventLeaving) {
                    return '{{ __('hiko.confirm_leave') }}';
                }
            }

            // Debounce Function
            function debounce(func, wait, immediate) {
                var timeout;
                return function() {
                    var context = this, args = arguments;
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

            // Hide Header and Footer in Iframes within Modals
            document.addEventListener('DOMContentLoaded', function() {
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
        </script>
    @endpush
</x-app-layout>
