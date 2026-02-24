<x-app-layout :title="$title">
    <x-success-alert />
    <div class="mb-6">
        <a href="{{ route('professions') }}" class="text-sm text-gray-600 hover:text-gray-900 flex items-center gap-1">
            <span>←</span> {{ __('hiko.back_to_professions') }}
        </a>
    </div>

    <livewire:professions-consistency-check />
</x-app-layout>
