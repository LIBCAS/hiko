<x-app-layout :title="$title">
    <x-success-alert />
    <form onkeydown="return event.key != 'Enter';" action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div class="required">
            <x-label for="name" :value="__('Jméno')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $place->name)"
                required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div class="required">
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
            @error('country')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="note" :value="__('Poznámka')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $place->note) }}</x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div x-data="{ open: false }" class="p-2 border rounded-md border-primary-light">
            <button type="button" @click="open = !open"
                class="inline-flex items-center font-semibold text-primary hover:underline">
                <x-heroicon-o-location-marker class="h-5 mr-2" /> <span>{{ __('Vyhledat souřadnice') }}</span>
            </button>
            <span x-show="open" x-transition.duration.500ms>
                <livewire:geonames-search />
            </span>
        </div>
        <div>
            <x-label for="latitude" :value="__('Zeměpisná šířka')" />
            <x-input id="latitude" class="block w-full mt-1" type="text" name="latitude"
                :value="old('latitude', $place->latitude)" />
            @error('latitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="longitude" :value="__('Zeměpisná délka')" />
            <x-input id="longitude" class="block w-full mt-1" type="text" name="longitude"
                :value="old('longitude', $place->longitude)" />
            @error('longitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="geoname_id" :value="__('Geoname ID')" />
            <x-input id="geoname_id" class="block w-full mt-1" type="text" name="geoname_id"
                :value="old('geoname_id', $place->geoname_id)" />
            @error('geoname_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($place->id)
        <form x-data="{ form: $el }" action="{{ route('places.destroy', $place->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('Odstraní místo! Pokračovat?')) form.submit()">
                {{ __('Odstranit místo') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
