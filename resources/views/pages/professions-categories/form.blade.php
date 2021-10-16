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
                :value="old('cs', $professionCategory->translations['name']['cs'] ?? null)" />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="en" :value="__('EN')" />
            <x-input id="en" class="block w-full mt-1" type="text" name="en"
                :value="old('cs', $professionCategory->translations['name']['en'] ?? null)" />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($professionCategory->id)
        @if ($professionCategory->identities->count() > 0)
            <p class="mt-6 text-sm">
                {{ __('Počet připojených osob:') }} {{ $professionCategory->identities->count() }}
            </p>
        @else
            <form x-data="{ form: $el }" action="{{ route('professions.category.destroy', $professionCategory->id) }}"
                method="post" class="max-w-sm mt-8">
                @csrf
                @method('DELETE')
                <x-button-danger class="w-full"
                    x-on:click.prevent="if (confirm('Odstraní profesi! Pokračovat?')) form.submit()">
                    {{ __('Odstranit profesi') }}
                </x-button-danger>
            </form>
        @endif
    @endif
</x-app-layout>
