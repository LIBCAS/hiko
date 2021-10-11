<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el }" @submit.prevent action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div>
            <x-label for="name" :value="__('Jméno')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $place->name)"
                required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="country" :value="__('Země')" />
            <x-select x-data="select({element: $el })" x-init="initSelect()" id="country" class="block w-full mt-1"
                name="country" required>
                @foreach ($countries as $country)
                    <option value="{{ $country->name }}"
                        {{ old('country', $place->country) == $country->name ? 'selected' : '' }}>
                        {{ $country->name }}
                    </option>
                @endforeach
            </x-select>
        </div>
        <div>
            <x-label for="note" :value="__('Poznámka')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $place->note) }}</x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div x-data="{ open: false }" class="p-2 border rounded-md border-primary-light">
            <button type="button" @click="open = !open" class="inline-flex items-center font-semibold text-primary hover:underline">
                <x-heroicon-o-location-marker class="h-5 mr-2" /> <span>{{ __('Vyhledat souřadnice') }}</span>
            </button>
            <span x-show="open" x-transition.duration.500ms>
                <livewire:geonames-search />
            </span>
        </div>
        <div>
            <x-label for="latitude" :value="__('Zeměpisná šířka')" />
            <x-input id="latitude" class="block w-full mt-1" type="text" name="latitude"
                :value="old('name', $place->latitude)" />
            @error('latitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="longitude" :value="__('Zeměpisná délka')" />
            <x-input id="longitude" class="block w-full mt-1" type="text" name="longitude"
                :value="old('name', $place->longitude)" />
            @error('longitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="geoname_id" :value="__('Geoname ID')" />
            <x-input id="geoname_id" class="block w-full mt-1" type="text" name="geoname_id"
                :value="old('name', $place->geoname_id)" />
            @error('geoname_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($place->id)
        <form action="{{ route('places.destroy', $place->id) }}" method="post" class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full" x-data=""
                x-on:click="return confirm('Odstraní místo! Pokračovat?')">
                {{ __('Odstranit místo') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
