<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el }" @submit.prevent action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="cs" :value="__('CS')" />
            <x-input id="cs" class="block w-full mt-1" type="text" name="cs"
                :value="old('cs', $keywordCategory->translations['name']['cs'] ?? null)" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" :value="__('EN')" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en"
                :value="old('cs', $keywordCategory->translations['name']['en'] ?? null)" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>

    @if ($keywordCategory->id)
        @if ($keywordCategory->keywords->count() > 0)
            <p class="mt-6 text-sm">
                {{ __('Počet závislých klíčových slov:') }} {{ $keywordCategory->keywords->count() }}
            </p>
        @else
            <form action="{{ route('keywords.category.destroy', $keywordCategory->id) }}" method="post"
                class="max-w-sm mt-8">
                @csrf
                @method('DELETE')
                <x-button-danger class="w-full" x-data=""
                    x-on:click="return confirm('Odstraní klíčové slovo! Pokračovat?')">
                    {{ __('Odstranit klíčové slovo') }}
                </x-button-danger>
            </form>
        @endif
    @endif
</x-app-layout>
