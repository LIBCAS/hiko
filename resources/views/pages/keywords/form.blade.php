<x-app-layout :title="$title">
    <x-success-alert />
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
        <div>
            <x-label for="category" :value="__('hiko.category')" />
            <x-select name="category" id="category" class="block w-full mt-1" x-data="ajaxChoices({ url: '{{ route('ajax.keywords.category') }}', element: $el })"
                x-init="initSelect()">
                @if ($category)
                    <option value="{{ $category['id'] }}" selected>{{ $category['label'] }}</option>
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
</x-app-layout>
