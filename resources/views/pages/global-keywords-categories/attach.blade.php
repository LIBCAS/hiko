<x-app-layout :title="__('Attach Keyword')">
    <div class="max-w-lg mx-auto bg-white p-6 shadow rounded-md">
        <h2 class="text-xl font-semibold mb-4">{{ __('Attach Keyword to Category') }}</h2>
        <form action="{{ route('keywords.category.attach', ['category' => $keywordCategory->id]) }}" method="POST">
            @csrf
            <div class="mb-4">
                <x-label for="keyword_id" :value="__('Select Keyword')" />
                <select name="keyword_id" id="keyword_id" class="block w-full mt-1">
                    @foreach ($keywords as $keyword)
                        <option value="{{ $keyword->id }}">{{ $keyword->name }}</option>
                    @endforeach
                </select>
                @error('keyword_id')
                    <div class="text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <x-button-simple class="w-full">
                {{ __('Attach Keyword') }}
            </x-button-simple>
        </form>
    </div>
</x-app-layout>
