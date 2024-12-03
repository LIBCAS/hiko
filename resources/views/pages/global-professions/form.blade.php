<x-app-layout :title="$title">
    <x-success-alert />
    
    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
            <form 
                x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'GlobalProfession']) }}', id: '{{ $profession ? $profession->id : null }}' })" 
                x-init="$watch('search', () => findSimilarNames($data))" 
                action="{{ $action }}" 
                method="post" 
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
                        :value="old('cs', $profession->getTranslation('name', 'cs') ?? null)"
                        x-on:change="search = $el.value" 
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
                        :value="old('en', $profession->getTranslation('name', 'en') ?? null)"
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
                    <x-label for="category" :value="__('hiko.category')" />
                    <x-select name="category" id="category" class="block w-full mt-1" 
                        x-data="ajaxChoices({ url: '{{ route('ajax.global.professions.category') }}', element: $el })"
                        x-init="initSelect()"
                    >
                        <option value="">{{ __('hiko.select_category') }}</option>
                        @foreach ($availableCategories as $availableCategory)
                            @if ($availableCategory instanceof \App\Models\GlobalProfessionCategory)
                                <option value="{{ $availableCategory->id }}" 
                                    {{ old('category', $profession->profession_category_id ?? null) == $availableCategory->id ? 'selected' : '' }}>
                                    {{ $availableCategory->getTranslation('name', app()->getLocale()) }}
                                </option>
                            @endif
                        @endforeach
                    </x-select>
                    @error('category')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Livewire Modal for Creating New Category -->
                <livewire:create-new-item-modal :route="route('professions.category.create')" :text="__('hiko.modal_new_profession_category')" />

                <!-- Action Buttons -->
                <x-button-simple class="w-full" name="action" value="edit">
                    {{ $label }}
                </x-button-simple>
                <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
                    {{ $label }} {{ __('hiko.and_create_new') }}
                </x-button-inverted>
            </form>

            <!-- Delete Form with Alpine.js Confirmation -->
            @if ($profession->id)
                @can('delete-metadata')
                    <form x-data="{ form: $el }" action="{{ route('global.professions.destroy', ['globalProfession' => $profession->id]) }}" method="post"
                        class="max-w-sm mt-8">
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

        <!-- Right Side Panels (Optional) -->
        @if ($profession->id)
            <!-- Identities Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($profession->identities->count() > 0)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_persons_count') }}: {{ $profession->identities->count() }}</h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($profession->identities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $identity->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_persons') }}</h2>
                @endif
            </div>

            <!-- Category Section -->
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($profession->profession_category)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_category') }}: 
                        <a href="{{ route('professions.category.edit', $profession->profession_category->id) }}" class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">
                            {{ $profession->profession_category->name }}
                        </a>
                    </h2>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_category') }}</h2>
                @endif
            </div>
        @endif
    </div>

    @push('scripts')
        <script>
            var preventLeaving = true;
            window.onbeforeunload = function(e) {
                if (preventLeaving) {
                    return '{{ __('hiko.confirm_leave') }}';
                }
            }

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
