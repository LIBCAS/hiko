<x-app-layout :title="$title">
    <x-success-alert />

    <div class="mb-6">
        <a href="{{ route('places') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <span>←</span> {{ __('hiko.back_to_places') }}
        </a>
    </div>

    <livewire:places-consistency-check />
</x-app-layout>
