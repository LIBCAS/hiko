<x-app-layout :title="$title">
    <x-success-alert />
    <x-form-errors />
    <form onkeydown="return event.key != 'Enter';" action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div class="required">
            <x-label for="name" :value="__('hiko.name')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $place->name)" required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div class="required">
            <x-label for="country" :value="__('hiko.country')" />
            <x-select x-data="choices({ element: $el })" x-init="initSelect()" id="country" class="block w-full mt-1"
                name="country">
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
            <x-label for="division" :value="__('hiko.division')" />
            <x-input id="division" class="block w-full mt-1" type="text" name="division" :value="old('division', $place->division)" />
            @error('division')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="note" :value="__('hiko.note')" />
            <x-textarea name="note" id="note" class="block w-full mt-1">{{ old('note', $place->note) }}</x-textarea>
            @error('note')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div x-data="{ open: false }" class="p-3 bg-gray-200 border rounded-md shadow">
            <button type="button" @click="open = !open"
                class="inline-flex items-center font-semibold text-primary hover:underline">
                <x-icons.location-marker class="h-5 mr-2" /> <span>{{ __('hiko.search_geolocation') }}</span>
            </button>
            <span x-show="open" x-transition.duration.500ms>
                <livewire:geonames-search />
            </span>
        </div>
        <div>
            <x-label for="latitude" :value="__('hiko.latitude')" />
            <x-input id="latitude" class="block w-full mt-1" type="text" name="latitude" :value="old('latitude', $place->latitude)" />
            @error('latitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="longitude" :value="__('hiko.longitude')" />
            <x-input id="longitude" class="block w-full mt-1" type="text" name="longitude" :value="old('longitude', $place->longitude)" />
            @error('longitude')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div>
            <x-label for="geoname_id" :value="__('Geoname ID')" />
            <x-input id="geoname_id" class="block w-full mt-1" type="text" name="geoname_id" :value="old('geoname_id', $place->geoname_id)" />
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
                x-on:click.prevent="if (confirm('{{ __('hiko.confirm_remove') }}')) form.submit()">
                {{ __('hiko.remove') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
