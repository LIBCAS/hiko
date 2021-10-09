<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el }" @submit.prevent action="{{ $action }}" method="post" class="max-w-sm space-y-3" autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="cs" :value="__('CS')" />
            <x-input id="cs" class="block w-full mt-1" type="text" name="cs"
                :value="old('cs', $keyword->translations['name']['cs'] ?? null)" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" :value="__('EN')" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en"
                :value="old('cs', $keyword->translations['name']['en'] ?? null)" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="category" :value="__('Kategorie')" />
            <x-select name="category" id="category" class="block w-full mt-1"
                x-data="ajaxSelect({url: '{{ route('ajax.keywords.category') }}', element: $el })"
                x-init="initSelect()">
                @if (isset($category) && $category)
                    <option value="{{ $category['id'] }}">{{ $category['name'] }}</option>
                @endif
            </x-select>
            @error('category')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>

    @if ($keyword->id)
        <form action="{{ route('keywords.destroy', $keyword->id) }}" method="post" class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full" x-data=""
                x-on:click="return confirm('Odstraní klíčové slovo! Pokračovat?')">
                {{ __('Odstranit klíčové slovo') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
