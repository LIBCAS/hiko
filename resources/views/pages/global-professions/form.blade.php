<x-app-layout :title="$title">
    <x-success-alert />
    <div class="grid-cols-3 grid gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
            <form 
                action="{{ $action }}" 
                method="post" 
                class="space-y-3" 
                autocomplete="off"
            >
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <!-- CS Field -->
                <div>
                    <x-label for="cs" value="CS" />
                    <x-input 
                        id="cs" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="cs" 
                        :value="old('cs', $profession->getTranslation('name', 'cs') ?? null)"
                    />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- EN Field -->
                <div>
                    <x-label for="en" value="EN" />
                    <x-input 
                        id="en" 
                        class="block w-full mt-1" 
                        type="text" 
                        name="en" 
                        :value="old('en', $profession->getTranslation('name', 'en') ?? null)"
                    />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <!-- Category Dropdown -->
                <div class="required">
                    <x-label for="category_id" :value="__('hiko.category')" />
                    <x-select name="category_id" id="category_id" class="block w-full mt-1"
                        x-data="ajaxChoices({ url: '{{ route('ajax.global.professions.category') }}', element: $el })"
                        x-init="initSelect()"
                    >
                        <option value="">{{ __('hiko.select_category') }}</option>
                        @foreach ($availableCategories as $availableCategory)
                            @if ($availableCategory instanceof \App\Models\GlobalProfessionCategory)
                                <option value="{{ $availableCategory->id }}" 
                                    {{ old('category_id', $profession->profession_category_id ?? null) == $availableCategory->id ? 'selected' : '' }}>
                                    {{ $availableCategory->getTranslation('name', app()->getLocale()) }}
                                </option>
                            @endif
                        @endforeach
                    </x-select>
                    @error('category_id')
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
        </div>
    </div>
</x-app-layout>
