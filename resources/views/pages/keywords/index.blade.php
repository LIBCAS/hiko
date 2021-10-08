<x-app-layout :title="$title">
    <x-success-alert />
    <a href="{{ route('keywords.create') }}" class="max-w-sm px-2 py-1 font-bold text-primary hover:underline">
        + {{ __('Nové klíčové slovo') }}
    </a>
    <livewire:keywords-table />
</x-app-layout>
