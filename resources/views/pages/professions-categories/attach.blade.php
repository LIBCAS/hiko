<x-app-layout :title="__('Attach Profession')">
    <div class="max-w-lg mx-auto bg-white p-6 shadow rounded-md">
        <h2 class="text-xl font-semibold mb-4">{{ __('Attach Profession to Category') }}</h2>
        <form action="{{ route('professions.attach.store', $professionCategory->id) }}" method="POST">
            @csrf
            <div class="mb-4">
                <x-label for="profession_id" :value="__('Select Profession')" />
                <select name="profession_id" id="profession_id" class="block w-full mt-1">
                    @foreach ($professions as $profession)
                        <option value="{{ $profession->id }}">{{ $profession->name }}</option>
                    @endforeach
                </select>
                @error('profession_id')
                    <div class="text-red-600">{{ $message }}</div>
                @enderror
            </div>
            <x-button-simple class="w-full">
                {{ __('Attach Profession') }}
            </x-button-simple>
        </form>
    </div>
</x-app-layout>
