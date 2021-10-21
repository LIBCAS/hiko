<x-app-layout :title="$title">
    <x-success-alert />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nové klíčové slovo') }}" link="{{ route('keywords.create') }}" />
        <a href="{{ route('keywords.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('Exportovat') }}
        </a>
    @endcan
    <livewire:keywords-table />
    @can('manage-metadata')
        <x-create-link label="{{ __('Nová kategorie klíčových slov') }}" link="{{ route('keywords.category.create') }}"
            class="mt-16" />
        <a href="{{ route('keywords.category.export') }}" class="inline-block mt-3 text-sm font-semibold">
            {{ __('Exportovat') }}
        </a>
    @endcan
    @cannot('manage-metadata')
        <p class="mt-16 font-bold">
            {{ __('Kategorie klíčových slov') }}
        </p>
    @endcannot
    <livewire:keywords-categories-table />
</x-app-layout>
