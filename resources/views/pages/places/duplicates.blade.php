<x-app-layout :title="$title">
    <x-success-alert />
    <div class="mb-4">
        <a href="{{ route('places') }}" class="text-sm text-gray-600 hover:text-gray-900">
            ← {{ __('hiko.back_to_places') }}
        </a>
    </div>
    <livewire:places-consistency-check />
</x-app-layout>
