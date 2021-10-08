<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('keywords.create') }}" class="max-w-sm px-2 py-1 font-bold text-primary hover:underline">
        + {{ __('Nové klíčové slovo') }}
    </a>
    <livewire:keywords-table />
    <a href="{{ route('keywords.category.create') }}"
        class="block max-w-sm px-2 py-1 mt-16 font-bold text-primary hover:underline">
        + {{ __('Nová kategorie klíčových slov') }}
    </a>
    <livewire:keywords-categories-table />
</x-app-layout>
