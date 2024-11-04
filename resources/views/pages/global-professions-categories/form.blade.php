<x-app-layout :title="$title">
    <x-success-alert />

    <form 
        action="{{ $action }}" 
        method="POST" 
        class="space-y-3" 
        autocomplete="off"
    >
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <!-- CS Field -->
        <div>
            <x-label for="cs" value="{{ __('CS') }}" />
            <x-input 
                id="cs" 
                class="block w-full mt-1" 
                type="text" 
                name="cs" 
                :value="old('cs', $professionCategory->getTranslation('name', 'cs') ?? null)"
            />
            @error('cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <!-- EN Field -->
        <div>
            <x-label for="en" value="{{ __('EN') }}" />
            <x-input 
                id="en" 
                class="block w-full mt-1" 
                type="text" 
                name="en" 
                :value="old('en', $professionCategory->getTranslation('name', 'en') ?? null)"
            />
            @error('en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <x-button-simple class="w-full">{{ $label }}</x-button-simple>
        <x-button-inverted class="w-full text-black bg-white">{{ $label }} {{ __('hiko.and_create_new') }}</x-button-inverted>
    </form>
</x-app-layout>
