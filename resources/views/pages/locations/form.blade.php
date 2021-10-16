<x-app-layout :title="$title">
    <x-success-alert />
    <form x-data="{ form: $el }" @submit.prevent action="{{ $action }}" method="post" class="max-w-sm space-y-3"
        autocomplete="off">
        @csrf
        @isset($method)
            @method($method)
        @endisset
        <div class="required">
            <x-label for="name" :value="__('Jméno')" />
            <x-input id="name" class="block w-full mt-1" type="text" name="name" :value="old('name', $location->name)"
                required />
            @error('name')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <div class="required">
            <x-label for="type" :value="__('Typ')" />
            <x-select id="type" class="block w-full mt-1" name="type" required>
                @foreach ($types as $key => $type)
                    <option value="{{ $key }}" {{ old('type', $location->type) == $key ? 'selected' : '' }}>
                        {{ $type }}
                    </option>
                @endforeach
            </x-select>
            @error('type')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>
        <x-button-simple type="button" @click="form.submit()" class="w-full">
            {{ $label }}
        </x-button-simple>
    </form>
    @if ($location->id)
        <form x-data="{ form: $el }" action="{{ route('locations.destroy', $location->id) }}" method="post"
            class="max-w-sm mt-8">
            @csrf
            @method('DELETE')
            <x-button-danger class="w-full"
                x-on:click.prevent="if (confirm('Odstraní místo uložení! Pokračovat?')) form.submit()">
                {{ __('Odstranit místo uložení') }}
            </x-button-danger>
        </form>
    @endif
</x-app-layout>
