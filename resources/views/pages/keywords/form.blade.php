<x-app-layout :title="$title">
    @if ($category)
        <h2 class="text-l font-semibold">{{ __('hiko.attached_category') }}: <a href="{{ route('keywords.category.edit', $category['id']) }}" class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">{{ $category['label'] }}</a></h2>
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
                        @foreach($keyword->letters as $letter)
                            <li class="text-sm">
                                {{ $letter->id }} - <a href="{{ route('letters.edit', $letter->id) }}" class="border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $letter->name }}</a>
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
        @production
            <script>
                var preventLeaving = true;
                window.onbeforeunload = function(e) {
                    if (preventLeaving) {
                        return '{{ __('hiko.confirm_leave') }}'
                    }
                }

                // Add a debounce function
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

                // Use debounce with the AJAX request
                var searchInput = document.getElementById('mentioned');
                searchInput.addEventListener('input', debounce(function(e) {
                  // Make AJAX request here
                }, 500));

            </script>
        @endproduction
            <script>
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
