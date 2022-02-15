<x-app-layout :title="$title">
    <x-success-alert />
    <form onkeydown="return event.key != 'Enter';" action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="cs" value="CS" />
            <x-input id="cs" class="block w-full mt-1" type="text" name="cs"
                :value="old('cs', $keyword->translations['name']['cs'] ?? null)" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" value="EN" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en"
                :value="old('cs', $keyword->translations['name']['en'] ?? null)" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="category" :value="__('hiko.category')" />
            <x-select name="category" id="category" class="block w-full mt-1"
                x-data="ajaxSelect({url: '{{ route('ajax.keywords.category') }}', element: $el, options: JSON.parse(document.getElementById('selectedCategory').innerHTML) })"
                x-init="initSelect()">
            </x-select>
            @error('category')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($keyword->id)
        <form x-data="{ form: $el }" action="{{ route('keywords.destroy', $keyword->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                {{ __('hiko.remove') }}
            </x-button-danger>
        </form>
    @endif
    @push('scripts')
        <script id="selectedCategory" type="application/json">
            @json($category)
        </script>
    @endpush
</x-app-layout>
