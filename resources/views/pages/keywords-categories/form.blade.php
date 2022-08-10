<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'KeywordCategory']) }}', id: '{{ $keywordCategory->id }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="cs" value="CS" />
            <x-input id="cs" class="block w-full mt-1" type="text" name="cs" x-on:change="search = $el.value"
                :value="old('cs', $keywordCategory->translations['name']['cs'] ?? null)" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" value="EN" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en" x-on:change="search = $el.value"
                :value="old('cs', $keywordCategory->translations['name']['en'] ?? null)" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-alert-similar-names />
        <x-button-simple class="w-full" name="action" value="edit">
            {{ $label }}
        </x-button-simple>
        <x-button-inverted class="w-full text-black bg-white" name="action" value="create">
            {{ $label }} {{ __('hiko.and_create_new') }}
        </x-button-inverted>
    </form>
    @if ($keywordCategory->id)
        @if ($keywordCategory->keywords->count() > 0)
            <p class="mt-6 text-sm">
                {{ __('hiko.attached_keywords_count') }} {{ $keywordCategory->keywords->count() }}
            </p>
        @else
            <form x-data="{ form: $el }" action="{{ route('keywords.category.destroy', $keywordCategory->id) }}"
                method="post" class="max-w-sm mt-8">
                @csrf
                @method('DELETE')
                <x-button-danger class="w-full"
                    x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                    {{ __('hiko.remove') }}
                </x-button-danger>
            </form>
        @endif
    @endif
</x-app-layout>
