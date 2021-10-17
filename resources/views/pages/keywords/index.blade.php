<x-app-layout :title="$title">
    <x-success-alert />
    <x-create-link label="{{ __('Nové klíčové slovo') }}" link="{{ route('keywords.create') }}" />
    <a href="{{ route('keywords.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('Exportovat') }}
    </a>
    <livewire:keywords-table />
    <x-create-link label="{{ __('Nová kategorie klíčových slov') }}" link="{{ route('keywords.category.create') }}" class="mt-16" />
    <a href="{{ route('keywords.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
        {{ __('Exportovat') }}
    </a>
    <livewire:keywords-categories-table />
</x-app-layout>
