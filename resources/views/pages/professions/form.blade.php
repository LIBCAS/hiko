<x-app-layout :title="$title">
    <x-success-alert />

    <form action="{{ $action }}" method="post">
        @csrf
        @isset($method)
            @method($method)
        @endisset

        <!-- Name in CS -->
        <div class="form-group">
            <label for="name_cs">{{ __('Name (CS)') }}</label>
            <input type="text" name="name[cs]" id="name_cs" class="form-control" 
                   value="{{ old('name.cs', $profession->getTranslation('name', 'cs') ?? '') }}">
            @error('name.cs')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <!-- Name in EN -->
        <div class="form-group">
            <label for="name_en">{{ __('Name (EN)') }}</label>
            <input type="text" name="name[en]" id="name_en" class="form-control" 
                   value="{{ old('name.en', $profession->getTranslation('name', 'en') ?? '') }}">
            @error('name.en')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <!-- Category Dropdown -->
        <div class="form-group">
            <label for="category">{{ __('Category') }}</label>
            <select name="category_id" id="category" class="form-control">
                <option value="">{{ __('Select a category') }}</option>
                @foreach ($availableCategories as $category)
                    <option value="{{ $category->id }}" 
                        {{ old('category_id', $profession->profession_category_id ?? '') == $category->id ? 'selected' : '' }}>
                        {{ $category->getTranslation('name', 'cs') ?? $category->name }}
                    </option>
                @endforeach
            </select>
            @error('category_id')
                <div class="text-red-600">{{ $message }}</div>
            @enderror
        </div>

        <!-- Submit Button -->
        <div class="mt-4">
            <x-button>{{ $label }}</x-button>
        </div>
    </form>
</x-app-layout>
