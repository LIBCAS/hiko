<x-app-layout :title="$title">
    <x-success-alert />

    <div class="grid grid-cols-3 gap-4 mb-4 space-y-3">
        <div class="max-w-sm">
            <form action="{{ $action }}" method="POST" class="space-y-3" autocomplete="off">
                @csrf
                @isset($method)
                    @method($method)
                @endisset

                <div>
                    <x-label for="cs" value="CS" />
                    <x-input id="cs" class="block w-full mt-1" type="text" name="cs"
                        :value="old('cs', $profession->getTranslation('name', 'cs'))" />
                    @error('cs')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-label for="en" value="EN" />
                    <x-input id="en" class="block w-full mt-1" type="text" name="en"
                        :value="old('en', $profession->getTranslation('name', 'en'))" />
                    @error('en')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <div>
                    <x-label for="category" :value="__('hiko.category')" />
                    <x-select name="category" id="category" class="block w-full mt-1">
                        <option value="">{{ __('Select Category') }}</option>
                        @foreach ($availableCategories as $availableCategory)
                            <option value="{{ $availableCategory->id }}"
                                {{ old('category', $profession->profession_category_id) == $availableCategory->id ? 'selected' : '' }}>
                                {{ $availableCategory->name }}
                            </option>
                        @endforeach
                    </x-select>
                    @error('category')
                        <div class="text-red-600">{{ $message }}</div>
                    @enderror
                </div>

                <x-button-simple class="w-full">
                    {{ $label }}
                </x-button-simple>
            </form>

            @if ($profession->id)
                @can('delete', $profession)
                    <form action="{{ route('professions.destroy', $profession->id) }}" method="POST" class="max-w-sm mt-8">
                        @csrf
                        @method('DELETE')
                        <x-button-danger class="w-full" onclick="return confirm('{{ __('hiko.confirm_remove') }}')">
                            {{ __('hiko.remove') }}
                        </x-button-danger>
                    </form>
                @endcan
            @endif
        </div>

        @if ($profession->id)
            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($profession->identities->count() > 0)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_persons_count') }}: {{ $profession->identities->count() }}</h2>
                    <ul class="list-disc px-3 py-3">
                        @foreach ($profession->identities as $identity)
                            <li>
                                <a href="{{ route('identities.edit', $identity->id) }}" class="text-sm border-b text-primary-dark border-primary-light hover:border-primary-dark">
                                    {{ $identity->name }}
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_persons') }}</h2>
                @endif
            </div>

            <div class="max-w-sm bg-white p-6 shadow rounded-md">
                @if ($profession->profession_category)
                    <h2 class="text-l font-semibold">{{ __('hiko.attached_category') }}:
                        <a href="{{ route('professions.category.edit', $profession->profession_category->id) }}" class="border-b-2 text-primary-dark border-primary-light hover:border-primary-dark">
                            {{ $profession->profession_category->name }}
                        </a>
                    </h2>
                @else
                    <h2 class="text-l font-semibold">{{ __('hiko.no_attached_category') }}</h2>
                @endif
            </div>
        @endif
    </div>
</x-app-layout>
