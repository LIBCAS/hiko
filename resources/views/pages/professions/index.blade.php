<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('professions.create') }}" class="max-w-sm px-2 py-1 font-bold text-primary hover:underline">
        + {{ __('Nová profese') }}
    </a>
    <livewire:professions-table />
    <a href="{{ route('professions.category.create') }}"
        class="block max-w-sm px-2 py-1 mt-16 font-bold text-primary hover:underline">
        + {{ __('Nová kategorie profese') }}
    </a>
    <livewire:profession-category-table />
</x-app-layout>
