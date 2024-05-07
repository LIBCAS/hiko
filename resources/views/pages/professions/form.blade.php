<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="similarItems({ similarNamesUrl: '{{ route('ajax.items.similar', ['model' => 'Profession']) }}', id: '{{ $profession ? $profession->id : null }}' })" x-init="$watch('search', () => findSimilarNames($data))" action="{{ $action }}" method="post"
        class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="cs" value="CS" />
            <x-input id="cs" class="block w-full mt-1" type="text" name="cs" :value="old('cs', $profession->translations['name']['cs'] ?? null)"
                x-on:change="search = $el.value" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" value="EN" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en" :value="old('cs', $profession->translations['name']['en'] ?? null)"
                x-on:change="search = $el.value" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-alert-similar-names />
        <div>
            <x-label for="category" :value="__('hiko.category')" />
            <x-select name="category" id="category" class="block w-full mt-1" x-data="ajaxChoices({ url: '{{ route('ajax.professions.category') }}', element: $el })"
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
    @if ($profession->id)
        @if ($profession->identities->count() > 0)
            <p class="mt-6 text-sm">
                {{ __('hiko.attached_persons_count') }}: {{ $profession->identities->count() }}
            </p>
            <ul class="mt-6">
                @foreach ($profession->identities as $identity)
                    <li class="max-w-sm mt-1">
                        <a href="{{ route('identities.edit', $identity->id) }}" class="text-sm font-semibold text-primary-dark hover:underline">{{ $identity->name }}</a>
                    </li>
                @endforeach
            </ul>
        @else
            <form x-data="{ form: $el }" action="{{ route('professons.destroy', $profession->id) }}" method="post"
                class="max-w-sm mt-8">
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
