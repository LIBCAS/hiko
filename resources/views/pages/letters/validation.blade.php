<x-app-layout :title="$title">
    <x-success-alert />

    <div class="mb-6">
        <a href="{{ route('letters') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <span>←</span> {{ __('hiko.back_to_letters') }}
        </a>
    </div>

    <livewire:letters-consistency-check />
</x-app-layout>
