<x-app-layout :title="$title">
    <x-success-alert />
    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
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
                @can('delete-metadata')
                    <form x-data="{ form: $el }"
                        action="{{ route('keywords.category.destroy', $keywordCategory->id) }}" method="post"
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

        @if ($keywordCategory->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($keywordCategory->keywords->count() > 0)
                    <h2 class="text-l font-semibold">{{ __('hiko.keywords') }}: {{ $keywordCategory->keywords->count() }}</h2>
                    <ul class="list-disc p-3">
                        @foreach ($keywordCategory->keywords->sortBy('name') as $keyword)
                            <li>
                                <a href="{{ route('keywords.edit', $keyword->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">{{ $keyword->name }}</a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_keywords') }}</h2>
                @endif
            </div>
        @endif

    </div>
</x-app-layout>
