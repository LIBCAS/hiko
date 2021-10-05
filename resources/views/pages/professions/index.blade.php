<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('professions.create') }}" class="max-w-sm px-2 py-1 font-bold text-primary hover:underline">
        + {{ __('Nová profese') }}
    </a>
    <livewire:professions-table />
</x-app-layout>
